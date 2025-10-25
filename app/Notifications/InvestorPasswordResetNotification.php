<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class InvestorPasswordResetNotification extends ResetPassword
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        parent::__construct($token);
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Restablece tu contraseña - ZUMA')
            ->line('Hola ' . $notifiable->name)
            ->line('Has solicitado restablecer tu contraseña.')
            ->line('Haz clic en el botón de abajo para restablecer tu contraseña:')
            ->action('Restablecer Contraseña', $url)
            ->line('Este enlace de restablecimiento expirará en ' . config('auth.passwords.investors.expire') . ' minutos.')
            ->line('Si no solicitaste un restablecimiento de contraseña, puedes ignorar este email.')
            ->line('Saludos, Equipo ZUMA');
    }

    /**
     * Get the reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        // Construir la URL del frontend directamente
        $frontendUrl = env('CLIENT_APP_URL_FACTORING', 'https://zuma.com.pe') . '/factoring/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return $frontendUrl;
    }
}
