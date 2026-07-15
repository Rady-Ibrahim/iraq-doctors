<?php

namespace Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    protected $table = 'medicines';

    protected $fillable = [
        'medicine_category_id',
        'name_ar',
        'name_en',
        'generic_name',
        'barcode',
        'dosage_form',
        'strength',
        'manufacturer',
        'description_ar',
        'sort_order',
        'is_active',
        'created_by_pharmacy_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MedicineCategory::class, 'medicine_category_id');
    }

    public function pharmacyItems(): HasMany
    {
        return $this->hasMany(PharmacyMedicine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
