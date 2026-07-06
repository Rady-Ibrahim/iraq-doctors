<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorStaffMember;
use Modules\Doctor\Support\DoctorStaffPermissions;

class DoctorDashboardContext
{
    public function __construct(
        public readonly Doctor $doctor,
        public readonly User $user,
        public readonly ?DoctorStaffMember $staffMember = null,
    ) {}

    public static function resolve(): self
    {
        if (app()->bound(self::class)) {
            return app(self::class);
        }

        return self::make();
    }

    public static function make(): self
    {
        $user = auth('web')->user();

        if (! $user) {
            throw new AuthorizationException('يجب تسجيل الدخول أولاً');
        }

        if ($user->isDoctor()) {
            $doctor = Doctor::with('user')->where('user_id', $user->id)->firstOrFail();

            return new self($doctor, $user);
        }

        if ($user->isDoctorStaff()) {
            $staffMember = DoctorStaffMember::with(['doctor.user'])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->firstOrFail();

            return new self($staffMember->doctor, $user, $staffMember);
        }

        throw new AuthorizationException('غير مصرح لك بالوصول إلى لوحة الطبيب');
    }

    public function isOwner(): bool
    {
        return $this->staffMember === null;
    }

    public function isStaff(): bool
    {
        return $this->staffMember !== null;
    }

    public function permissions(): array
    {
        if ($this->isOwner()) {
            return array_keys(DoctorStaffPermissions::ALL);
        }

        return $this->staffMember?->permissions ?? [];
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->staffMember?->hasPermission($permission) ?? false;
    }

    public function doctorUserId(): int
    {
        return (int) $this->doctor->user_id;
    }
}
