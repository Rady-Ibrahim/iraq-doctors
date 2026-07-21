<?php

namespace Modules\Pharmacy\Services\Web;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Pharmacy\Models\Pharmacy;

class PharmacyProfileService
{
    public function getProfile(Pharmacy $pharmacy): array
    {
        $pharmacy->loadMissing(['governorate', 'user']);

        return [
            'id' => $pharmacy->id,
            'name' => $pharmacy->name,
            'description_ar' => $pharmacy->description_ar,
            'governorate_id' => $pharmacy->governorate_id,
            'governorate_name' => $pharmacy->governorate?->name_ar,
            'district' => $pharmacy->district,
            'address' => $pharmacy->address,
            'latitude' => $pharmacy->latitude,
            'longitude' => $pharmacy->longitude,
            'contact_phone' => $pharmacy->contact_phone,
            'whatsapp' => $pharmacy->whatsapp,
            'working_hours' => $pharmacy->working_hours ?? $this->defaultWorkingHours(),
            'delivery_enabled' => $pharmacy->delivery_enabled,
            'delivery_fee' => $pharmacy->delivery_fee,
            'min_order_for_delivery' => $pharmacy->min_order_for_delivery,
            'logo' => storage_public_url($pharmacy->logo),
            'user_name' => $pharmacy->user?->name,
            'user_email' => $pharmacy->user?->email,
            'user_phone' => $pharmacy->user?->phone,
        ];
    }

    public function updateProfile(Pharmacy $pharmacy, array $data, ?UploadedFile $logo = null): Pharmacy
    {
        return DB::transaction(function () use ($pharmacy, $data, $logo) {
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
                'delivery_enabled',
                'delivery_fee',
                'min_order_for_delivery',
            ])->filter(fn ($v) => $v !== null)->all();

            if ($logo) {
                $payload['logo'] = $logo->store('pharmacies/logos', 'public');
            }

            $pharmacy->update($payload);

            $primaryBranch = $pharmacy->branches()->where('is_primary', true)->first();
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

            if (! empty($data['user_name']) || array_key_exists('user_email', $data)) {
                $userPayload = [];
                if (! empty($data['user_name'])) {
                    $userPayload['name'] = $data['user_name'];
                }
                if (array_key_exists('user_email', $data)) {
                    $userPayload['email'] = $data['user_email'] ?: null;
                }
                if ($userPayload !== []) {
                    $pharmacy->user?->update($userPayload);
                }
            }

            return $pharmacy->fresh(['governorate', 'user']);
        });
    }

    public function defaultWorkingHours(): array
    {
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $hours = [];
        foreach ($days as $day) {
            $hours[$day] = ['enabled' => $day !== 'friday', 'open' => '08:00', 'close' => '22:00'];
        }

        return $hours;
    }
}
