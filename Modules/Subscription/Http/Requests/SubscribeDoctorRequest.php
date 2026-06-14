<?php

namespace Modules\Subscription\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class SubscribeDoctorRequest extends ApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'subscription_id' => 'required|uuid|exists:subscriptions,id',
            'amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:255',
            'auto_renew' => 'boolean',
        ];
    }
}
