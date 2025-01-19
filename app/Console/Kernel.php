<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Customer;
use App\Console\Commands\GeneratePaymentLinksCommand;
use App\Console\Commands\ProcessDirectDebits;




class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
 	Commands\GeneratePaymentLinksCommand::class,
 	Commands\ProcessDirectDebits::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
protected function schedule(Schedule $schedule)
{
    $schedule->command('directdebit:process')
             ->dailyAt('00:00') 
             ->before(function () {
                 \Log::info('Starting directdebit:process command at ' . now());
             })
             ->after(function () {
                 \Log::info('Finished directdebit:process command at ' . now());
             });
}


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
