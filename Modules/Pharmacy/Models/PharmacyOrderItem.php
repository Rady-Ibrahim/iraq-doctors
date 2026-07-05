<?php

namespace Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyOrderItem extends Model
{
    protected $table = 'pharmacy_order_items';

    protected $fillable = [
        'pharmacy_order_id',
        'pharmacy_medicine_id',
        'medicine_id',
        'medicine_name',
        'price',
        'quantity',
        'source',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PharmacyOrder::class, 'pharmacy_order_id');
    }

    public function pharmacyMedicine(): BelongsTo
    {
        return $this->belongsTo(PharmacyMedicine::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function lineTotal(): float
    {
        return (float) $this->price * (int) $this->quantity;
    }
}
