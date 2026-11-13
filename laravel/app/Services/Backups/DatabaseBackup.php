<?php

declare(strict_types=1);

namespace App\Services\Backups;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

final class DatabaseBackup
{
    public function create(): string
    {
        $configuration = config('database.connections.'.config('database.default'));
        if (! in_array($configuration['driver'], ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Backup requires a MySQL/MariaDB connection.');
        }
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        $name = 'backup-'.now()->format('Ymd-His').'-'.Str::random(10).'.sql';
        $path = $disk->path('backups/'.$name);
        $file = fopen($path, 'wb');
        try {
            $process = new Process([config('deployment.mysqldump'), '--single-transaction', '--quick', '--routines', '--triggers',
                '--host='.$configuration['host'], '--port='.$configuration['port'], '--user='.$configuration['username'], $configuration['database']], null, ['MYSQL_PWD' => $configuration['password']], null, 120);
            $process->mustRun(function (string $type, string $buffer) use ($file): void {
                if ($type === Process::OUT) {
                    fwrite($file, $buffer);
                }
            });
        } catch (\Throwable $exception) {
            fclose($file);
            $disk->delete('backups/'.$name);
            throw new RuntimeException('No se pudo generar la copia de seguridad. Revise la configuración del motor.', previous: $exception);
        }
        fclose($file);

        return $name;
    }

    public function files(): array
    {
        return array_values(array_map(fn ($path) => ['name' => basename($path), 'bytes' => Storage::disk('local')->size($path)], array_filter(Storage::disk('local')->files('backups'), fn ($path) => str_ends_with($path, '.sql'))));
    }

    public function path(string $name): string
    {
        abort_unless(preg_match('/^backup-\d{8}-\d{6}-[a-zA-Z0-9]{10}\.sql$/D', $name), 404);
        abort_unless(Storage::disk('local')->exists('backups/'.$name), 404);

        return Storage::disk('local')->path('backups/'.$name);
    }
}
