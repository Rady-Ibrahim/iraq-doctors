<?php

namespace Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Governorate;

class Pharmacy extends Model
{
    use SoftDeletes;

    protected $table = 'pharmacies';

    protected $fillable = [
        'user_id',
        'name',
        'governorate_id',
        'district',
        'address',
        'latitude',
        'longitude',
        'description_ar',
        'logo',
        'commercial_register_document',
        'license_document',
        'owner_id_document',
        'status',
        'reject_reason',
        'contact_phone',
        'whatsapp',
        'working_hours',
        'delivery_enabled',
        'delivery_fee',
        'min_order_for_delivery',
        'subscription_id',
        'rating',
        'rating_count',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'working_hours' => 'array',
        'delivery_enabled' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'min_order_for_delivery' => 'decimal:2',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function branches()
    {
        return $this->hasMany(PharmacyBranch::class);
    }

    public function pharmacySubscriptions()
    {
        return $this->hasMany(PharmacySubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(PharmacySubscription::class)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->latest();
    }

    public function pharmacyMedicines()
    {
        return $this->hasMany(PharmacyMedicine::class);
    }

    public function orders()
    {
        return $this->hasMany(PharmacyOrder::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function approvedReviews()
    {
        return $this->hasMany(\Modules\Review\Models\Review::class, 'pharmacy_id')
            ->where('status', \Modules\Review\Models\Review::STATUS_APPROVED);
    }

    public function recalculateRatingFromReviews(): void
    {
        $stats = $this->approvedReviews()
            ->selectRaw('COUNT(*) as count, AVG(rating) as average')
            ->first();

        $count = (int) ($stats->count ?? 0);
        $this->rating_count = $count;
        $this->rating = $count > 0 ? round((float) $stats->average, 2) : 0;
        $this->save();
    }
}
