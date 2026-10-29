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

        return view('admin.dashboard', [
            'users' => User::with('role:id,role_name')->get(['id', 'name', 'email', 'role_id', 'is_active', 'last_login_at']),
            'roles' => PromotionData::roleLabels(),
        ]);
    }
}
