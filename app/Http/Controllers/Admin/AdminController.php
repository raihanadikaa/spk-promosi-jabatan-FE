<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Queries\PromotionData;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $v = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $like = '%' . addcslashes($v['q'] ?? '', '%_\\') . '%';

        $users = User::query()
            ->with('role:id,role_name')
            ->when(filled($v['q'] ?? null), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)))
            ->when($v['role'] ?? null, fn ($q, $role) => $q->where('role_id', $role))
            ->when($v['status'] ?? null, fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(PromotionData::PER_PAGE)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'filters' => $v,
            'roles' => Role::orderBy('id')->get(['id', 'role_name']),
            'roleLabels' => PromotionData::roleLabels(),
        ]);
    }

    /**
     * Admin tidak menentukan password: sistem membuat password sementara acak
     * yang ditampilkan sekali untuk diserahkan ke pengguna.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $temporary = $this->temporaryPassword();

        $user = User::forceCreate($request->validated() + ['password' => $temporary, 'is_active' => true]);
        Activity::log('akun.buat', "Membuat akun {$user->name} ({$user->email}) dengan role {$user->roleName()}.", $user);

        return to_route('admin.users')
            ->with('success', __('Akun :name dibuat.', ['name' => $user->name]))
            ->with('temporary_password', ['name' => $user->name, 'email' => $user->email, 'password' => $temporary]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->is($request->user()) && (int) $data['role_id'] !== $user->role_id) {
            throw ValidationException::withMessages(['role_id' => __('Anda tidak bisa mengubah role akun sendiri.')]);
        }

        $user->forceFill($data);
        $changed = array_keys(collect($user->getDirty())->except('updated_at')->all());
        $user->save();
        if ($changed) {
            Activity::log('akun.ubah', "Mengubah akun {$user->name}: " . implode(', ', $changed) . '.', $user);
        }

        return to_route('admin.users')->with('success', __('Akun :name diperbarui.', ['name' => $user->name]) . $this->orphanWarning($user));
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => __('Anda tidak bisa menonaktifkan akun sendiri.')]);
        }

        DB::transaction(function () use ($user) {
            $user->forceFill(['is_active' => ! $user->is_active])->save();

            if (! $user->is_active) {
                // Putus semua sesi yang masih hidup.
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->forceFill(['remember_token' => Str::random(60)])->save();
            }
        });

        Activity::log('akun.status', ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan') . " akun {$user->name}.", $user);

        return to_route('admin.users')->with('success', ($user->is_active ? __(':name diaktifkan.', ['name' => $user->name]) : __(':name dinonaktifkan.', ['name' => $user->name])) . $this->orphanWarning($user));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => __('Ganti password akun sendiri lewat menu Ganti password.')]);
        }

        $temporary = $this->temporaryPassword();
        $user->forceFill(['password' => $temporary, 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        Activity::log('akun.reset', "Mereset password akun {$user->name}.", $user);

        return to_route('admin.users')
            ->with('success', __('Password :name direset.', ['name' => $user->name]))
            ->with('temporary_password', ['name' => $user->name, 'email' => $user->email, 'password' => $temporary]);
    }

    /**
     * Hapus akun (hanya admin). Wajib password admin. Admin tidak bisa menghapus akunnya sendiri, dan sistem harus
     * tetap punya admin, HRD, dan direktur aktif. Penilaian/keputusan tetap tersimpan (kolom pelaku menjadi kosong,
     * nama tetap tercatat di log aktivitas).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $request->validateWithBag('deleteUser', ['password' => ['required', 'current_password']],
            ['password.current_password' => __('Password salah.')], ['password' => 'password']);

        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['password' => __('Anda tidak bisa menghapus akun sendiri.')])->errorBag('deleteUser');
        }
        if ($reason = self::deleteBlocker($user)) {
            throw ValidationException::withMessages(['password' => $reason])->errorBag('deleteUser');
        }

        $orphans = \App\Models\Employee::active()->where('supervisor_id', $user->id)->count();
        $name = $user->name;
        $email = $user->email;
        $userId = $user->id;

        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->notifications()->delete();
            $user->delete();
        });
        // Dicatat setelah penghapusan benar-benar berhasil (log tidak boleh mencatat kejadian yang batal).
        Activity::log('akun.hapus', "Menghapus akun {$name} ({$email}).", null, null, ['user_id' => $userId]);
        if ($user->avatar_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($user->avatar_path);
        }

        $warning = $orphans ? ' ' . __('Perhatian: :count karyawan aktif kini tanpa atasan penilai. Atur atasannya di halaman Karyawan.', ['count' => $orphans]) : '';

        return to_route('admin.users')->with('success', __('Akun :name dihapus.', ['name' => $name]) . $warning);
    }

    /** Alasan akun tidak boleh dihapus (null = boleh): sistem harus tetap punya admin, HRD, dan direktur aktif. */
    public static function deleteBlocker(User $user): ?string
    {
        $role = $user->roleName();
        if (! in_array($role, ['admin', 'hrd', 'direktur'], true) || ! $user->is_active) {
            return null;
        }
        $others = User::where('id', '!=', $user->id)->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('role_name', $role))->exists();

        return $others ? null : __(':name satu-satunya :role yang aktif. Tambahkan :role lain sebelum menghapus akun ini.', ['name' => $user->name, 'role' => __('role:' . $role)]);
    }

    /** Bawahan aktif yang kehilangan penilai karena akun ini nonaktif atau bukan atasan lagi. */
    private function orphanWarning(User $user): string
    {
        $user->unsetRelation('role');
        if ($user->is_active && $user->hasRole('manager')) {
            return '';
        }
        $count = \App\Models\Employee::active()->where('supervisor_id', $user->id)->count();

        return $count ? ' ' . __('Perhatian: :count karyawan aktif masih tercatat sebagai bawahannya dan tidak bisa dinilai sampai HRD mengganti atasan penilainya.', ['count' => $count]) : '';
    }

    private function temporaryPassword(): string
    {
        return Str::password(14, symbols: false);
    }
}
