<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Investment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Money\Money;

class InvestorFullyPaymentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment,
        private readonly Investment $investment,
        private readonly Money $netExpectedReturn,
        private readonly Money $itfAmount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago de inversión')
            ->line('Hola ' . $notifiable->name)
            ->line('Tu pago de inversión se ha efectuado correctamente.')
            ->line('Empresa: ' . $this->payment->invoice->company->name)
            ->line('Número de factura: ' . $this->payment->invoice->invoice_code)
            ->line('Fecha de pago: ' . Carbon::parse($this->payment->pay_date)->format('d/m/Y'))
            ->line('Monto: ' . MoneyFormatter::formatFromDecimal($this->investment->amount))
            ->line('Retorno neto esperado: ' . MoneyFormatter::format($this->netExpectedReturn))
            ->line('ITF: ' . MoneyFormatter::format($this->itfAmount))
            ->action('Ver oportunidades', env('CLIENT_APP_URL','https://www.zuma.com.pe') . '/factoring/oportunidades')
            ->line('Gracias por usar nuestros servicios.');
    }
}
