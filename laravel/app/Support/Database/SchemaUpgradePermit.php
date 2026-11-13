<?php

declare(strict_types=1);

namespace App\Support\Database;

final readonly class SchemaUpgradePermit
{
    public function __construct(public string $connection, public string $database) {}
}
