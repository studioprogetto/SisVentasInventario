<?php

declare(strict_types=1);

use App\Support\Database\TestDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Test baseline only; existing installations already own these tables. */
return new class extends Migration
{
    public function up(): void
    {
        TestDatabaseGuard::assertIsolated();
        $schema = json_decode(file_get_contents(database_path('schema/legacy-schema.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach (array_keys($schema) as $name) {
            if (Schema::hasTable($name)) {
                throw new LogicException('Refusing to overwrite an existing business table.');
            }
        }
        Schema::disableForeignKeyConstraints();
        try {
            foreach ($schema as $name => $definition) {
                if (DB::getDriverName() !== 'sqlite') {
                    DB::unprepared($definition['ddl']);

                    continue;
                }
                // SQLite fixture preserves names, nullability, unique keys and defaults.
                // MariaDB ENUM/FK/index semantics are tested separately with its exact DDL.
                Schema::create($name, function (Blueprint $table) use ($definition): void {
                    $primary = [];
                    foreach ($definition['columns'] as $column) {
                        $key = $column['COLUMN_NAME'];
                        if (str_contains($column['EXTRA'], 'auto_increment')) {
                            $table->increments($key);

                            continue;
                        }
                        $type = $column['DATA_TYPE'];
                        $field = match (true) {
                            in_array($type, ['int', 'bigint', 'tinyint', 'smallint']) => $table->integer($key),
                            in_array($type, ['decimal', 'double', 'float']) => $table->decimal($key, 15, 4),
                            $type === 'date' => $table->date($key),
                            in_array($type, ['datetime', 'timestamp']) => $table->dateTime($key),
                            $type === 'text' => $table->text($key),
                            default => $table->string($key, 511),
                        };
                        if ($column['IS_NULLABLE'] === 'YES') {
                            $field->nullable();
                        }
                        $default = $column['COLUMN_DEFAULT'];
                        if ($default !== null && $default !== 'NULL') {
                            $field->default(strtolower($default) === 'current_timestamp()' ? DB::raw('CURRENT_TIMESTAMP') : trim($default, "'"));
                        }
                        if ($column['COLUMN_KEY'] === 'PRI') {
                            $primary[] = $key;
                        }
                        if ($column['COLUMN_KEY'] === 'UNI') {
                            $field->unique();
                        }
                    }
                    if ($primary !== []) {
                        $table->primary($primary);
                    }
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        TestDatabaseGuard::assertIsolated();
        $schema = json_decode(file_get_contents(database_path('schema/legacy-schema.json')), true, flags: JSON_THROW_ON_ERROR);
        Schema::disableForeignKeyConstraints();
        try {
            foreach (array_reverse(array_keys($schema)) as $name) {
                Schema::dropIfExists($name);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
