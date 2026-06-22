<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorSubscription extends Model
{
    use HasFactory;

    protected $table = 'doctor_subscriptions';

    protected $fillable = [
        'doctor_id',
        'subscription_id',
        'start_date',
        'end_date',
        'status',
        'amount_paid',
        'payment_method',
        'transaction_id',
        'payment_receipt',
        'submitted_amount',
        'payment_reject_reason',
        'reviewed_by',
        'reviewed_at',
        'expiry_reminder_sent_at',
        'auto_renew',
        'cancelled_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount_paid' => 'decimal:2',
        'auto_renew' => 'boolean',
        'cancelled_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expiry_reminder_sent_at' => 'datetime',
        'submitted_amount' => 'decimal:2',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function doctor()
    {
        return $this->belongsTo(\Modules\Doctor\Models\Doctor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('end_date', '<', now());
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->end_date < now();
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date));
    }
}
