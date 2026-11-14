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
        $request->merge([
            'desde' => $request->input('desde') ?: now()->startOfMonth()->toDateString(),
            'hasta' => $request->input('hasta') ?: now()->toDateString(),
        ]);
        $dates = $request->validate(['desde' => ['required', 'date_format:Y-m-d'], 'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde', 'before_or_equal:today']]);

        return view('dashboard.index', ['summary' => $dashboard->summary($dates['desde'] ?? null, $dates['hasta'] ?? null)]);
    }
}
