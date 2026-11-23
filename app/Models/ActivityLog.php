<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /** Kategori untuk filter di halaman log. */
    public const CATEGORIES = [
        'periode' => 'Periode',
        'kriteria' => 'Kriteria & bobot',
        'karyawan' => 'Karyawan & organisasi',
        'penilaian' => 'Penilaian atasan',
        'saw' => 'Perhitungan SAW',
        'keputusan' => 'Keputusan direktur',
        'akun' => 'Akun & login',
    ];

    protected $fillable = [
        'user_id', 'user_name', 'category', 'action', 'description',
        'subject_type', 'subject_id', 'period_id', 'properties', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class, 'period_id');
    }
}
