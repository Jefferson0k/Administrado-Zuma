<?php
// 1. Primero, crea la nueva notificación
// App/Notifications/InvestorAccountRejectedNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountRejectedNotification extends Notification
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
        $loginUrl = env('CLIENT_APP_URL', 'http://localhost:5173') . '/login';

        return (new MailMessage)
            ->subject('Cuenta rechazada - Validación requerida - ZUMA')
            ->line('Hola ' . $notifiable->name)
            ->line('Tu cuenta ha sido rechazada durante el proceso de validación.')
            ->line('Para continuar con el proceso, necesitas completar nuevamente tus datos personales y subir las fotos de tu DNI de manera correcta.')
            ->line('Asegúrate de que:')
            ->line('• Las fotos del DNI sean claras y legibles')
            ->line('• Todos los datos estén completos y sean correctos')
            ->line('• Las imágenes estén en formato correcto (JPG, PNG)')
            ->action('Ingresar a mi cuenta', $loginUrl)
            ->line('Si tienes alguna duda, no dudes en contactarnos.')
            ->line('Saludos, Equipo ZUMA');
    }
}