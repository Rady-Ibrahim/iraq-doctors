<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTestCategory extends Model
{
    protected $table = 'lab_test_categories';

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

    public function tests(): HasMany
    {
        return $this->hasMany(LabTest::class)->orderBy('sort_order');
    }

    public function activeTests(): HasMany
    {
        return $this->tests()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
