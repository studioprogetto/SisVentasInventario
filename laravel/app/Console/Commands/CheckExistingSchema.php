<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class CheckExistingSchema extends Command
{
    protected $signature = 'system:check-schema';

    protected $description = 'Read-only comparison of existing table and column names with the phase-0 baseline';

    public function handle(): int
    {
        $baseline = json_decode(file_get_contents(database_path('schema/legacy-schema.json')), true, flags: JSON_THROW_ON_ERROR);
        $missing = [];
        foreach ($baseline as $table => $definition) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;

                continue;
            }
            $actual = Schema::getColumnListing($table);
            foreach ($definition['columns'] as $column) {
                if (! in_array($column['COLUMN_NAME'], $actual, true)) {
                    $missing[] = $table.'.'.$column['COLUMN_NAME'];
                }
            }
        }
        if ($missing !== []) {
            foreach ($missing as $item) {
                $this->error('Missing schema object: '.$item);
            }

            return self::FAILURE;
        }
        $this->info(count($baseline).' table definitions match by table/column name. No data changed.');
        $this->comment('This does not certify types, constraints, values or business correctness.');

        return self::SUCCESS;
    }
}
