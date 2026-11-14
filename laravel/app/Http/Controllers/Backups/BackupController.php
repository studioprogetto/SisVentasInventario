<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Services\Backups\DatabaseBackup;
use App\Services\Operations\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class BackupController extends Controller
{
    public function index(DatabaseBackup $backups): mixed
    {
        $files = collect($backups->files())->sortByDesc('name');
        $page = LengthAwarePaginator::resolveCurrentPage();
        $size = config('business.pagination');
        $files = new LengthAwarePaginator($files->forPage($page, $size)->values(), $files->count(), $size, $page, ['path' => request()->url()]);

        return view('backups.index', compact('files'));
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
