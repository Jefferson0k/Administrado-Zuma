<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Investment;
use App\Models\Balance;
use App\Models\Movement;
use App\Models\User;
use App\Notifications\FacturaAnuladaAdminNotification;
use App\Notifications\ReembolsoInversionNotification;
use App\Notifications\ErrorAnulacionFacturaNotification;
use App\Enums\MovementStatus;
use App\Helpers\MoneyConverter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AnularFacturaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoiceId;
    public $userId;
    public $comment;
    public $timeout = 300;

    public function __construct($invoiceId, $userId, $comment = null)
    {
        $this->invoiceId = $invoiceId;
        $this->userId = $userId;
        $this->comment = $comment;
    }

    public function handle()
    {
        try {
            DB::transaction(function () {
                $invoice = Invoice::with(['investments.investor'])->findOrFail($this->invoiceId);
                
                if (!$invoice->anularFactura($this->userId, $this->comment)) {
                    throw new \Exception('La factura no puede ser anulada (ya pagada o ya anulada).');
                }

                $invoice->update([
                    'status' => 'rejected',
                    'approval1_status' => 'rejected',
                    'approval2_status' => 'rejected'
                ]);

                $investments = Investment::where('invoice_id', $this->invoiceId)->get();
                $processedInvestments = 0;

                foreach ($investments as $investment) {
                    $this->processInvestment($investment, $invoice->id);
                    $processedInvestments++;

                    // Notificar al inversor
                    $investment->investor->notify(
                        new ReembolsoInversionNotification($invoice, $investment)
                    );
                }

                // Notificar al administrador
                $adminUsers = User::where('role', 'admin')->get();
                Notification::send($adminUsers, new FacturaAnuladaAdminNotification(
                    $invoice, 
                    $processedInvestments
                ));

                Log::info("Factura {$this->invoiceId} anulada exitosamente", [
                    'user_id' => $this->userId,
                    'investments_processed' => $processedInvestments
                ]);
            });
        } catch (\Throwable $exception) {
            $this->handleError($exception);
            throw $exception;
        }
    }

    protected function processInvestment(Investment $investment, $invoiceId)
    {
        $investor = $investment->investor;
        $balance = Balance::where('investor_id', $investor->id)
            ->where('currency', $investment->currency)
            ->first();

        if ($balance) {
            $balance->subtractInvestedAmount(
                MoneyConverter::fromDecimal($investment->amount, $investment->currency)
            );
            $balance->subtractExpectedAmount(
                MoneyConverter::fromDecimal($investment->return, $investment->currency)
            );
            $balance->addAmount(
                MoneyConverter::fromDecimal($investment->amount, $investment->currency)
            );
            $balance->save();
        }

        $movement = Movement::create([
            'currency' => $investment->currency,
            'amount' => $investment->amount,
            'type' => 'withdraw',
            'status' => MovementStatus::VALID,
            'confirm_status' => MovementStatus::VALID,
            'description' => 'Reembolso por anulación de factura #' . $invoiceId,
            'investor_id' => $investment->investor_id,
        ]);

        $investment->update([
            'status' => 'reembloso',
            'movement_reembloso' => $movement->id
        ]);
    }

    protected function handleError(\Throwable $exception)
    {
        $invoice = Invoice::find($this->invoiceId);
        
        $adminUsers = User::where('role', 'admin')->get();
        
        Notification::send($adminUsers, new ErrorAnulacionFacturaNotification(
            $invoice ?? new Invoice(['id' => $this->invoiceId]),
            $exception->getMessage()
        ));

        Log::error("Error en AnularFacturaJob para factura {$this->invoiceId}", [
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
            'trace' => $exception->getTraceAsString()
        ]);
    }

    public function failed(\Throwable $exception)
    {
        // El handleError ya se ejecutó en el catch, pero por si acaso
        $this->handleError($exception);
    }
}