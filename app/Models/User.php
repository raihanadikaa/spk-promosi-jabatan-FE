<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke tabel Role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Data karyawan milik akun ini (role karyawan). */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /** Bawahan langsung yang dinilai oleh akun ini (role manager/atasan). */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->roleName(), $roles, true);
    }

    /**
     * Method yang dipanggil oleh RoleMiddleware
     * Mengembalikan nama role user (misal: 'hrd', 'admin', dll)
     */
      public function roleName(): string
    {
        // PERBAIKAN: Gunakan 'role_name' bukan 'name'
        return $this->role?->role_name ?? '';
    }

    /**
     * Redirect URL berdasarkan role user
     */
    public function dashboardUrl(): string
    {
        return match ($this->roleName()) {
            'admin' => route('admin.dashboard'),
            'hrd' => route('hrd.dashboard'),
            'manager' => route('manager.dashboard'),
            'direktur' => route('direktur.dashboard'),
            'karyawan' => route('karyawan.dashboard'),
            // Fallback ke halaman login (aman dari redirect loop)
            default => route('login'), 
        };
    }
}