<?php

namespace Modules\Doctor\Services;

use App\Support\PhoneNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorStaffMember;
use Modules\Doctor\Support\DoctorStaffPermissions;

class DoctorStaffService
{
    public function listForDoctor(int $doctorId): Collection
    {
        return DoctorStaffMember::with('user')
            ->where('doctor_id', $doctorId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DoctorStaffMember $member) => $this->formatMember($member));
    }

    public function createStaff(Doctor $doctor, array $data): DoctorStaffMember
    {
        return DB::transaction(function () use ($doctor, $data) {
            $phone = PhoneNormalizer::toE164($data['phone']);
            $permissions = DoctorStaffPermissions::sanitize(
                $data['permissions'] ?? DoctorStaffPermissions::DEFAULT
            );

            if ($permissions === []) {
                $permissions = DoctorStaffPermissions::DEFAULT;
            }

            $user = User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'doctor_staff',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);

            $member = DoctorStaffMember::create([
                'doctor_id' => $doctor->id,
                'user_id' => $user->id,
                'permissions' => $permissions,
                'status' => 'active',
            ]);

            return $member->load('user');
        });
    }

    public function updateStaff(Doctor $doctor, int $memberId, array $data): DoctorStaffMember
    {
        $member = $this->findMemberForDoctor($doctor->id, $memberId);

        return DB::transaction(function () use ($member, $data) {
            $userUpdates = [];

            if (isset($data['name'])) {
                $userUpdates['name'] = $data['name'];
            }

            if (isset($data['phone'])) {
                $userUpdates['phone'] = PhoneNormalizer::toE164($data['phone']);
            }

            if (array_key_exists('email', $data)) {
                $userUpdates['email'] = $data['email'];
            }

            if (! empty($data['password'])) {
                $userUpdates['password'] = Hash::make($data['password']);
            }

            if ($userUpdates !== []) {
                $member->user->update($userUpdates);
            }

            if (isset($data['permissions'])) {
                $permissions = DoctorStaffPermissions::sanitize($data['permissions']);

                if ($permissions === []) {
                    $permissions = DoctorStaffPermissions::DEFAULT;
                }

                $member->update(['permissions' => $permissions]);
            }

            return $member->fresh(['user']);
        });
    }

    public function updateStatus(Doctor $doctor, int $memberId, string $status): DoctorStaffMember
    {
        $member = $this->findMemberForDoctor($doctor->id, $memberId);
        $member->update(['status' => $status]);

        $member->user->update([
            'status' => $status === 'active' ? 'active' : 'inactive',
        ]);

        return $member->fresh(['user']);
    }

    public function deleteStaff(Doctor $doctor, int $memberId): void
    {
        $member = $this->findMemberForDoctor($doctor->id, $memberId);

        DB::transaction(function () use ($member) {
            $member->user->delete();
            $member->delete();
        });
    }

    public function permissionCatalog(): array
    {
        return DoctorStaffPermissions::labels();
    }

    private function findMemberForDoctor(int $doctorId, int $memberId): DoctorStaffMember
    {
        return DoctorStaffMember::with('user')
            ->where('doctor_id', $doctorId)
            ->where('id', $memberId)
            ->firstOrFail();
    }

    private function formatMember(DoctorStaffMember $member): array
    {
        return [
            'id' => $member->id,
            'status' => $member->status,
            'permissions' => $member->permissions ?? [],
            'created_at' => $member->created_at?->format('Y-m-d H:i'),
            'user' => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'phone' => $member->user->phone,
                'email' => $member->user->email,
                'status' => $member->user->status,
            ],
        ];
    }
}
