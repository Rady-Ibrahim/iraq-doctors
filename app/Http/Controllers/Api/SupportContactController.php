<?php

namespace App\Http\Controllers\Api;

use App\Models\SupportContact;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SupportContactController extends Controller
{
    use ApiResponse;

    /**
     * Public endpoint for mobile apps — list active support contacts.
     */
    public function index(): JsonResponse
    {
        $items = SupportContact::active()
            ->ordered()
            ->get()
            ->map(fn (SupportContact $item) => [
                'id' => $item->id,
                'service_name' => $item->service_name,
                'whatsapp_phone' => $item->whatsapp_phone,
                'call_phone' => $item->call_phone,
                'sort_order' => $item->sort_order,
            ]);

        return $this->success($items);
    }
}
