<?php

namespace App\Services\Tickets;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TicketDelivery
{
    public function event(int $id, string $action, ?int $user = null): void
    {
        DB::table('eventos_ticket')->insert(['entrega' => $id, 'accion' => $action, 'usuario' => $user, 'created_at' => now()]);
    }

    private function token(string $nonce): string
    {
        return hash_hmac('sha256', 'ticket-delivery:'.$nonce, config('app.key'));
    }

    public function publicUrl(string $path): string
    {
        $base = config('business.ticket_public_url') ?: config('app.url');
        $runtime = storage_path('framework/ticket-public-url.txt');
        $temporary = is_file($runtime) ? trim((string) file_get_contents($runtime)) : '';
        if (preg_match('~^https://[a-z0-9-]+\.trycloudflare\.com$~D', $temporary)) {
            $base = $temporary;
        }

        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    public function active(int $cashier): ?object
    {
        return DB::table('entregas_ticket')->where(['cajero' => $cashier, 'estado' => 'activo'])
            ->where(fn ($q) => $q->whereNull('vence')->orWhere('vence', '>', now()))->first();
    }

    public function display(int $cashier): array
    {
        $row = $this->active($cashier);

        return $row ? ['url' => $this->publicUrl('ticket/acceso/'.$this->token($row->nonce)),
            'remaining' => $row->vence ? max(0, strtotime($row->vence) - now()->timestamp) : null,
            'ticket' => $row->venta] : ['url' => null];
    }

    private function expire(int $cashier): void
    {
        $expired = DB::table('entregas_ticket')->where(['cajero' => $cashier, 'estado' => 'activo'])
            ->where('vence', '<=', now())->lockForUpdate()->get();
        foreach ($expired as $row) {
            DB::table('entregas_ticket')->where('id', $row->id)->update(['estado' => 'expirado', 'token_hash' => null, 'nonce' => null]);
            $this->event($row->id, 'expirado');
        }
    }

    public function operate(int $cashier, int $saleId, string $action, ?int $seconds, ?int $actor = null): string
    {
        return DB::transaction(function () use ($cashier, $saleId, $action, $seconds, $actor) {
            // Todos los puestos del mismo cajero comparten el bloqueo de publicación.
            DB::table('usuarios')->where('id_usuario', $cashier)->lockForUpdate()->firstOrFail();
            $sale = DB::table('ventas')->where(['id_venta' => $saleId, 'id_usuario' => $cashier])->firstOrFail();
            $this->expire($cashier);
            $table = DB::table('entregas_ticket')->where('venta', $saleId);
            $row = (clone $table)->lockForUpdate()->first();
            if ($action === 'publicar') {
                $action = match ($row->estado ?? null) {
                    null => 'solicitar',
                    'activo', 'pausado', 'entregado' => 'renovar',
                    default => 'mostrar',
                };
            }
            if ($action === 'solicitar') {
                if ($row) {
                    return 'La venta ya está en el centro de tickets.';
                }
                $id = DB::table('entregas_ticket')->insertGetId(['venta' => $saleId, 'cajero' => $cashier, 'turno' => $sale->id_turno,
                    'fecha_venta' => $sale->fecha_venta, 'estado' => 'pendiente', 'segundos' => $seconds ?? 60, 'created_at' => now(), 'updated_at' => now()]);
                $this->event($id, 'solicitado', $actor ?? $cashier);
                $row = (clone $table)->first();
            } else {
                abort_unless($row, 404);
            }

            $allowed = match ($action) {
                'mostrar' => ['pendiente', 'expirado', 'revocado', 'cerrado'],
                'renovar' => ['activo', 'expirado', 'pausado', 'entregado'],
                'pausar' => ['activo'],
                'reanudar' => ['pausado'],
                'revocar','cerrar' => ['activo', 'pausado', 'pendiente', 'expirado', 'entregado', 'revocado'],
                'solicitar' => ['pendiente'],
                default => [],
            };
            if (! in_array($row->estado, $allowed, true)) {
                throw ValidationException::withMessages(['entrega' => 'La entrega cambió de estado. Actualiza el centro de tickets.']);
            }
            $next = match ($action) {
                'pausar' => 'pausado', 'revocar' => 'revocado', 'cerrar' => 'cerrado', default => 'activo',
            };
            $changes = ['estado' => $next, 'token_hash' => null, 'nonce' => null, 'acceso_hash' => null, 'vence' => null, 'updated_at' => now()];
            if ($next === 'activo') {
                $occupied = DB::table('entregas_ticket')->where('cajero', $cashier)->whereIn('estado', ['activo', 'pausado'])->where('id', '!=', $row->id)->exists();
                $earlier = DB::table('entregas_ticket')->where(['cajero' => $cashier, 'estado' => 'pendiente'])
                    ->where(fn ($q) => $q->where('fecha_venta', '<', $sale->fecha_venta)->orWhere(fn ($tie) => $tie->where('fecha_venta', $sale->fecha_venta)->where('venta', '<', $saleId)))->exists();
                if ($occupied || ($earlier && ! in_array($row->estado, ['activo', 'pausado'], true))) {
                    if ($action === 'solicitar') {
                        return 'Ticket en cola. Hay una entrega anterior.';
                    }
                    throw ValidationException::withMessages(['entrega' => 'Atiende primero el ticket anterior de esta caja.']);
                }
                $ttl = $seconds ?? $row->segundos;
                $nonce = bin2hex(random_bytes(32));
                $changes['nonce'] = $nonce;
                $changes['token_hash'] = hash('sha256', $this->token($nonce));
                $changes['segundos'] = $ttl;
                $changes['vence'] = $ttl ? now()->addSeconds($ttl) : null;
            } elseif ($next === 'pausado' && $row->vence) {
                $changes['segundos'] = max(1, strtotime($row->vence) - now()->timestamp);
            }
            $table->update($changes);
            $this->event($row->id, $action, $actor ?? $cashier);

            return 'Entrega actualizada.';
        }, 3);
    }

    public function available(string $token)
    {
        return DB::table('entregas_ticket')->where('token_hash', hash('sha256', $token))->where('estado', 'activo')
            ->where(fn ($q) => $q->whereNull('vence')->orWhere('vence', '>', now()));
    }

    public function claim(string $token): array
    {
        return DB::transaction(function () use ($token) {
            $row = $this->available($token)->lockForUpdate()->first();
            abort_unless($row, 410);
            $access = bin2hex(random_bytes(32));
            DB::table('entregas_ticket')->where('id', $row->id)->update(['estado' => 'entregado', 'acceso_hash' => hash('sha256', $access),
                'nonce' => null, 'token_hash' => null, 'vence' => now()->addDay(), 'updated_at' => now()]);
            $this->event($row->id, 'reclamado');

            return ['id' => $row->id, 'clave' => $access];
        });
    }
}
