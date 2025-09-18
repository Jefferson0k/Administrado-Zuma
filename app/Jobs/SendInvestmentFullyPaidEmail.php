<?php

namespace App\Jobs;

use App\Models\Investor;
use App\Models\Payment;
use App\Models\Investment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvestmentFullyPaidEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Investor $investor,
        public Payment $payment,
        public Investment $investment,
        public $netExpectedReturn,
        public $itfAmount
    ) {}

    public function handle(): void
    {
        $this->investor->sendInvestmentFullyPaidEmailNotification(
            $this->payment,
            $this->investment,
            $this->netExpectedReturn,
            $this->itfAmount
        );
    }
}