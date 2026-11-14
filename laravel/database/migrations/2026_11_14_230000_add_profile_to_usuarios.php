<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('nombres', 100)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('correo', 254)->nullable();
            $table->string('celular', 25)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('foto_perfil')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn([
            'nombres', 'apellidos', 'correo', 'celular', 'fecha_nacimiento', 'foto_perfil',
        ]));
    }
};
