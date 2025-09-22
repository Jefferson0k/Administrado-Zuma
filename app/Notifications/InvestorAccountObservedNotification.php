<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountObservedNotification extends Notification
{
    use Queueable;

    protected $comment;

    public function __construct(string $comment)
    {
        $this->comment = $comment;
    }

    public function via($notifiable)
    {
        return ['mail']; // si quieres también puedes añadir 'database'
    }

    public function toMail($notifiable)
    {
        $dashboardUrl = env('CLIENT_APP_URL', 'http://localhost:5173') . '/dashboard';

        return (new MailMessage)
            ->subject('Cuenta de inversionista observada - ZUMA')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu cuenta de inversionista ha sido observada.')
            ->line('Motivo: ' . $this->comment)
            ->line('Por favor revisa la información y realiza las correcciones necesarias.')
            ->action('Ir al Dashboard', $dashboardUrl)
            ->line('Gracias por usar ZUMA.');
    }
}
