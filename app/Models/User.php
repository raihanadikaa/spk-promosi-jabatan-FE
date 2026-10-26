<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'role_id', // Pastikan kolom ini ada di database Anda
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
        ];
    }

    /**
     * Relasi ke tabel Role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Method yang dipanggil oleh RoleMiddleware
     * Mengembalikan nama role user (misal: 'hrd', 'admin', dll)
     */
    public function roleName(): string
    {
        // Mengambil nama dari relasi role. 
        // Pastikan di tabel 'roles' Anda ada kolom 'name'.
        // Jika tidak ada role, kembalikan string kosong atau default.
        return $this->role ? $this->role->name : '';
    }

    /**
     * (Opsional) Method untuk redirect setelah login
     * Digunakan di routes/web.php Anda
     */
    public function dashboardUrl(): string
    {
        return match ($this->roleName()) {
            'admin' => route('admin.dashboard'),
            'hrd' => route('hrd.dashboard'),
            'manager' => route('manager.dashboard'),
            'direktur' => route('direktur.dashboard'),
            'karyawan' => route('karyawan.dashboard'),
            default => '/',
        };
    }
}