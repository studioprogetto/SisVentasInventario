<?php

declare(strict_types=1);

namespace App\Http\Controllers\Currency;

use App\Http\Controllers\Controller;
use App\Models\TipoCambio;
use App\Models\TipoMoneda;
use App\Services\Currency\ExchangeRateWriter;
use Illuminate\Http\Request;

final class ExchangeRateController extends Controller
{
    public function index(): mixed
    {
        return view('currency.rates', ['rates' => TipoCambio::query()->with('moneda')->orderByDesc('fecha')->paginate(config('business.pagination')), 'currencies' => TipoMoneda::query()->where('activo', 1)->get()]);
    }

    public function save(Request $request, ExchangeRateWriter $rates): mixed
    {
        $data = $request->validate(['fecha' => ['required', 'date_format:Y-m-d'], 'id_moneda' => ['required', 'integer', 'exists:tipo_moneda,id'], 'valor_soles' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:999999']]);
        $rates->save((int) $request->user()->getKey(), $data);

        return back()->with('status', 'Tipo de cambio guardado; las operaciones anteriores conservan su tasa.');
    }
}
