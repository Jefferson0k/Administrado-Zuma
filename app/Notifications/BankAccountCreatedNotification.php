<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankAccountCreatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
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
        $appName      = config('app.name', 'ZUMA');
        $brandPrimary = '#F0372D'; // rojo corporativo
        $brandButton  = '#3B82F6'; // azul CTA
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo.png';
        $supportPhone = env('SUPPORT_PHONE', '+51 986 351 267');

        return (new MailMessage)
            ->subject('ZUMA – Tu cuenta bancaria está en proceso de validación')
            ->view('emails.bankaccounts.bankaccountcreated', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,
                'userName'     => $notifiable->name ?? 'Usuario',
                'supportPhone' => $supportPhone,
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'footerYear'   => date('Y'),
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
