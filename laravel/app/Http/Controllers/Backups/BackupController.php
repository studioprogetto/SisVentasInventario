<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Services\Backups\DatabaseBackup;
use App\Services\Operations\AuditTrail;
use Illuminate\Http\Request;

final class BackupController extends Controller
{
    public function index(DatabaseBackup $backups): mixed
    {
        return view('backups.index', ['files' => $backups->files()]);
    }

    public function create(Request $request, DatabaseBackup $backups, AuditTrail $audit): mixed
    {
        $name = $backups->create();
        $audit->record((int) $request->user()->getKey(), 'crear_backup', 'backups', $name);

        return back()->with('status', 'Copia privada generada.');
    }

    public function download(DatabaseBackup $backups, string $name): mixed
    {
        return response()->download($backups->path($name), $name);
    }
}
