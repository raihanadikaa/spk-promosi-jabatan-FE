<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Penampung sementara untuk form UI selama backend belum tersambung.
 * Tidak menyimpan apa pun; hanya mengembalikan notifikasi sesuai aksi.
 * Pesan diambil dari daftar tetap (bukan dari input) dan aksi dibatasi per role.
 */
class DemoActionController extends Controller
{
    private const ACTIONS = [
        'assessment.draft' => ['manager', 'Draft penilaian disimpan.'],
        'assessment.submit' => ['manager', 'Penilaian dikirim ke HRD.'],
        'weights.save' => ['hrd', 'Bobot kriteria disimpan.'],
        'saw.run' => ['hrd', 'Ranking dihitung ulang dari penilaian terbaru.'],
        'period.create' => ['hrd', 'Periode penilaian dibuat.'],
        'period.close' => ['hrd', 'Periode penilaian ditutup.'],
        'employee.create' => ['hrd', 'Data karyawan ditambahkan.'],
        'promotion.approve' => ['direktur', 'Promosi disetujui.'],
        'promotion.reject' => ['direktur', 'Rekomendasi promosi ditolak.'],
        'user.create' => ['admin', 'Pengguna ditambahkan.'],
        'user.toggle' => ['admin', 'Status pengguna diperbarui.'],
        'user.reset' => ['admin', 'Link reset password dikirim ke email pengguna.'],
    ];

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:' . implode(',', array_keys(self::ACTIONS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        [$role, $message] = self::ACTIONS[$validated['action']];

        abort_unless($request->user()->roleName() === $role, 403, 'Akses ditolak. Anda tidak memiliki hak untuk aksi ini.');

        if ($validated['action'] === 'promotion.reject' && blank($validated['note'] ?? null)) {
            return back()->withErrors(['note' => 'Tulis alasan penolakan supaya HRD dan karyawan bisa menindaklanjuti.'])->withInput();
        }

        $target = $request->input('redirect');

        // Hanya izinkan redirect ke path internal (cegah open redirect).
        // Browser memperlakukan "/\host" sama dengan "//host", jadi keduanya ditolak.
        if (is_string($target) && preg_match('#^/(?![/\\\\])#', $target)) {
            return redirect($target)->with('success', $message . ' (mode demo, belum tersimpan)');
        }

        return back()->with('success', $message . ' (mode demo, belum tersimpan)');
    }
}
