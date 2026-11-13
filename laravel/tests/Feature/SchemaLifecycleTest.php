<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Database\TestDatabaseGuard;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SchemaLifecycleTest extends TestCase
{
    public function test_baseline_migrates_rolls_back_and_migrates_again_only_in_test_database(): void
    {
        TestDatabaseGuard::assertIsolated();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('productos'));
        $this->artisan('system:check-schema')->assertSuccessful();
        $this->artisan('migrate:rollback', ['--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('productos'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('productos'));
    }
}
