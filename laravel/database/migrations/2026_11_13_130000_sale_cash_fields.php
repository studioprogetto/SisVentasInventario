<?php

declare(strict_types=1);

use App\Support\Database\SchemaOperationGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        SchemaOperationGuard::assertAllowed();
        Schema::table('ventas', function (Blueprint $table): void {
            $table->decimal('monto_recibido', 10, 2)->nullable()->after('metodo_pago');
            $table->decimal('cambio_entregado', 10, 2)->nullable()->after('monto_recibido');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->dropColumn(['monto_recibido', 'cambio_entregado']);
        });
    }
};
