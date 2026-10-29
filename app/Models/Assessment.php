<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';

    protected $fillable = ['employee_id', 'period_id', 'assessor_id', 'status', 'notes', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class, 'period_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AssessmentDetail::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::SUBMITTED;
    }
}
