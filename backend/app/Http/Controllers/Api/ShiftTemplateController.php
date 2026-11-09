<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShiftTemplate;
use Illuminate\Http\JsonResponse;

class ShiftTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = ShiftTemplate::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $templates]);
    }
}
