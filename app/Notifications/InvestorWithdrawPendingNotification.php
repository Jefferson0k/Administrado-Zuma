<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorWithdrawPendingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Withdraw $withdraw
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Retiro pendiente de aprobación')
            ->line('Hola ' . $notifiable->name)
            ->line('Hemos recibido tu solicitud de retiro.')
            ->line('Monto: ' . MoneyFormatter::formatFromDecimal($this->withdraw->amount))
            ->line('Fecha de retiro: ' . Carbon::parse($this->withdraw->created_at)->format('d/m/Y H:i'))
            ->line('Gracias por usar nuestros servicios.');
    }
}
