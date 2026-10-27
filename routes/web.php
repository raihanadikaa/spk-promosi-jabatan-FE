<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Direktur\DirekturDashboardController;
use App\Http\Controllers\Hrd\HrdDashboardController;
use App\Http\Controllers\Karyawan\KaryawanDashboardController;
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
// Tambahkan GET route untuk berjaga-jaga jika user terpaksa logout lewat URL
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])
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
| Dashboard per Role
| Idle timeout ditangani di masing-masing DashboardController
| via trait EnforcesIdleTimeout (NFR-SEC-05)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
});

Route::middleware(['auth', 'role:hrd'])->group(function () {
    Route::get('/hrd/dashboard', [HrdDashboardController::class, 'index'])
        ->name('hrd.dashboard');
});

Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/manager/dashboard', [ManagerDashboardController::class, 'index'])
        ->name('manager.dashboard');
});

Route::middleware(['auth', 'role:direktur'])->group(function () {
    Route::get('/direktur/dashboard', [DirekturDashboardController::class, 'index'])
        ->name('direktur.dashboard');
});

Route::middleware(['auth', 'role:karyawan'])->group(function () {
    Route::get('/karyawan/dashboard', [KaryawanDashboardController::class, 'index'])
        ->name('karyawan.dashboard');
});