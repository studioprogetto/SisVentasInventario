<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('avisos_usuario', function (Blueprint $t) {
            $t->id(); $t->unsignedInteger('usuario'); $t->string('clave', 100);
            $t->string('titulo'); $t->text('mensaje'); $t->text('url')->nullable();
            $t->timestamp('leido')->nullable(); $t->timestamp('eliminado')->nullable()->index();
            $t->timestamp('created_at')->useCurrent(); $t->unique(['usuario','clave']);
        });
        Schema::create('solicitudes_devolucion', function (Blueprint $t) {
            $t->id(); $t->unsignedInteger('usuario'); $t->string('clave',64);
            $t->unsignedInteger('venta'); $t->json('datos'); $t->string('estado')->default('pendiente');
            $t->unsignedInteger('revisor')->nullable(); $t->timestamps(); $t->unique(['usuario','clave']);
        });
        Schema::table('usuarios', fn(Blueprint $t) => $t->boolean('avisos_autolectura')->default(true));
        Schema::table('turnos_caja', function(Blueprint $t) {
            $t->string('nota_apertura')->nullable(); $t->string('nota_cierre')->nullable();
        });
        $pen = DB::table('tipo_moneda')->where('codigo','PEN')->value('id');
        if ($pen) DB::table('ventas')->whereNull('moneda_codigo')->whereNull('id_moneda')->update([
            'id_moneda'=>$pen,'moneda_codigo'=>'PEN','tipo_cambio_aplicado'=>1,'total_base'=>DB::raw('total_venta'),
        ]);
    }
    public function down(): void
    {
        Schema::dropIfExists('avisos_usuario'); Schema::dropIfExists('solicitudes_devolucion');
        Schema::table('usuarios', fn(Blueprint $t) => $t->dropColumn('avisos_autolectura'));
        Schema::table('turnos_caja', fn(Blueprint $t) => $t->dropColumn(['nota_apertura','nota_cierre']));
    }
};
