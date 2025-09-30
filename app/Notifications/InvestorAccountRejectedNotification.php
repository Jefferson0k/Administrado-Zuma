<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InvestorAccountRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ?string $supportPhone = null,
        protected ?string $whatsappUrl = null
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appName      = config('app.name', 'ZUMA');
        $clientUrl    = rtrim(env('CLIENT_APP_URL', 'http://localhost:5173'), '/');
        $loginUrl     = "{$clientUrl}/login";

        return (new MailMessage)
            ->subject("{$appName} — No pudimos validar tu registro")
            ->view('emails.investor-rejected', [
                'appName'      => $appName,
                'userName'     => $notifiable->name ?? 'Usuario',
                'loginUrl'     => $loginUrl,
                'supportPhone' => $this->supportPhone ?? '',
                'whatsappUrl'  => $this->whatsappUrl ?? '#',
            ]);
    }
}
