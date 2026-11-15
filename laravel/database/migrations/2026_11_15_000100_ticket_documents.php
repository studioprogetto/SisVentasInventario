<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_ticket', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('venta')->index();
            $t->unsignedInteger('usuario');
            $t->string('tipo', 10);
            $t->string('numero', 32);
            $t->string('archivo');
            $t->string('extension', 4);
            $t->timestamps();
            $t->unique(['tipo', 'numero', 'extension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_ticket');
    }
};
