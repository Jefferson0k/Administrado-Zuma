<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorWithdrawApprovedNotification extends Notification
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
            ->subject('ZUMA - Retiro aprobado')
            ->view('emails.withdraws.approved', [
                'notifiable' => $notifiable,
                'withdraw' => $this->withdraw,
                'monto' => MoneyFormatter::formatFromDecimal($this->withdraw->amount, $this->withdraw->currency),
                'fecha' => Carbon::parse($this->withdraw->created_at)->format('d/m/Y H:i'),

            ]);
            // ->line('Hola ' . $notifiable->name)
            // ->line('Tu retiro se ha aprobado correctamente.')
            // ->line('Monto: ' . MoneyFormatter::formatFromDecimal($this->withdraw->amount))
            // ->line('Fecha de aprobación: ' . Carbon::now()->format('d/m/Y H:i'))
            // ->line('Número de operación: ' . $this->withdraw->nro_operation)
            // ->line('Fecha de pago: ' . Carbon::parse($this->withdraw->deposit_pay_date)->format('d/m/Y'))
            // ->line('Gracias por usar nuestros servicios.');
    }
}
