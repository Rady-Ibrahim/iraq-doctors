<?php

namespace Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Doctor\Models\Governorate;

class PharmacyBranch extends Model
{
    protected $table = 'pharmacy_branches';

    protected $fillable = [
        'pharmacy_id',
        'governorate_id',
        'branch_name',
        'district',
        'address',
        'latitude',
        'longitude',
        'phone',
        'is_primary',
        'is_active',
        'working_hours',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'working_hours' => 'array',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
