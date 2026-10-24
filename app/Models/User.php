<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi: user milik satu role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Relasi: user bisa terkait dengan satu data karyawan.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /* =========================================================
     |  HELPER ROLE
     |  Gunakan role_id dari tabel roles:
     |  1=admin, 2=hrd, 3=manager, 4=direktur, 5=karyawan
     | ========================================================= */

    public function isAdmin(): bool
    {
        return $this->role_id === 1;
    }

    public function isHrd(): bool
    {
        return $this->role_id === 2;
    }

    public function isManager(): bool
    {
        return $this->role_id === 3;
    }

    public function isDirektur(): bool
    {
        return $this->role_id === 4;
    }

    public function isKaryawan(): bool
    {
        return $this->role_id === 5;
    }

    /**
     * Nama role dalam bentuk string (lowercase).
     */
    public function roleName(): string
    {
        return match ($this->role_id) {
            1 => 'admin',
            2 => 'hrd',
            3 => 'manager',
            4 => 'direktur',
            5 => 'karyawan',
            default => 'unknown',
        };
    }

    /**
     * URL dashboard sesuai role.
     */
    public function dashboardUrl(): string
    {
        return match ($this->role_id) {
            1 => route('admin.dashboard'),
            2 => route('hrd.dashboard'),
            3 => route('manager.dashboard'),
            4 => route('direktur.dashboard'),
            5 => route('karyawan.dashboard'),
            default => route('login'),
        };
    }
}