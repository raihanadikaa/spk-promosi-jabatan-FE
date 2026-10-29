<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'criteria';

    protected $fillable = ['code', 'criteria_name', 'attribute_type', 'input_type', 'unit', 'min_value', 'max_value', 'description', 'sort_order'];

    protected function casts(): array
    {
        return [
            'min_value' => 'integer',
            'max_value' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function weights(): HasMany
    {
        return $this->hasMany(CriteriaWeight::class, 'criteria_id');
    }

    /** Masa kerja diisi otomatis dari tanggal bergabung, bukan oleh atasan. */
    public function isAuto(): bool
    {
        return $this->input_type === 'auto';
    }
}
