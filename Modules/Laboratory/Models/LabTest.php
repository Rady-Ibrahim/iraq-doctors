<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTest extends Model
{
    protected $table = 'lab_tests';

    protected $fillable = [
        'lab_test_category_id',
        'name_ar',
        'name_en',
        'code',
        'description_ar',
        'sample_type',
        'sort_order',
        'is_active',
        'created_by_laboratory_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LabTestCategory::class, 'lab_test_category_id');
    }

    public function laboratoryItems(): HasMany
    {
        return $this->hasMany(LaboratoryTestItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
