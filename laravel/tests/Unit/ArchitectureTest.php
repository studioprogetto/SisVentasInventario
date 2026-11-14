<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ArchitectureTest extends TestCase
{
    public function test_dashboard_series_fills_missing_days_without_demo_values(): void
    {
        $query = new \App\Services\Dashboard\DashboardQuery;
        $method = new \ReflectionMethod($query, 'normalizeSeries');
        $series = collect([(object) ['dia' => '2026-11-02', 'total' => '12.50']]);
        $points = $method->invoke($query, $series, '2026-11-01', '2026-11-03');
        $this->assertSame([
            ['label' => '01/11/2026', 'value' => 0.0],
            ['label' => '02/11/2026', 'value' => 12.5],
            ['label' => '03/11/2026', 'value' => 0.0],
        ], $points);
    }

    public function test_dashboard_previous_window_has_the_same_inclusive_duration(): void
    {
        $query = new \App\Services\Dashboard\DashboardQuery;
        $method = new \ReflectionMethod($query, 'previousWindow');
        $this->assertSame(['start' => '2026-10-29 00:00:00', 'end' => '2026-10-31 23:59:59'], $method->invoke($query, '2026-11-01', '2026-11-03'));
    }

    public function test_new_application_has_no_legacy_runtime_dependencies(): void
    {
        $root = dirname(__DIR__, 2).'/app';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression('/\$_(?:SESSION|POST|GET|FILES)|\bglobal\s+\$|\bmysqli\b|\brequire_once\b|\bheader\s*\(|\bexit\s*[;(]/', $source, $file->getPathname());
        }
    }
}
