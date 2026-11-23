<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Activity;
use App\Support\Avatars;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Profil & pengaturan akun sendiri (semua role): data diri, foto/avatar, sesi login, tampilan & bahasa.
 * Penghapusan akun hanya oleh admin (AdminController::destroy).
 * Ganti password tetap ditangani AccountController (rute lama /akun/password tetap berlaku).
 */
class SettingsController extends Controller
{
    /** Ukuran sisi foto yang disimpan (px). Foto selalu dipotong persegi di browser lalu diproses ulang di server. */
    private const PHOTO_SIZE = 512;

    private const DISK = 'local';

    public const TABS = ['profil', 'keamanan', 'tampilan', 'akun'];

    public function show(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'profil';
        $user = $request->user();

        return view('account.settings', [
            'tab' => $tab,
            'user' => $user,
            'presets' => Avatars::PRESETS,
            'sessions' => $this->sessions($request),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\pL\pM\s.\'-]+$/u'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s-]{8,20}$/'],
        ], [
            'name.regex' => __('Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.'),
            'phone.regex' => __('Nomor HP berisi 8–20 angka (boleh diawali +).'),
        ], ['name' => __('nama'), 'phone' => __('nomor HP')]);

        $user = $request->user();
        $user->forceFill(['name' => trim(preg_replace('/\s+/', ' ', $data['name'])), 'phone' => ($data['phone'] ?? null) ? trim($data['phone']) : null]);
        $changes = collect($user->getDirty())->map(fn ($new, $field) => ['dari' => $user->getOriginal($field), 'ke' => $new])->all();
        $user->save();

        if ($changes) {
            Activity::log('akun.profil', "{$user->name} memperbarui profil.", $user, null, $changes);
        }

