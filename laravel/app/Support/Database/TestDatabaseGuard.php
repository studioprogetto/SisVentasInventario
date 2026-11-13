<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use LogicException;

final class TestDatabaseGuard
{
    public static function assertIsolated(?string $connectionName = null): void
    {
        $connection = DB::connection($connectionName);
        $database = $connection->getDatabaseName();
        $isolated = $connection->getDriverName() === 'sqlite'
            ? $database === ':memory:'
            : str_ends_with($database, '_test');

        if (! app()->environment('testing') || ! $isolated) {
            throw new LogicException('Schema operations are restricted to testing with SQLite :memory: or a database ending in _test.');
        }
    }
}
