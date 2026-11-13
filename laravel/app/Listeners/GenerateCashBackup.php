<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CashSessionClosed;
use App\Services\Backups\DatabaseBackup;
use App\Services\Operations\AuditTrail;
use Illuminate\Support\Facades\Log;

final class GenerateCashBackup
{
    public function __construct(private DatabaseBackup $backups, private AuditTrail $audit) {}

    public function handle(CashSessionClosed $event): void
    {
        if (! config('deployment.backup_on_close')) {
            return;
        }
        try {
            $name = $this->backups->create();
            $this->audit->record($event->user, 'backup_cierre', 'turnos_caja', $event->turn, ['archivo' => $name]);
        } catch (\Throwable) {
            Log::error('Automatic cash backup failed; business close remains committed.', ['turn' => $event->turn]);
            $this->audit->record($event->user, 'backup_fallido', 'turnos_caja', $event->turn);
        }
    }
}
