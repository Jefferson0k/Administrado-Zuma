<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankAccountObserved extends Notification
{
    use Queueable;

    protected $bankAccount;
    protected ?string $customMessage;

    /**
     * @param  mixed       $bankAccount
     * @param  string|null $customMessage  // <-- comes from frontend (request->comment)
     */
    public function __construct($bankAccount, ?string $customMessage = null)
    {
        $this->bankAccount   = $bankAccount;
        $this->customMessage = $customMessage;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $dashboardUrl = env('CLIENT_APP_URL', 'https://zuma.com.pe') . '/dashboard';

        return (new MailMessage)
            ->subject('Cuenta bancaria observada - ZUMA')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu cuenta bancaria con alias "' . ($this->bankAccount->alias ?? '—') . '" ha sido observada.')
            // Línea que usa tu mensaje desde el frontend:
            ->line($this->customMessage ?: 'Por favor revisa los comentarios y actualiza la información solicitada.')
            ->action('Ir al Dashboard', $dashboardUrl)
            ->line('Si tienes dudas, contacta a soporte. ¡Gracias por usar ZUMA!');
    }
}
