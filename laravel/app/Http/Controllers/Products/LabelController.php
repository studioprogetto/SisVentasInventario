<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Services\Products\LabelSheet;
use Illuminate\Http\Request;

final class LabelController extends Controller
{
    public function __invoke(Request $request, LabelSheet $labels): mixed
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['required', 'integer', 'distinct', 'exists:productos,id_producto']]);

        return view('products.labels', ['labels' => $labels->labels($data['ids'])]);
    }
}
