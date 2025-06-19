<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'purchase_date',
        'purchase_price',
        'status',
        'last_maintenance_date',
        'next_maintenance_date',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'reservation_equipment')
            ->withTimestamps();
    }

    public function needsMaintenance(): bool
    {
        return $this->next_maintenance_date !== null && 
            $this->next_maintenance_date <= now();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function markAsInUse(): self
    {
        $this->update(['status' => 'in_use']);
        return $this;
    }

    public function markAsAvailable(): self
    {
        $this->update(['status' => 'available']);
        return $this;
    }

    public function markAsMaintenance(): self
    {
        $this->update(['status' => 'maintenance']);
        return $this;
    }
}