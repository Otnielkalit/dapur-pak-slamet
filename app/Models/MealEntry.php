<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealEntry extends Model
{
    protected $fillable = [
        // Foreign keys
        'customer_id',
        'workplace_id',
        // Snapshot for stable exports/history
        'customer_code',
        'customer_name',
        'customer_phone',
        'workplace_name',
        // Main payload
        'eaten_at',
        'price',
        'paid',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'eaten_at' => 'datetime',
            'paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class, 'workplace_id');
    }

    public function scopePaid($query)
    {
        return $query->where('paid', true);
    }

    public function togglePaid(bool $paid): void
    {
        $this->paid = $paid;
        $this->paid_at = $paid ? now() : null;
        $this->save();
    }
}

