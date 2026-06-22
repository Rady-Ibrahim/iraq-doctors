<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Otp extends Model
{
    use HasFactory;

    protected $table = 'otps';

    protected $fillable = [
        'phone',
        'email',
        'code',
        'type',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public function isMaxAttemptsExceeded(): bool
    {
        return $this->attempts >= 3;
    }

    /** Identifier used for lookup — email takes priority over phone */
    public function getIdentifier(): string
    {
        return $this->email ?? $this->phone;
    }
}
