<?php

declare(strict_types=1);

namespace App\Services\Masters;

use App\Support\SearchTerms;
use App\Services\Operations\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MasterData
{
    public function __construct(private AuditTrail $audit) {}

    public function definition(string $module): array
    {
        $definition = config('masters.'.$module);
        abort_unless(is_array($definition), 404);

        return $definition;
    }

    public function permission(string $module, string $action): string
    {
        $definition = $this->definition($module);

        if (auth()->user()?->isMultifunctional()) return $definition['permission'].'_'.$action;
        return $definition['all_permission'] ?? $definition['permission'].'_'.$action;
    }

    public function model(string $module): Model
    {
        $definition = $this->definition($module);

        return new $definition['model'];
    }

    public function search(string $module, string $term, string $state = 'activos'): mixed
    {
        $model = $this->model($module);
        $field = array_key_first($this->definition($module)['fields']);

        $rows = $model->newQuery()->where('activo', $state !== 'inactivos')->when($term !== '', fn ($query) => $query->where(SearchTerms::matching($term, [$field])))->orderBy($field)->paginate(config('business.pagination'))->withQueryString();
        if ($module === 'cliente') {
            $balances = DB::table('saldos_clientes as s')
                ->leftJoin('tipo_moneda as m', 'm.id', '=', 's.id_moneda')
                ->whereIn('s.id_cliente', $rows->getCollection()->modelKeys())
                ->where('s.estado', 'pendiente')
                ->groupBy('s.id_cliente', 's.id_moneda', 'm.codigo')
                ->orderBy('m.codigo')
                ->select('s.id_cliente', 'm.codigo')
                ->selectRaw('SUM(CASE WHEN saldo > 0 THEN saldo ELSE 0 END) AS favor, SUM(CASE WHEN saldo < 0 THEN -saldo ELSE 0 END) AS deuda')
                ->get()->groupBy('id_cliente');
            foreach ($rows as $row) {
                $row->setRelation('balances', $balances->get($row->getKey(), collect()));
            }
        }
        return $rows;
    }

    public function save(string $module, int $user, array $values, ?int $id): int
    {
        return DB::transaction(function () use ($module, $user, $values, $id): int {
            $model = $this->model($module);
            if ($id) {
                $model = $model->newQuery()->lockForUpdate()->findOrFail($id);
                if ($module === 'tipomoneda' && isset($values['codigo']) && $values['codigo'] !== $model->getAttribute('codigo') && ($model->getAttribute('principal') || DB::table('ventas')->where('id_moneda', $id)->exists() || DB::table('compras')->where('id_moneda', $id)->exists() || DB::table('tipo_cambio')->where('id_moneda', $id)->exists())) {
                    throw ValidationException::withMessages(['codigo' => 'No puede cambiar la identidad de una moneda principal o con historial.']);
                }
            }
            $model->forceFill(array_intersect_key($values, $this->definition($module)['fields']));
            $model->save();
            $this->audit->record($user, 'guardar', $model->getTable(), $model->getKey(), $model->getAttributes());

            return (int) $model->getKey();
        });
    }

    public function state(string $module, int $user, int $id, bool $active): void
    {
        DB::transaction(function () use ($module, $user, $id, $active): void {
            $model = $this->model($module)->newQuery()->lockForUpdate()->findOrFail($id);
            if (! $active && $module === 'almacen' && (DB::table('existencias')->where('id_almacen', $id)->where('stock', '>', 0)->exists()
                || DB::table('transferencias')->whereIn('estado', ['pendiente', 'enviada'])->where(fn ($query) => $query->where('origen', $id)->orWhere('destino', $id))->exists())) {
                throw ValidationException::withMessages(['estado' => 'El almacén tiene existencias o transferencias abiertas.']);
            }
            if (! $active && $module === 'tipomoneda' && $model->getAttribute('principal')) {
                throw ValidationException::withMessages(['estado' => 'La moneda principal debe permanecer activa.']);
            }
            $model->setAttribute('activo', $active);
            $model->save();
            $this->audit->record($user, 'estado', $model->getTable(), $id, ['activo' => $active]);
        });
    }
}
