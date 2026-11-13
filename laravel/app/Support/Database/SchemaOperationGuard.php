<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

final class SchemaOperationGuard
{
    public static function assertAllowed(?string $connectionName = null): void
    {
        $connection = DB::connection($connectionName);
        if (app()->runningInConsole() && app()->bound(SchemaUpgradePermit::class)) {
            $permit = app(SchemaUpgradePermit::class);
            if ($connection->getName() === $permit->connection && $connection->getDatabaseName() === $permit->database) {
                return;
            }
        }
        TestDatabaseGuard::assertIsolated($connectionName);
    }
}
