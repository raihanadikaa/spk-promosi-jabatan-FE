<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NormalizedScore extends Model
{
    protected $fillable = ['assessment_detail_id', 'normalized_value', 'weighted_value'];

    protected function casts(): array
    {
        return [
            'normalized_value' => 'float',
            'weighted_value' => 'float',
        ];
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(AssessmentDetail::class, 'assessment_detail_id');
    }
}
