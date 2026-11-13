<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;

/** Guard the repository too: migrations create it before calling a migration's up(). */
final class GuardedMigrationRepository extends DatabaseMigrationRepository
{
    public function createRepository(): void
    {
        SchemaOperationGuard::assertAllowed($this->getConnection()->getName());
        parent::createRepository();
    }

    public function log($file, $batch): void
    {
        SchemaOperationGuard::assertAllowed($this->getConnection()->getName());
        parent::log($file, $batch);
    }

    public function delete($migration): void
    {
        SchemaOperationGuard::assertAllowed($this->getConnection()->getName());
        parent::delete($migration);
    }

    public function deleteRepository(): void
    {
        SchemaOperationGuard::assertAllowed($this->getConnection()->getName());
        parent::deleteRepository();
    }
}
