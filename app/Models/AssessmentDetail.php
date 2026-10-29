<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssessmentDetail extends Model
{
    protected $fillable = ['assessment_id', 'criteria_id', 'raw_score'];

    protected function casts(): array
    {
        return ['raw_score' => 'float'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criteria_id');
    }

    public function normalized(): HasOne
    {
        return $this->hasOne(NormalizedScore::class);
    }
}
