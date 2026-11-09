<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $employees = Employee::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('search_key', 'like', '%'.mb_strtolower($search).'%');
            })
            ->orderBy('sort_order')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json(['data' => $employees]);
    }
}
