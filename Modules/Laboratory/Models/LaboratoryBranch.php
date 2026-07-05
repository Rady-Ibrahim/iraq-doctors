<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Doctor\Models\Governorate;

class LaboratoryBranch extends Model
{
    protected $table = 'laboratory_branches';

    protected $fillable = [
        'laboratory_id',
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

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
