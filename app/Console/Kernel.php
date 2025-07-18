<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\UpdateUserRoles::class,
        \App\Console\Commands\PruneActivityLogs::class,
        \App\Console\Commands\SetupActivityLogs::class,
        \App\Console\Commands\CleanOrphanedEnrollments::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Limpiar registros de actividad antiguos (más de 90 días) cada semana
        $schedule->command('activity:prune --force')->weekly()->sundays()->at('01:00')
            ->appendOutputTo(storage_path('logs/activity-prune.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
