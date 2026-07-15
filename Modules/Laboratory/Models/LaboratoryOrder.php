<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\User;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\MedicalRecord\Models\MedicalRecord;

class LaboratoryOrder extends Model
{
    protected $table = 'laboratory_orders';

    protected $fillable = [
        'order_number',
        'laboratory_id',
        'patient_id',
        'laboratory_branch_id',
        'prescription_id',
        'prescription_image',
        'status',
        'source',
        'subtotal',
        'home_collection_fee',
        'total_amount',
        'patient_notes',
        'quote_notes',
        'lab_notes',
        'cancel_reason',
        'quoted_at',
        'scheduled_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'home_collection_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'quoted_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => LaboratoryOrderStatus::class,
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(LaboratoryBranch::class, 'laboratory_branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LaboratoryOrderItem::class);
    }

    public function prescriptionRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'prescription_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LaboratoryOrderResult::class);
    }

    public function hasPrescriptionImage(): bool
    {
        return ! empty($this->prescription_image);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [LaboratoryOrderStatus::Delivered, LaboratoryOrderStatus::Cancelled], true);
    }
}
