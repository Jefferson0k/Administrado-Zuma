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
     * Get the verification email notification mail message for the given URL.
     *
     * @param  string  $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Verifica tu dirección de email - zuma')
            ->greeting('¡Hola!')
            ->line('Gracias por registrarte en nuestra plataforma de inversiones.')
            ->line('Para completar tu registro y acceder a todas las funcionalidades, por favor verifica tu dirección de email.')
            ->action('Verificar Email', $url)
            ->line('Este enlace de verificación expirará en ' . config('auth.verification.expire', 60) . ' minutos.')
            ->line('Si no creaste esta cuenta, puedes ignorar este email.')
            ->salutation('Saludos, Equipo Zuma');
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
            'investor.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            false // No incluir el dominio actual
        );
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function toMail($notifiable)
    {
        // $verificationUrl = $this->verificationUrl($notifiable);

        // // Extraer solo la parte de la ruta (sin el dominio)
        // $path = parse_url($verificationUrl, PHP_URL_PATH);
        // $query = parse_url($verificationUrl, PHP_URL_QUERY);

        // // Construir la URL del frontend
        // $frontendUrl = env('CLIENT_APP_URL', 'http://localhost:5173') . $path;
        // if ($query) {
        //     $frontendUrl .= '?' . $query;
        // }

        return (new MailMessage)
            ->subject('Gracias por tu interés en ZUMA | Estamos casi listos para ti')
            ->view('emails.investor-verify', [
                'investor'         => $notifiable,
                'appName'          => config('app.name', 'ZUMA'),
                // Si quieres usar el enlace de verificación dentro del Blade:
                'verificationUrl'  => $this->verificationUrl($notifiable),
            ]);
    }
}
