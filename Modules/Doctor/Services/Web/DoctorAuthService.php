<?php

namespace Modules\Doctor\Services\Web;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Doctor;

class DoctorAuthService
{
    public function register(array $data, ?UploadedFile $licenseDocument = null, ?UploadedFile $clinicImage = null): User
    {
        return DB::transaction(function () use ($data, $licenseDocument, $clinicImage) {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'doctor',
                'status' => 'active',
            ]);

            $doctorData = [
                'user_id' => $user->id,
                'speciality_id' => $data['speciality_id'],
                'bio_ar' => $data['bio_ar'] ?? null,
                'experience_years' => $data['experience_years'] ?? 0,
                'status' => 'pending',
            ];

            if ($licenseDocument) {
                $doctorData['license_document'] = $licenseDocument->store('doctors/licenses', 'public');
            }

            if ($clinicImage) {
                $doctorData['clinic_image'] = $clinicImage->store('doctors/clinic', 'public');
            }

            Doctor::create($doctorData);

            return $user;
        });
    }

    public function login(string $phone, string $password): ?User
    {
        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->isDoctor() || !$user->isActive()) {
            return null;
        }

        return $user;
    }
}
