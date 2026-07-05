<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PharmacyCsrfController extends Controller
{
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }
}
