<?php

namespace Modules\Doctor\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class DoctorCsrfController extends Controller
{
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }
}
