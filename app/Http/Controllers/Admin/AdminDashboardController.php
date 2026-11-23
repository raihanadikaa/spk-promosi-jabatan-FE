<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesIdleTimeout;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\PromotionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use EnforcesIdleTimeout;

    /**
     * Tampilkan dashboard admin.
     * NFR-SEC-05: cek idle timeout 30 menit sebelum render.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->enforceIdleTimeout($request)) {
            return $redirect;
        }

        // Dihitung di database: jumlah akun bisa ribuan (setiap karyawan bisa punya akun portal).
        $activeByRole = User::query()
            ->where('users.is_active', true)
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->selectRaw('roles.role_name, count(*) as n')
            ->groupBy('roles.role_name')
            ->pluck('n', 'role_name');

        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'activeUsers' => (int) $activeByRole->sum(),
            'byRole' => $activeByRole->map(fn ($n) => (int) $n)->all(),
            'recent' => User::with('role:id,role_name')->whereNotNull('last_login_at')->orderByDesc('last_login_at')->limit(6)
                ->get(['id', 'name', 'role_id', 'last_login_at']),
            'roles' => PromotionData::roleLabels(),
            'recentActivity' => \App\Models\ActivityLog::orderByDesc('created_at')->orderByDesc('id')->limit(6)->get(['id', 'user_name', 'description', 'created_at']),
        ]);
    }
}
