<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

abstract class Controller extends \App\Http\Controllers\Controller
{
    /** ?per_page=, default 30, at most 100. */
    protected function perPage(Request $request): int
    {
        return max(1, min(100, $request->integer('per_page', 30)));
    }
}
