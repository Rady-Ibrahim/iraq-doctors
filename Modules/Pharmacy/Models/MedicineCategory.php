<?php

namespace Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineCategory extends Model
{
    protected $table = 'medicine_categories';

    protected $fillable = [
        'name_ar',
        'name_en',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function medicines(): HasMany
    {
        return $this->hasMany(Medicine::class)->orderBy('sort_order');
    }

    public function activeMedicines(): HasMany
    {
        return $this->medicines()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
