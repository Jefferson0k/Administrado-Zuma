<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountApprovedNotification extends Notification
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
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $dashboardUrl = env('CLIENT_APP_URL', 'http://localhost:5173') . '/dashboard';

        return (new MailMessage)
            ->subject('¡Cuenta aprobada exitosamente! - ZUMA')
            ->line('¡Felicidades ' . $notifiable->name . '!')
            ->line('Tu cuenta ha sido validada y aprobada exitosamente.')
            ->line('Ya puedes acceder a todas las funcionalidades de la plataforma:')
            ->line('• Realizar inversiones')
            ->line('• Gestionar tu portafolio')
            ->line('• Realizar depósitos y retiros')
            ->action('Acceder a mi Dashboard', $dashboardUrl)
            ->line('¡Bienvenido a ZUMA! Estamos emocionados de tenerte como parte de nuestra comunidad de inversionistas.')
            ->line('Saludos, Equipo ZUMA');
    }
}