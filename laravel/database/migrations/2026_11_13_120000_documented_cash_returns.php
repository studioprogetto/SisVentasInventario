<?php

declare(strict_types=1);

use App\Support\Database\SchemaOperationGuard;
use App\Support\Database\TestDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        SchemaOperationGuard::assertAllowed();
        foreach (['cambios_ventas', 'auditoria_cambios', 'ventas_anuladas'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->integer('id_cliente')->nullable()->change());
        }
        Schema::table('detalles_cambio', function (Blueprint $table): void {
            $table->integer('id_producto')->nullable()->change();
            $table->integer('id_servicio')->nullable();
        });
        Schema::table('movimientos_caja', fn (Blueprint $table) => $table->integer('referencia_id')->nullable());
    }

    public function down(): void
    {
        TestDatabaseGuard::assertIsolated();
        // Nullable historical references are not tightened again: that could destroy a legitimate generic return.
        Schema::table('detalles_cambio', fn (Blueprint $table) => $table->dropColumn('id_servicio'));
        Schema::table('movimientos_caja', fn (Blueprint $table) => $table->dropColumn('referencia_id'));
    }
};