        return $this->back('profil', $changes ? __('Profil tersimpan.') : __('Tidak ada perubahan.'));
    }

    /**
     * Foto profil. Gambar dari browser sudah dipotong & difilter, tetapi server tidak mempercayainya:
     * dibaca ulang oleh GD dan disimpan sebagai WebP 512×512 baru (metadata EXIF/GPS dan isi selipan ikut terbuang).
     */
    public function uploadPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=64,min_height=64,max_width=4096,max_height=4096'],
        ], [
            'photo.required' => __('Pilih foto terlebih dahulu.'),
            'photo.image' => __('File harus berupa gambar.'),
            'photo.mimes' => __('Format foto harus JPG, PNG, atau WebP.'),
            'photo.max' => __('Ukuran foto maksimal 5 MB.'),
            // Batas atas menjaga memori server: GD butuh ±4 byte/piksel saat membaca gambar (4096² ≈ 64 MB).
            'photo.dimensions' => __('Foto harus berukuran 64×64 sampai 4096×4096 piksel.'),
        ]);

        $source = @imagecreatefromstring((string) file_get_contents($request->file('photo')->getRealPath()));
        if (! $source) {
            throw ValidationException::withMessages(['photo' => __('Foto tidak bisa dibaca. Coba simpan ulang dalam format JPG atau PNG.')]);
        }

        // Potong tengah menjadi persegi (jaga-jaga bila yang dikirim tidak persegi), lalu perkecil.
        $w = imagesx($source);
        $h = imagesy($source);
        $side = min($w, $h);
        $canvas = imagecreatetruecolor(self::PHOTO_SIZE, self::PHOTO_SIZE);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 255, 255, 255, 127));
        imagecopyresampled($canvas, $source, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), self::PHOTO_SIZE, self::PHOTO_SIZE, $side, $side);

        ob_start();
        imagewebp($canvas, null, 86);
        $bytes = ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        $user = $request->user();
        $path = 'avatars/' . $user->id . '-' . Str::random(20) . '.webp';
        Storage::disk(self::DISK)->put($path, $bytes);

        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => $path, 'avatar_preset' => null])->save();
        if ($old) {
            Storage::disk(self::DISK)->delete($old);
        }

        Activity::log('akun.foto', "{$user->name} mengganti foto profil.", $user);

        return $this->back('profil', __('Foto profil diperbarui.'));
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->avatar_path) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
            Activity::log('akun.foto', "{$user->name} menghapus foto profil.", $user);
        }

        return $this->back('profil', __('Foto profil dihapus.'));
    }

    /** Pilih avatar siap pakai (menggantikan foto) atau kembali ke inisial (preset kosong). */
    public function setPreset(Request $request): RedirectResponse
    {
        $data = $request->validate(['preset' => ['nullable', 'string', Rule::in(array_keys(Avatars::PRESETS))]]);
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
        }
        $user->forceFill(['avatar_path' => null, 'avatar_preset' => $data['preset'] ?? null])->save();

        return $this->back('profil', isset($data['preset']) ? __('Avatar diganti.') : __('Avatar kembali ke inisial nama.'));
    }

    /** Foto profil: hanya untuk pengguna yang sudah masuk; nama file acak di URL → boleh di-cache lama. */
    public function avatar(User $user, string $v): Response
    {
        $path = $user->avatar_path;
        abort_unless($path && pathinfo($path, PATHINFO_FILENAME) === $v && Storage::disk(self::DISK)->exists($path), 404);

        return response(Storage::disk(self::DISK)->get($path), 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Preferensi tampilan (tema, ukuran teks) disimpan di akun → berlaku di semua perangkat & setiap login.
     * Dipanggil di latar (fetch) saat pengguna memilih; tetap bisa dipakai sebagai form biasa.
     */
    public function updatePreferences(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['sometimes', 'nullable', Rule::in(['light', 'dark', 'system'])],
            'text_size' => ['sometimes', 'nullable', Rule::in(['sm', 'md', 'lg'])],
        ]);

        $request->user()->forceFill($data)->save();

        return $request->expectsJson()
            ? response()->json(['saved' => $data])
            : $this->back('tampilan', __('Tersimpan'));
    }

    /** Keluarkan satu sesi lain milik sendiri. */
    public function destroySession(Request $request, string $id): RedirectResponse
    {
        abort_if($id === $request->session()->getId(), 422, __('Sesi ini sedang dipakai. Gunakan tombol Keluar.'));
        $deleted = DB::table('sessions')->where('user_id', $request->user()->id)->where('id', $id)->delete();
        abort_unless($deleted, 404);

        Activity::log('akun.sesi', "{$request->user()->name} mengeluarkan satu sesi login di perangkat lain.", $request->user());

        return $this->back('keamanan', __('Sesi di perangkat itu sudah dikeluarkan.'));
    }

    /** Keluarkan semua sesi lain + matikan cookie "Ingat saya" di perangkat lain. Wajib password. */
    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        $request->validateWithBag('sessions', ['password' => ['required', 'current_password']], ['password.current_password' => __('Password salah.')], ['password' => 'password']);

        $user = $request->user();
        $remembered = $request->hasCookie(Auth::getRecallerName());
        $count = DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        Auth::login($user, $remembered);
        $request->session()->regenerate();

        Activity::log('akun.sesi', "{$user->name} mengeluarkan {$count} sesi login di perangkat lain.", $user);

        return $this->back('keamanan', $count ? __(':n sesi di perangkat lain dikeluarkan.', ['n' => $count]) : __('Tidak ada sesi lain yang aktif.'));
    }

    /** @return list<array{id: string, current: bool, ip: ?string, browser: string, os: string, mobile: bool, last: \Illuminate\Support\Carbon}> */
    private function sessions(Request $request): array
    {
        // Hanya sesi yang belum kedaluwarsa (baris lama baru dibersihkan pengumpul sampah sesi sewaktu-waktu).
        return DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
            ->orderByDesc('last_activity')
            ->limit(50)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'current' => $s->id === $request->session()->getId(),
                'ip' => $s->ip_address,
                'last' => \Illuminate\Support\Carbon::createFromTimestamp($s->last_activity, config('app.timezone')),
            ] + self::agent((string) $s->user_agent))
            ->all();
    }

    /** Ringkasan user agent sederhana (tanpa pustaka): nama browser, sistem operasi, ponsel atau bukan. */
    public static function agent(string $ua): array
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Browser lain',
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Perangkat tidak dikenal',
        };

        return ['browser' => $browser, 'os' => $os, 'mobile' => (bool) preg_match('/Mobile|Android|iPhone/', $ua)];
    }

    private function back(string $tab, string $message): RedirectResponse
    {
        return redirect()->route('account.settings', ['tab' => $tab])->with('success', $message);
    }
}
