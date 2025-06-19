<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'name',
        'type',
        'price',
        'start_date',
        'end_date',
        'auto_renewal',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_renewal' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date >= now();
    }

    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getRemainingDays(): int
    {
        if ($this->end_date < now()) {
            return 0;
        }
        
        return now()->diffInDays($this->end_date);
    }
}