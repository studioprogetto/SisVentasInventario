<?php

declare(strict_types=1);

use App\Support\Database\SchemaOperationGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        SchemaOperationGuard::assertAllowed();
        Schema::create('empresa_configuraciones', function (Blueprint $table): void {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->timestamps();
        });

        $defaults = [
            ['clave' => 'name', 'valor' => 'STUDIO & PROGETTO'],
            ['clave' => 'ruc', 'valor' => '10756263157'],
            ['clave' => 'address', 'valor' => 'Calles Las Gemas 626 Urb. Santa Inés'],
            ['clave' => 'district', 'valor' => 'Trujillo - La Libertad'],
            ['clave' => 'phone', 'valor' => '+51 939275406'],
            ['clave' => 'email', 'valor' => 'ventas@studioyprogetto.com'],
            ['clave' => 'website', 'valor' => 'https://studioyprogetto.com'],
            ['clave' => 'opening_weekdays', 'valor' => 'Lun - Vie: 8:00 - 20:30'],
            ['clave' => 'opening_weekend', 'valor' => 'Sáb: 8:00 - 20:30'],
            ['clave' => 'return_policy_days', 'valor' => '4'],
            ['clave' => 'display_currency', 'valor' => 'S/'],
        ];

        foreach (array_chunk($defaults, 25) as $chunk) {
            DB::table('empresa_configuraciones')->upsert($chunk, ['clave']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_configuraciones');
    }
};
