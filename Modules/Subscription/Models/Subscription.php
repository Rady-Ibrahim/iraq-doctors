<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';

    protected $fillable = [
        'name',
        'description_ar',
        'description_en',
        'price',
        'duration_days',
        'max_appointments',
        'is_featured',
        'has_analytics',
        'has_banner',
        'visibility_score',
        'features',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'has_analytics' => 'boolean',
        'has_banner' => 'boolean',
        'visibility_score' => 'integer',
        'features' => 'array',
    ];

    public function doctorSubscriptions()
    {
        return $this->hasMany(DoctorSubscription::class);
    }

    public function doctors()
    {
        return $this->hasManyThrough(
            \Modules\Doctor\Models\Doctor::class,
            DoctorSubscription::class,
            'subscription_id',
            'id',
            'id',
            'doctor_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrderByVisibility($query)
    {
        return $query->orderByDesc('visibility_score')->orderBy('sort_order');
    }
}
