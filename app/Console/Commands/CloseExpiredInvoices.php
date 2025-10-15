<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CloseExpiredInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'php artisan make:command CloseExpiredInvoices.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $limitDate = $today->subDays(25);

        $affected = Invoice::where('condicion_oportunidad', '!=', 'cerrada')
            ->where(function ($query) use ($limitDate) {
                $query->whereDate('estimated_pay_date', '<', $limitDate)
                    ->orWhere('financed_amount', '<=', 0)
                    ->orWhere(function ($q) {
                        $q->whereNull('approval1_status')
                            ->orWhere('approval1_status', '!=', 'approved')
                            ->orWhereNull('approval2_status')
                            ->orWhere('approval2_status', '!=', 'approved')
                            ->orWhere('status_conclusion', '!=', 'approved');
                    });
            })
            ->update(['condicion_oportunidad' => 'cerrada']);

        $this->info("✅ $affected La factura ha sido cerrada.");

        return Command::SUCCESS;
    }
}
