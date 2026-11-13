<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardQuery $dashboard): View
    {
        $dates = $request->validate(['desde' => ['nullable', 'date_format:Y-m-d'], 'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde']]);

        return view('dashboard.index', ['summary' => $dashboard->summary($dates['desde'] ?? null, $dates['hasta'] ?? null)]);
    }
}
