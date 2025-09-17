<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountObservedNotification extends Notification
{
    use Queueable;

    protected string $comment;

    public function __construct(string $comment)
    {
        $this->comment = $comment;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Observación en tu registro de cuenta')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu cuenta ha sido observada en el proceso de validación.')
            ->line('Comentario del validador:')
            ->line($this->comment)
            ->line('Por favor ingresa a la plataforma y actualiza la información requerida.')
            ->action('Acceder a la plataforma', url('/login'))
            ->line('Gracias por tu atención.');
    }
}

