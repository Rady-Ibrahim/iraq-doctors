<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryOrderItem extends Model
{
    protected $table = 'laboratory_order_items';

    protected $fillable = [
        'laboratory_order_id',
        'laboratory_test_item_id',
        'lab_test_id',
        'test_name',
        'price',
        'quantity',
        'result_hours',
        'source',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'result_hours' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaboratoryOrder::class, 'laboratory_order_id');
    }

    public function laboratoryTestItem(): BelongsTo
    {
        return $this->belongsTo(LaboratoryTestItem::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function lineTotal(): float
    {
        return (float) $this->price * (int) $this->quantity;
    }
}
