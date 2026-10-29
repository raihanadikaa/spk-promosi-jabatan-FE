<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingResult extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'employee_id', 'period_id', 'final_score', 'rank', 'is_recommended',
        'approval_status', 'approved_by', 'approval_note', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'float',
            'rank' => 'integer',
            'is_recommended' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class, 'period_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isDecided(): bool
    {
        return in_array($this->approval_status, [self::APPROVED, self::REJECTED], true);
    }
}
