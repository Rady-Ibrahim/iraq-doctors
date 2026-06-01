<?php

namespace Modules\Auth\Services\Api;

use Modules\Auth\Models\User;
use Modules\Auth\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'patient',
            'status' => 'active',
        ]);

        return $user;
    }

    public function login(string $phone, string $password): ?User
    {
        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->isActive()) {
            return null;
        }

        return $user;
    }

    public function sendOtp(string $phone, string $type = 'login'): Otp
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::where('phone', $phone)->where('type', $type)->delete();

        $otp = Otp::create([
            'phone' => $phone,
            'code' => $code,
            'type' => $type,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    public function verifyOtp(string $phone, string $code, string $type = 'login'): ?Otp
    {
        $otp = Otp::where('phone', $phone)
            ->where('type', $type)
            ->latest()
            ->first();

        if (!$otp) {
            return null;
        }

        if ($otp->isExpired()) {
            return null;
        }

        if ($otp->isMaxAttemptsExceeded()) {
            return null;
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            return null;
        }

        return $otp;
    }

    public function loginWithOtp(string $phone, string $code): ?User
    {
        $otp = $this->verifyOtp($phone, $code, 'login');

        if (!$otp) {
            return null;
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return null;
        }

        $otp->delete();

        return $user;
    }

    public function resetPassword(string $phone, string $code, string $newPassword): ?User
    {
        $otp = $this->verifyOtp($phone, $code, 'reset_password');

        if (!$otp) {
            return null;
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return null;
        }

        $user->update(['password' => Hash::make($newPassword)]);

        $otp->delete();

        return $user;
    }

    public function createToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
