<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Investment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorInvestedNotification extends Notification
{

    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;


    public function __construct(
        public readonly Invoice $invoice,
        public readonly Investment $investment,
        public readonly Company $company,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ZUMA - Nueva operación recibida')
            ->view('emails.investments.invested', [
                'notifiable' => $notifiable,
                'company' => $this->company,
                'invoice' => $this->invoice,
                'investment' => $this->investment,
                'monto' => MoneyFormatter::formatFromDecimal($this->investment->amount, $this->invoice->currency),
                'tasa' => $this->investment->rate,
                'fecha' => Carbon::parse($this->investment->due_date)->format('d/m/Y'),
            ]);
            // ->line('Hola ' . $notifiable->name)
            // ->line('Tu inversión se registró correctamente.')
            // ->line('Empresa: ' . $this->company->name)
            // ->line('Factura: ' . $this->invoice->invoice_code)
            // ->line('Monto: ' . MoneyFormatter::formatFromDecimal($this->investment->amount, $this->invoice->currency))
            // ->line('Tasa: ' . $this->investment->rate . '%')
            // ->line('Fecha estimada de pago: ' . Carbon::parse($this->investment->due_date)->format('d/m/Y'))
            // ->action('Ver detalles', env('CLIENT_APP_URL') . '/mis-inversiones');
    }
}
