<?php

namespace Modules\Admin\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AdminCsrfController extends Controller
{
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }
}
