<?php

declare(strict_types=1);

namespace App\Services\Cash;

use App\Events\CashSessionClosed;
use App\Services\Operations\AuditTrail;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CashSession
{
    public function __construct(private AuditTrail $audit) {}

    public function active(int $user): \stdClass
    {
        $turn = DB::table('turnos_caja')->where(['id_usuario' => $user, 'estado' => 'abierto'])->lockForUpdate()->first();
        if (! $turn) {
            throw ValidationException::withMessages(['caja' => 'Debe abrir su caja antes de operar.']);
        }

        return $turn;
    }

    public function open(int $user, string $amount, ?string $note = null): array
    {
        return DB::transaction(function () use ($user, $amount, $note): array {
            DB::table('usuarios')->where('id_usuario', $user)->lockForUpdate()->first();
            if (DB::table('turnos_caja')->where(['id_usuario' => $user, 'estado' => 'abierto'])->exists()) {
                throw ValidationException::withMessages(['caja' => 'Ya tiene una caja abierta.']);
            }
            $id = DB::table('turnos_caja')->insertGetId(['id_usuario' => $user, 'nota_apertura' => $note, 'monto_inicial' => Money::decimal(Money::cents($amount)), 'estado' => 'abierto'], 'id_turno');
            $this->audit->record($user, 'apertura', 'turnos_caja', $id);

            return ['id_turno' => $id];
        }, 3);
    }

    public function movement(int $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data): array {
            $turn = $this->active($user);
            $amount = Money::cents($data['monto']);
            if ($amount <= 0 || ! in_array($data['tipo_movimiento'], ['ingreso', 'egreso'], true)) {
                throw ValidationException::withMessages(['monto' => 'Movimiento inválido.']);
            }
            $id = DB::table('movimientos_caja')->insertGetId(['id_turno' => $turn->id_turno, 'id_usuario' => $user,
                'tipo_movimiento' => $data['tipo_movimiento'], 'monto' => Money::decimal($amount), 'descripcion' => $data['descripcion']], 'id_movimiento_caja');
            $this->audit->record($user, 'movimiento', 'movimientos_caja', $id, ['tipo_movimiento'=>$data['tipo_movimiento']]);

            return ['id_movimiento_caja' => $id];
        }, 3);
    }

    public function close(int $user, string $declared, bool $legacyReviewed = false, ?string $note = null): array
    {
        return DB::transaction(function () use ($user, $declared, $legacyReviewed, $note): array {
            $turn = $this->active($user);
            $legacy = DB::table('ventas')->where('id_turno', $turn->id_turno)->whereNull('id_moneda')->count();
            if ($legacy > 0 && ! $legacyReviewed) {
                throw ValidationException::withMessages(['caja' => 'Revise las ventas históricas sin moneda documentada antes de cerrar este turno.']);
            }
            // Physical cash only. Electronic payments and credit notes are separate.
            $cash = DB::table('ventas')->where('id_turno', $turn->id_turno)->where('metodo_pago', 'efectivo')->whereIn('estado', ['completada', 'parcialmente_devuelta', 'anulada'])
                ->selectRaw('COALESCE(SUM(COALESCE(cobro_base,total_base,total_venta)),0) as cash_amount')->first()->cash_amount;
            $movements = DB::table('movimientos_caja')->where('id_turno', $turn->id_turno)->get();
            $expected = Money::cents($turn->monto_inicial) + Money::cents((string) $cash);
            foreach ($movements as $movement) {
                $expected += ($movement->tipo_movimiento === 'ingreso' ? 1 : -1) * Money::cents($movement->monto);
            }
            $actual = Money::cents($declared);
            DB::table('turnos_caja')->where('id_turno', $turn->id_turno)->update([
                'estado' => 'cerrado', 'nota_cierre' => $note, 'fecha_cierre' => now(), 'monto_final_sistema' => Money::decimal($expected),
                'monto_final_real' => Money::decimal($actual), 'diferencia' => Money::decimal($actual - $expected),
            ]);
            $this->audit->record($user, 'cierre', 'turnos_caja', $turn->id_turno);
            if ($actual !== $expected) {
                app(\App\Services\Notifications\Inbox::class)->admins('caja-diferencia-'.$turn->id_turno,
                    'Diferencia en cierre de caja', 'Turno #'.$turn->id_turno.' · Diferencia declarada: S/ '.Money::decimal($actual - $expected));
            }
            if ($legacy > 0) {
                $this->audit->record($user, 'cierre_historico_revisado', 'turnos_caja', $turn->id_turno, ['ventas_sin_moneda' => $legacy]);
            }
            DB::afterCommit(fn () => event(new CashSessionClosed($user, (int) $turn->id_turno)));

            return ['id_turno' => $turn->id_turno, 'esperado' => Money::decimal($expected), 'declarado' => Money::decimal($actual), 'diferencia' => Money::decimal($actual - $expected)];
        }, 3);
    }
}
