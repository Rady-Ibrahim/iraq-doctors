<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryTestItem extends Model
{
    protected $table = 'laboratory_test_items';

    protected $fillable = [
        'laboratory_id',
        'lab_test_id',
        'price',
        'result_hours',
        'is_available',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'result_hours' => 'integer',
        'is_available' => 'boolean',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
