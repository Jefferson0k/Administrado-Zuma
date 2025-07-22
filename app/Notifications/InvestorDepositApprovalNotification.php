<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorDepositApprovalNotification extends Notification
{
    use Queueable;
    public function __construct(
        public readonly Deposit $deposit,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Depósito aprobado')
            ->line('Hola ' . $notifiable->name)
            ->line('Tu depósito con número de operación ' . $this->deposit->nro_operation . ' se ha aprobado correctamente.')
            ->line('Monto: ' . MoneyFormatter::formatFromDecimal($this->deposit->amount))
            ->line('Fecha de depósito: ' . Carbon::parse($this->deposit->created_at)->format('d/m/Y H:i'))
            ->action('Ver oportunidades', config('app.client_app_url') . '/oportunidades')
            ->line('Gracias por usar nuestros servicios.');
    }
}
