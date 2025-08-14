<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class InvestorEmailVerificationNotification extends VerifyEmail
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
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            false
        );
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        try {
            $verificationUrl = $this->verificationUrl($notifiable);

            // Extraer solo la parte de la ruta (sin el dominio)
            $path = parse_url($verificationUrl, PHP_URL_PATH);
            $query = parse_url($verificationUrl, PHP_URL_QUERY);

            // Construir la URL del frontend Vue
            $frontendUrl = env('CLIENT_APP_URL', 'https://zuma.com.pe') . '/email/verify/' . '?' . parse_url($verificationUrl, PHP_URL_QUERY);
            $frontendUrl = env('CLIENT_APP_URL', 'https://zuma.com.pe') . '/email/verify/';
            if ($query) {
                $frontendUrl .= '?' . $query;
            }

            // Verificar si la vista existe, si no usar MailMessage simple
            if (view()->exists('emails.investor-verify')) {
                return (new MailMessage)
                    ->subject('Confirma tu correo en ZUMA')
                    ->view(
                        'emails.investor-verify',
                        [
                            'url' => $frontendUrl,
                            'investor' => $notifiable,
                        ]
                    );
            } else {
                // Fallback a MailMessage simple si la vista no existe
                return (new MailMessage)
                    ->subject('Confirma tu correo en ZUMA')
                    ->greeting('¡Hola ' . $notifiable->name . '!')
                    ->line('Gracias por actualizar tu información en nuestra plataforma.')
                    ->line('Para continuar usando todas las funcionalidades, por favor verifica tu nueva dirección de email.')
                    ->action('Verificar Email', $frontendUrl)
                    ->line('Este enlace expirará en ' . config('auth.verification.expire', 60) . ' minutos.')
                    ->line('Si no realizaste este cambio, contacta a soporte.')
                    ->salutation('Saludos, Equipo ZUMA');
            }
        } catch (\Exception $e) {
            \Log::error('Error building email verification notification: ' . $e->getMessage());
            throw $e;
        }
    }
}