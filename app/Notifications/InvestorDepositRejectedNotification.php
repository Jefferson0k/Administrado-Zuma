<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorDepositRejectedNotification extends Notification
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
            ->subject('ZUMA - Depósito rechazado')
            ->view('emails.deposits.rejected', 
            [
                'deposit' => $this->deposit, 
            'notifiable' => $notifiable,
            'fecha' => Carbon::now()->format('d/m/Y H:i'),
            'monto' => MoneyFormatter::formatFromDecimal($this->deposit->amount, $this->deposit->currency),
        ]);
            // ->line('Hola ' . $notifiable->name)
            // ->line('Tu depósito se ha rechazado.')
            // ->line('Monto: ' . MoneyFormatter::formatFromDecimal($this->deposit->amount))
            // ->line('Fecha de rechazo: ' . Carbon::now()->format('d/m/Y H:i'))
            // ->line('Motivo: ' . $this->deposit->description)
            // ->line('Gracias por usar nuestros servicios.');
    }
}
