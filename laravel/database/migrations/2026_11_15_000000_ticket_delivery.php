<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas_ticket', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('venta')->unique();
            $t->unsignedInteger('cajero');
            $t->unsignedInteger('turno')->nullable();
            $t->dateTime('fecha_venta');
            $t->string('estado', 20);
            $t->string('nonce', 64)->nullable();
            $t->char('token_hash', 64)->nullable()->unique();
            $t->char('acceso_hash', 64)->nullable();
            $t->dateTime('vence')->nullable();
            $t->unsignedSmallInteger('segundos')->default(60);
            $t->timestamps();
            $t->index(['cajero', 'estado', 'fecha_venta']);
        });
        Schema::create('eventos_ticket', function (Blueprint $t) {
            $t->id();
            $t->foreignId('entrega')->constrained('entregas_ticket')->cascadeOnDelete();
            $t->string('accion', 32);
            $t->unsignedInteger('usuario')->nullable();
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_ticket');
        Schema::dropIfExists('entregas_ticket');
    }
};
