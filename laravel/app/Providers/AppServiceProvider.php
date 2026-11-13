<?php

namespace App\Providers;

use App\Events\CashSessionClosed;
use App\Listeners\GenerateCashBackup;
use App\Support\BusinessConfig;
use App\Support\Database\GuardedMigrationRepository;
use App\Support\Database\SchemaOperationGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('migration.repository', function ($repository, $app) {
            $configuration = $app['config']['database.migrations'];
            $table = is_array($configuration) ? $configuration['table'] : $configuration;

            return new GuardedMigrationRepository($app['db'], $table);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BusinessConfig::apply();
        Event::listen(CashSessionClosed::class, GenerateCashBackup::class);
        DB::prohibitDestructiveCommands(! $this->app->environment('testing'));
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (str_starts_with((string) $event->command, 'migrate') || in_array($event->command, ['db:wipe', 'schema:load'])) {
                SchemaOperationGuard::assertAllowed();
            }
        });
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by(hash('sha256', mb_strtolower((string) $request->input('username')).'|'.$request->ip())),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });
    }
}
