<?php

namespace Modules\Review\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Doctor\Models\Doctor;
use Modules\Auth\Models\User;
use Modules\Appointment\Models\Appointment;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reviews';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'rating',
        'comment',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reject_reason',
        'is_flagged',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_flagged' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class);
    }
}
