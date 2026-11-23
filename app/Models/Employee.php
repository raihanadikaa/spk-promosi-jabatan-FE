<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'nik', 'department_id', 'position_id', 'supervisor_id', 'user_id', 'join_date', 'is_active'];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** Atasan langsung yang berhak menilai karyawan ini. */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** Akun portal karyawan (opsional). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function rankingResults(): HasMany
    {
        return $this->hasMany(RankingResult::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Karyawan yang tidak bisa dinilai siapa pun: belum punya atasan, atau atasannya sudah
     * dinonaktifkan / tidak lagi ber-role atasan (mis. diubah admin).
     */
    public function scopeWithoutValidSupervisor(Builder $query): void
    {
        $query->where(fn ($q) => $q->whereNull('supervisor_id')
            ->orWhereDoesntHave('supervisor', fn ($u) => $u->where('is_active', true)
                ->whereHas('role', fn ($r) => $r->where('role_name', 'manager'))));
    }

    /** Masa kerja dalam tahun (1 desimal) sampai tanggal tertentu, mis. akhir periode. */
    public function tenureAt(CarbonInterface $date): float
    {
        return max(0, round($this->join_date->diffInMonths($date) / 12, 1));
    }
}
