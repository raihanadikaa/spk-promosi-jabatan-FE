<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemoActionController;
use App\Http\Controllers\Direktur\DirekturController;
use App\Http\Controllers\Direktur\DirekturDashboardController;
use App\Http\Controllers\Hrd\HrdController;
use App\Http\Controllers\Hrd\HrdDashboardController;
use App\Http\Controllers\Karyawan\KaryawanDashboardController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Manager\ManagerDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes (Belum Login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
// Hanya POST (dengan token CSRF): logout lewat GET bisa dipicu situs lain, mis. <img src=".../logout">
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Redirect root ke dashboard / login
| NFR-SEC-05: cek idle timeout 30 menit sebelum redirect ke dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', function (Request $request) {
    if (Auth::check()) {
        $user = Auth::user();

        // [PENGAMAN REDIRECT LOOP]
        // Jika user login tapi tidak punya role, tendang keluar
        if (empty($user->roleName())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda tidak memiliki Role yang valid. Hubungi Admin.']);
        }

        // NFR-SEC-05: Cek idle timeout 30 menit
        $last = (int) $request->session()->get('last_activity_at', 0);

        if ($last > 0 && (time() - $last) > 1800) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Sesi Anda telah berakhir karena tidak aktif selama 30 menit. Silakan login kembali.');
        }

        $request->session()->put('last_activity_at', time());

        // Redirect ke dashboard sesuai role
        return redirect()->to($user->dashboardUrl());
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Halaman per Role
| Hak akses mengikuti matriks Role & Hak Akses (dokumen ROLE).
| 'idle' = NFR-SEC-05 idle timeout 30 menit untuk semua halaman ber-login.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'idle', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/pengguna', [AdminController::class, 'users'])->name('users');
});

Route::middleware(['auth', 'idle', 'role:hrd'])->prefix('hrd')->name('hrd.')->group(function () {
    Route::get('/dashboard', [HrdDashboardController::class, 'index'])->name('dashboard');
    Route::get('/karyawan', [HrdController::class, 'employees'])->name('employees');
    Route::get('/periode', [HrdController::class, 'periods'])->name('periods');
    Route::get('/kriteria', [HrdController::class, 'criteria'])->name('criteria');
    Route::get('/proses-saw', [HrdController::class, 'saw'])->name('saw');
    Route::get('/ranking', [HrdController::class, 'ranking'])->name('ranking');
    Route::get('/ranking/{employee}', [HrdController::class, 'candidate'])->whereNumber('employee')->name('ranking.show');
});

Route::middleware(['auth', 'idle', 'role:manager'])->prefix('atasan')->name('manager.')->group(function () {
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/penilaian', [ManagerController::class, 'assessments'])->name('assessments');
    Route::get('/penilaian/{employee}', [ManagerController::class, 'assess'])->whereNumber('employee')->name('assessments.edit');
    Route::get('/ranking', [ManagerController::class, 'ranking'])->name('ranking');
});

Route::middleware(['auth', 'idle', 'role:direktur'])->prefix('direktur')->name('direktur.')->group(function () {
    Route::get('/dashboard', [DirekturDashboardController::class, 'index'])->name('dashboard');
    Route::get('/ranking', [DirekturController::class, 'ranking'])->name('ranking');
    Route::get('/ranking/{employee}', [DirekturController::class, 'candidate'])->whereNumber('employee')->name('ranking.show');
});

Route::middleware(['auth', 'idle', 'role:karyawan'])->prefix('karyawan')->name('karyawan.')->group(function () {
    Route::get('/dashboard', [KaryawanDashboardController::class, 'index'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Aksi form sementara (belum ada backend)
| Menerima POST dari form UI, lalu kembali dengan notifikasi. Tidak menyimpan apa pun.
| Ganti dengan route/controller asli saat backend siap.
|--------------------------------------------------------------------------
*/
Route::post('/demo/aksi', DemoActionController::class)
    ->middleware(['auth', 'idle', 'throttle:30,1'])
    ->name('demo.action');
