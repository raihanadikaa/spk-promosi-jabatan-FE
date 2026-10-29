<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Queries\PromotionData;
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

        return to_route('admin.users')
            ->with('success', "Akun {$user->name} dibuat.")
            ->with('temporary_password', ['name' => $user->name, 'email' => $user->email, 'password' => $temporary]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->is($request->user()) && (int) $data['role_id'] !== $user->role_id) {
            throw ValidationException::withMessages(['role_id' => 'Anda tidak bisa mengubah role akun sendiri.']);
        }

        $user->forceFill($data)->save();

        return to_route('admin.users')->with('success', "Akun {$user->name} diperbarui.");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'Anda tidak bisa menonaktifkan akun sendiri.']);
        }

        DB::transaction(function () use ($user) {
            $user->forceFill(['is_active' => ! $user->is_active])->save();

            if (! $user->is_active) {
                // Putus semua sesi yang masih hidup.
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->forceFill(['remember_token' => Str::random(60)])->save();
            }
        });

        return to_route('admin.users')->with('success', $user->is_active ? "{$user->name} diaktifkan." : "{$user->name} dinonaktifkan.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'Ganti password akun sendiri lewat menu Ganti password.']);
        }

        $temporary = $this->temporaryPassword();
        $user->forceFill(['password' => $temporary, 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return to_route('admin.users')
            ->with('success', "Password {$user->name} direset.")
            ->with('temporary_password', ['name' => $user->name, 'email' => $user->email, 'password' => $temporary]);
    }

    private function temporaryPassword(): string
    {
        return Str::password(14, symbols: false);
    }
}
