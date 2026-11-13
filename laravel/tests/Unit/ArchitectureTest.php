<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ArchitectureTest extends TestCase
{
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
