<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentPeriod extends Model
{
    public const DRAFT = 'draft';
    public const ONGOING = 'ongoing';
    public const CLOSED = 'closed';

    protected $fillable = ['period_name', 'start_date', 'end_date', 'assessment_deadline', 'status', 'calculated_at'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'assessment_deadline' => 'date',
            'calculated_at' => 'datetime',
        ];
    }

    public function weights(): HasMany
    {
        return $this->hasMany(CriteriaWeight::class, 'period_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'period_id');
    }

    public function rankingResults(): HasMany
    {
        return $this->hasMany(RankingResult::class, 'period_id');
    }

    public function isOngoing(): bool
    {
        return $this->status === self::ONGOING;
    }

    public function scopeOngoing(Builder $query): void
    {
        $query->where('status', self::ONGOING);
    }

    /**
     * Periode yang sedang dikerjakan: yang berlangsung; jika tidak ada, yang terakhir ditutup;
     * jika belum ada keduanya, draft terbaru. Null hanya jika tabel kosong.
     */
    public static function current(): ?self
    {
        return static::query()
            ->orderByRaw("case status when 'ongoing' then 0 when 'closed' then 1 else 2 end")
            ->orderByDesc('start_date')
            ->first();
    }

    /** Periode sebelumnya yang sudah berjalan (untuk perbandingan skor). */
    public function previous(): ?self
    {
        return static::query()
            ->where('start_date', '<', $this->start_date)
            ->where('status', '!=', self::DRAFT)
            ->orderByDesc('start_date')
            ->first();
    }
}
