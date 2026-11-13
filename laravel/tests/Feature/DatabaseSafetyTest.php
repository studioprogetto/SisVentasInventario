<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

final class DatabaseSafetyTest extends TestCase
{
    public function test_migration_command_is_blocked_before_creating_repository_outside_testing(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        $this->assertFalse(Schema::hasTable('migrations'));
        $this->app->instance('env', 'local');
        try {
            $this->artisan('migrate', ['--force' => true])->run();
            $this->fail('A non-testing migration command must be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('restricted', $exception->getMessage());
        }
        $this->assertFalse(Schema::hasTable('migrations'));
    }
}
