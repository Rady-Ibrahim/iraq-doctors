<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class LaboratoryCsrfController extends Controller
{
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }
}
