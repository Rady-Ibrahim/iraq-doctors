<?php

namespace Modules\Doctor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Auth\Models\User;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctors';

    protected $fillable = [
        'user_id',
        'speciality_id',
        'bio_ar',
        'bio_en',
        'experience_years',
        'consultation_fee',
        'rating',
        'rating_count',
        'latitude',
        'longitude',
        'address',
        'status',
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
