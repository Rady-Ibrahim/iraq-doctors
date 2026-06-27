<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'avatar',
        'birthdate',
        'gender',
        'city',
        'district',
        'address',
        'phone',
        'email',
        'password',
        'role',
        'status',
        'is_ghost',
        'created_by_doctor_id',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthdate' => 'date',
        'password' => 'hashed',
    ];

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function doctor()
    {
        return $this->hasOne(\Modules\Doctor\Models\Doctor::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(\App\Models\DeviceToken::class);
    }

    public function appointments()
    {
        return $this->hasMany(\Modules\Appointment\Models\Appointment::class, 'patient_id');
    }
}
