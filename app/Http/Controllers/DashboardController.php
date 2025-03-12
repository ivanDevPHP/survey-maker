<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $this->dashboardService->getDashboardData($user);

        return response()->json($data);
    }

    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getLogs(Request $request): LengthAwarePaginator
    {
        $user = $request->user();

        return $this->dashboardService->getLogs($user);
    }
}
