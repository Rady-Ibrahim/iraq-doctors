<?php

namespace Modules\Doctor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Auth\Models\User;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'doctors';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'speciality_id',
        'bio_ar',
        'bio_en',
        'experience_years',
        'consultation_fee',
        'consultation_type',
        'rating',
        'rating_count',
        'latitude',
        'longitude',
        'address',
        'status',
        'subscription_id',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'latitude' => 'decimal:10',
        'longitude' => 'decimal:10',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function branches()
    {
        return $this->hasMany(DoctorBranch::class);
    }

    public function primaryBranch()
    {
        return $this->hasOne(DoctorBranch::class)->where('is_primary', true);
    }

    public function appointments()
    {
        return $this->hasManyThrough(
            \Modules\Appointment\Models\Appointment::class,
            DoctorSchedule::class
        );
    }

    public function reviews()
    {
        return $this->hasMany(\Modules\Review\Models\Review::class);
    }

    public function subscription()
    {
        return $this->belongsTo(\Modules\Subscription\Models\Subscription::class);
    }

    public function doctorSubscriptions()
    {
        return $this->hasMany(\Modules\Subscription\Models\DoctorSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->doctorSubscriptions()->active()->with('subscription')->first();
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function updateRating(float $newRating): void
    {
        $totalRating = ($this->rating * $this->rating_count) + $newRating;
        $this->rating_count++;
        $this->rating = $totalRating / $this->rating_count;
        $this->save();
    }
}
