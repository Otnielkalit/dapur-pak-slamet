<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workplace extends Model
{
    protected $fillable = [
        'name',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function mealEntries(): HasMany
    {
        return $this->hasMany(MealEntry::class, 'workplace_id');
    }
}

