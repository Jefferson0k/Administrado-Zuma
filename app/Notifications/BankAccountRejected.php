<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankAccountRejected extends Notification
{
    use Queueable;

    protected $bankAccount;

    /**
     * Create a new notification instance.
     */
    public function __construct($bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $dashboardUrl = env('CLIENT_APP_URL', default: 'https://zuma.com.pe');

        return (new MailMessage)
            ->subject('ZUMA - Cuenta bancaria rechazada')
            ->view('emails.bankaccounts.rejected', [
                'notifiable' => $notifiable,
                'bankAccount' => $this->bankAccount,
                'dashboardUrl' => $dashboardUrl,
                'userName' => $notifiable->name,
            ]);
    }
}
