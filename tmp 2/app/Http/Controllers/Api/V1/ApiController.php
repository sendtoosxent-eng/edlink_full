<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

abstract class ApiController extends Controller
{
    protected function ok(mixed $data, array $meta = [])
    {
        return response()->json(['data' => $data, 'meta' => (object) $meta]);
    }
}
