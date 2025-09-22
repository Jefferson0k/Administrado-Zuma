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

class SendInvestmentPartialEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Investor $investor,
        public Payment $payment,
        public Investment $investment,
        public $amount
    ) {}

    public function handle(): void
    {
        $this->investor->sendInvestmentPartialEmailNotification(
            $this->payment,
            $this->investment,
            $this->amount
        );
    }
}