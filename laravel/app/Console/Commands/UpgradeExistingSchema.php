<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backups\DatabaseBackup;
use App\Support\Database\SchemaUpgradePermit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class UpgradeExistingSchema extends Command
{
    protected $signature = 'system:upgrade {--database-name= : Exact existing database name; backup is mandatory}';

    protected $description = 'Backup and apply only the additive operational migration to an existing installation';

    public function handle(DatabaseBackup $backups): int
    {
        $connection = DB::connection();
        $name = $connection->getDatabaseName();
        if ($this->option('database-name') !== $name || ! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('An exact existing MySQL/MariaDB database name is required.');

            return self::FAILURE;
        }
        if (! Schema::hasTable('usuarios')) {
            $this->error('This command cannot initialize an empty database.');

            return self::FAILURE;
        }
        $backup = $backups->create();
        $path = $backups->path($backup);
        Storage::disk('local')->put('backups/'.$backup.'.sha256', hash_file('sha256', $path));
        $this->info('Consistent private backup created and hashed: '.$backup);
        $this->laravel->instance(SchemaUpgradePermit::class, new SchemaUpgradePermit($connection->getName(), $name));
        try {
            return $this->call('migrate', ['--force' => true, '--path' => ['database/migrations/2026_11_13_100000_operational_foundation.php', 'database/migrations/2026_11_13_110000_public_catalog_views.php', 'database/migrations/2026_11_13_120000_documented_cash_returns.php', 'database/migrations/2026_11_14_230000_add_profile_to_usuarios.php', 'database/migrations/2026_11_14_235000_inbox_and_currency.php']]);
        } finally {
            $this->laravel->forgetInstance(SchemaUpgradePermit::class);
        }
    }
}
