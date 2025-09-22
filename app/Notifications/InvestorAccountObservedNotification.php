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
            ->subject('Observación en tu registro de usuario')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu usuario ha sido observado en el proceso de validación.')
            ->line('Comentario del validador:')
            ->line($this->comment)
            ->line('Por favor ingresa a la plataforma y actualiza la información requerida.')
            ->action('Acceder a la plataforma', url('/login'))
            ->line('Gracias por tu atención.');
    }
}
