<?php

namespace Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\User;
use Modules\MedicalRecord\Models\MedicalRecord;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;

class PharmacyOrder extends Model
{
    protected $table = 'pharmacy_orders';

    protected $fillable = [
        'order_number',
        'pharmacy_id',
        'patient_id',
        'pharmacy_branch_id',
        'prescription_id',
        'prescription_image',
        'fulfillment_type',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_notes',
        'status',
        'source',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'patient_notes',
        'quote_notes',
        'pharmacy_notes',
        'cancel_reason',
        'quoted_at',
        'out_for_delivery_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
        'quoted_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => PharmacyOrderStatus::class,
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(PharmacyBranch::class, 'pharmacy_branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyOrderItem::class);
    }

    public function prescriptionRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'prescription_id');
    }

    public function isDelivery(): bool
    {
        return $this->fulfillment_type === 'delivery';
    }

    public function hasPrescriptionImage(): bool
    {
        return ! empty($this->prescription_image);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [PharmacyOrderStatus::Completed, PharmacyOrderStatus::Cancelled], true);
    }
}
