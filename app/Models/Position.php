<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = ['position_name', 'level'];

    protected function casts(): array
    {
        return ['level' => 'integer'];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
