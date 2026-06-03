<?php

namespace Modules\Doctor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorBranch extends Model
{
    use HasFactory;

    protected $table = 'doctor_branches';

    protected $fillable = [
        'doctor_id',
        'branch_name',
        'governorate',
        'district',
        'address',
        'latitude',
        'longitude',
        'phone',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:10',
        'longitude' => 'decimal:10',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_branch_id');
    }
}
