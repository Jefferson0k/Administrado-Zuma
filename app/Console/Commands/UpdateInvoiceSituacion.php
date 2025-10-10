<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Invoice;

class UpdateInvoiceSituacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-invoice-situacion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        

        $affected = Invoice::where('created_at', '<', $today->subDays(8))
            ->update(['situacion' => 'vigente']);

        $affected = Invoice::where('created_at', '>=', $today->subDays(8))
            ->update(['situacion' => 'vigente_8_dias']);

        

            
    }
}
