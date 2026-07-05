<?php

namespace Modules\Pharmacy\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class SearchPharmaciesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'name' => 'nullable|string|max:255',
            'delivery' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'governorate_id.exists' => 'المحافظة غير صالحة',
        ];
    }
}
