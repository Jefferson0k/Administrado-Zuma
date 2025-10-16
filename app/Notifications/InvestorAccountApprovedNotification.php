<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InvestorAccountApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ?string $whatsappUrl = null,
        protected ?string $supportPhone = null
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appName   = config('app.name', 'ZUMA');
        $clientUrl = rtrim(env('CLIENT_APP_URL', 'https://zuma.com.pe'), '/');
        $dashboard = "{$clientUrl}/dashboard";

        return (new MailMessage)
            ->subject("{$appName} — ¡Tu cuenta fue aprobada!")
            ->view('emails.investor-approved', [
                'appName'      => $appName,
                'userName'     => $notifiable->name ?? 'Usuario',
                'dashboardUrl' => $dashboard,
                'whatsappUrl'  => $this->whatsappUrl ?? '#',
                'supportPhone' => $this->supportPhone ?? '+51 986 351 267',
            ]);
    }
}
