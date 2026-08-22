<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;

#[Group('Dashboard — Admin')]
class DashboardController extends Controller
{
    public function index(DashboardStatsService $stats)
    {
        $this->authorize('dashboard.view');

        return response()->json(['data' => $stats->stats()]);
    }
}
