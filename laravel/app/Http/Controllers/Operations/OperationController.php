<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\OperationRequest;
use App\Services\Cash\CashSession;
use App\Services\Inventory\InventoryAdjustment;
use App\Services\Inventory\TransferWorkflow;
use App\Services\Operations\Idempotency;
use App\Services\Purchases\PurchaseReceipt;
use App\Services\Sales\ProductExchange;
use App\Services\Sales\ReturnCredit;
use App\Services\Sales\SaleCheckout;
use App\Services\Sales\SaleQuote;

final class OperationController extends Controller
{
    public function refund(OperationRequest $request, ReturnCredit $returns): mixed
    {
        return $this->result($request, (($request->validated('destino') === 'cambio') ? app(ProductExchange::class)->exchange((int) $request->user()->getKey(), $request->validated()) : $returns->refund((int) $request->user()->getKey(), $request->validated())), 'sales.history');
    }

    public function exchange(OperationRequest $request, ProductExchange $exchanges): mixed
    {
        return $this->result($request, $exchanges->exchange((int) $request->user()->getKey(), $request->validated()), 'sales.history');
    }

    public function purchase(OperationRequest $request, PurchaseReceipt $purchases): mixed
    {
        return $this->result($request, $purchases->order((int) $request->user()->getKey(), $request->validated()), 'purchases.index');
    }

    public function receive(OperationRequest $request, PurchaseReceipt $purchases, int $id): mixed
    {
        return $this->result($request, $purchases->receive((int) $request->user()->getKey(), $id), 'purchases.index');
    }

    public function transfer(OperationRequest $request, TransferWorkflow $transfers): mixed
    {
        return $this->result($request, $transfers->create((int) $request->user()->getKey(), $request->validated()), 'transfers.index');
    }

    public function transition(OperationRequest $request, TransferWorkflow $transfers, int $id): mixed
    {
        abort_if($request->user()->isMultifunctional() && $request->validated('estado') === 'cancelada', 403);
        return $this->result($request, $transfers->transition((int) $request->user()->getKey(), $id, $request->validated('estado')), 'transfers.index');
    }

    public function adjust(OperationRequest $request, InventoryAdjustment $inventory): mixed
    {
        return $this->result($request, $inventory->adjust((int) $request->user()->getKey(), $request->validated()), 'products.index');
    }

    public function cash(OperationRequest $request, CashSession $cash, Idempotency $operations): mixed
    {
        $user = (int) $request->user()->getKey();
        $data = $request->validated();
        $action = $request->route()->getName();
        $result = $operations->execute($user, $action, $data['idempotency_key'], $data, fn () => match ($action) {
            'cash.open' => $cash->open($user, (string) $data['monto_inicial'], $data['observacion'] ?? null),
            'cash.close' => $cash->close($user, (string) $data['monto_final_real'], (bool) ($data['confirmar_legacy'] ?? false), $data['observacion'] ?? null),
            default => $cash->movement($user, $data),
        });

        return $this->result($request, $result, 'cash.index');
    }

    private function result(OperationRequest $request, array $result, string $route): mixed
    {
        return $request->expectsJson() ? response()->json($result) : to_route($route)->with('status', 'Operación registrada.')->with('operation', $result);
    }
}
