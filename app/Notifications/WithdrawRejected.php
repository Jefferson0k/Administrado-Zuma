<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Helpers\MoneyFormatter;
use App\Models\Withdraw;
use Carbon\Carbon;


class WithdrawRejected extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Withdraw $withdraw
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

         return (new MailMessage)
            ->subject('ZUMA - Retiro rechazado')
            ->view('emails.withdraws.rejected', [
                'notifiable' => $notifiable,
                'withdraw' => $this->withdraw,
                'monto' => MoneyFormatter::formatFromDecimal($this->withdraw->amount, $this->withdraw->currency),
                'fecha' => Carbon::parse($this->withdraw->created_at)->format('d/m/Y H:i'),

            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
