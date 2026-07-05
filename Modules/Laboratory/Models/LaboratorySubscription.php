<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Subscription\Models\Subscription;

class LaboratorySubscription extends Model
{
    protected $table = 'laboratory_subscriptions';

    protected $fillable = [
        'laboratory_id',
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
        'submitted_amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'cancelled_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expiry_reminder_sent_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date));
    }
}
