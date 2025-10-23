<?php

namespace App\Notifications;

use App\Models\BankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountActivateNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly BankAccount $bankAccount
    ) {
        //
    }

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
            ->subject('Zuma - Cuenta de Banco validada')
            ->view('emails.bankaccounts.approved', [
                'username' => $notifiable->name,
                'bankAccountbank' => $this->bankAccount->bank->name,
                'bankAccountAlias' => $this->bankAccount->alias,
                'moneda' => $this->bankAccount->currency,
                'userName' => $notifiable->name,
            ]);
        
            // ->line("Tu cuenta de banco {$this->bankAccount->bank->name} ({$this->bankAccount->currency}) ha sido validada.")
            // ->line('Realiza un depósito y empieza a invertir.')
            // ->action('Realizar depósito', config('app.client_app_url') . '/informacion-bancaria/estado-de-cuentas?isOpenModal=true')
            // ->line('Gracias por usar nuestros servicios.');
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
