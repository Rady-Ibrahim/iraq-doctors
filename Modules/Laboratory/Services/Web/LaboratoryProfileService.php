<?php

namespace Modules\Laboratory\Services\Web;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Laboratory\Models\Laboratory;

class LaboratoryProfileService
{
    public function getProfile(Laboratory $laboratory): array
    {
        $laboratory->loadMissing(['governorate', 'user']);

        return [
            'id' => $laboratory->id,
            'name' => $laboratory->name,
            'description_ar' => $laboratory->description_ar,
            'governorate_id' => $laboratory->governorate_id,
            'governorate_name' => $laboratory->governorate?->name_ar,
            'district' => $laboratory->district,
            'address' => $laboratory->address,
            'latitude' => $laboratory->latitude,
            'longitude' => $laboratory->longitude,
            'contact_phone' => $laboratory->contact_phone,
            'whatsapp' => $laboratory->whatsapp,
            'working_hours' => $laboratory->working_hours ?? $this->defaultWorkingHours(),
            'home_collection_enabled' => $laboratory->home_collection_enabled,
            'home_collection_fee' => $laboratory->home_collection_fee,
            'logo' => storage_public_url($laboratory->logo),
            'user_name' => $laboratory->user?->name,
            'user_email' => $laboratory->user?->email,
            'user_phone' => $laboratory->user?->phone,
        ];
    }

    public function updateProfile(Laboratory $laboratory, array $data, ?UploadedFile $logo = null): Laboratory
    {
        return DB::transaction(function () use ($laboratory, $data, $logo) {
            $payload = collect($data)->only([
                'name',
                'description_ar',
                'governorate_id',
                'district',
                'address',
                'latitude',
                'longitude',
                'contact_phone',
                'whatsapp',
                'working_hours',
                'home_collection_enabled',
                'home_collection_fee',
            ])->filter(fn ($v) => $v !== null)->all();

            if ($logo) {
                $payload['logo'] = $logo->store('laboratories/logos', 'public');
            }

            $laboratory->update($payload);

            $primaryBranch = $laboratory->branches()->where('is_primary', true)->first();
            if ($primaryBranch) {
                $branchPayload = [];
                if (array_key_exists('address', $payload)) {
                    $branchPayload['address'] = $payload['address'];
                }
                if (array_key_exists('latitude', $payload)) {
                    $branchPayload['latitude'] = $payload['latitude'];
                }
                if (array_key_exists('longitude', $payload)) {
                    $branchPayload['longitude'] = $payload['longitude'];
                }
                if (array_key_exists('district', $payload)) {
                    $branchPayload['district'] = $payload['district'];
                }
                if (array_key_exists('governorate_id', $payload)) {
                    $branchPayload['governorate_id'] = $payload['governorate_id'];
                }
                if ($branchPayload !== []) {
                    $primaryBranch->update($branchPayload);
                }
            }

            if (!empty($data['user_name']) || !empty($data['user_email'])) {
                $userPayload = [];
                if (!empty($data['user_name'])) {
                    $userPayload['name'] = $data['user_name'];
                }
                if (array_key_exists('user_email', $data)) {
                    $userPayload['email'] = $data['user_email'] ?: null;
                }
                if ($userPayload !== []) {
                    $laboratory->user?->update($userPayload);
                }
            }

            return $laboratory->fresh(['governorate', 'user']);
        });
    }

    public function defaultWorkingHours(): array
    {
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $hours = [];
        foreach ($days as $day) {
            $hours[$day] = ['enabled' => $day !== 'friday', 'open' => '08:00', 'close' => '20:00'];
        }

        return $hours;
    }
}
