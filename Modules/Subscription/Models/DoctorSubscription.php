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
        'auto_renew',
        'cancelled_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount_paid' => 'decimal:2',
        'auto_renew' => 'boolean',
        'cancelled_at' => 'datetime',
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

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date));
    }
}
