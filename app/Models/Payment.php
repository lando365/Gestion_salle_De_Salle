<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'subscription_id',
        'invoice_number',
        'amount',
        'payment_date',
        'payment_method',
        'status',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        $lastPayment = self::latest()->first();
        
        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->invoice_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function markAsPaid(): self
    {
        $this->update(['status' => 'paid']);
        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->update(['status' => 'cancelled']);
        return $this;
    }

    public function markAsRefunded(): self
    {
        $this->update(['status' => 'refunded']);
        return $this;
    }
}