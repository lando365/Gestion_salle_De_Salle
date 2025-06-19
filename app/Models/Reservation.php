<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'service_id',
        'coach_id',
        'start_time',
        'end_time',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'reservation_equipment')
            ->withTimestamps();
    }

    public function getDurationInMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function isUpcoming(): bool
    {
        return $this->start_time > now() && $this->status === 'scheduled';
    }

    public function isPast(): bool
    {
        return $this->end_time < now();
    }

    public function isInProgress(): bool
    {
        return $this->start_time <= now() && $this->end_time >= now();
    }

    public function markAsCompleted(): self
    {
        $this->update(['status' => 'completed']);
        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->update(['status' => 'cancelled']);
        return $this;
    }

    public function markAsNoShow(): self
    {
        $this->update(['status' => 'no_show']);
        return $this;
    }
}