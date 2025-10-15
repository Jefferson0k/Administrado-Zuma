<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('subastas:activar')->everyMinute();
        $schedule->command('posts:publish-scheduled')->everyMinute();
        $schedule->command('invoices:close-expired')->everyMinute();
        $schedule->command('app:update-invoice-situacion')->everyMinute();
    }
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

}
