<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\OperationRequest;
use App\Services\Sales\SaleCheckout;
use App\Services\Sales\SaleQuote;

final class SaleController extends Controller
{
    public function quote(OperationRequest $request, SaleQuote $pricing): mixed
    {
        return response()->json($pricing->quote($request->validated()));
    }

    public function sale(OperationRequest $request, SaleCheckout $sales): mixed
    {
        return $this->result($request, $sales->checkout((int) $request->user()->getKey(), $request->validated()), 'sales.pos');
    }

    private function result(OperationRequest $request, array $result, string $route): mixed
    {
        return $request->expectsJson() ? response()->json($result) : to_route($route)->with('status', 'Operación registrada.')->with('operation', $result);
    }
}
