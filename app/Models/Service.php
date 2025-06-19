<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'capacity',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'capacity' => 'integer',
        'active' => 'boolean',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getHourlyRate(): float
    {
        if ($this->duration <= 0) {
            return 0;
        }

        return ($this->price * 60) / $this->duration;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}