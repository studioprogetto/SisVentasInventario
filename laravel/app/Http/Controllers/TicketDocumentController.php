<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class TicketDocumentController extends Controller
{
    public function attach(Request $request)
    {
        $sale = $request->validate(['venta' => 'required|integer|min:1'])['venta'];
        DB::table('ventas')->where('id_venta', $sale)->firstOrFail();
        $data = $request->validate(['tipo' => 'required|in:boleta,factura', 'numero' => ['required', 'regex:/^[BF][A-Z0-9]{3}-[0-9]{1,10}$/'],
            'documento' => 'required|file|max:8192|mimetypes:application/pdf,application/xml,text/xml']);
        if ($data['numero'][0] !== ($data['tipo'] === 'boleta' ? 'B' : 'F')) {
            throw ValidationException::withMessages(['numero' => 'La serie debe corresponder al tipo de documento.']);
        }
        $file = $request->file('documento');
        $extension = $file->getMimeType() === 'application/pdf' ? 'pdf' : 'xml';
        $key = ['tipo' => $data['tipo'], 'numero' => $data['numero'], 'extension' => $extension];
        $path = $file->store('documentos-ticket', 'local');
        abort_unless($path, 500);
        try {
            $inserted = DB::table('documentos_ticket')->insertOrIgnore($key + ['venta' => $sale, 'usuario' => $request->user()->getKey(),
                'archivo' => $path, 'created_at' => now(), 'updated_at' => now()]);
            if (! $inserted) {
                throw ValidationException::withMessages(['documento' => 'Ese comprobante y formato ya están asociados a una venta.']);
            }
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('status', 'Comprobante vinculado al ticket.');
    }
}
