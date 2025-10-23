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

class InvestorPartialPaymentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment,
        private readonly Investment $investment,
        private readonly Money $amountToPay,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago parcial creado')
            ->line('Hola ' . $notifiable->name)
            ->line('Tu pago parcial se ha creado correctamente.')
            ->line('Empresa: ' . $this->payment->invoice->company->name)
            ->line('Número de factura: ' . $this->payment->invoice->invoice_code)
            ->line('Fecha de pago: ' . Carbon::parse($this->payment->pay_date)->format('d/m/Y'))
            ->line('Fecha estimada de pago de reprogramación: ' . Carbon::parse($this->payment->reprogramation_date)->format('d/m/Y'))
            ->line('Tasa de reprogramación: ' . $this->payment->reprogramation_rate . '%')
            ->line('Monto invertido: ' . MoneyFormatter::formatFromDecimal($this->investment->amount))
            ->line('Monto a pagar: ' . MoneyFormatter::format($this->amountToPay))
            ->action('IR A ZUMA', env('CLIENT_APP_URL','https://www.zuma.com.pe'))
            ->line('Gracias por usar nuestros servicios.');
    }
}
