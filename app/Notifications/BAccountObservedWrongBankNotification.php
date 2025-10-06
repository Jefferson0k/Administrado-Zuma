<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BAccountObservedWrongBankNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName      = config('app.name', 'ZUMA');
        $brandPrimary = '#F0372D'; // barra/logo (rojo)
        $brandButton  = '#3B82F6'; // botón (azul)
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo-dark.png';
        $whatsUrl     = env('ZUMA_WHATSAPP_URL', 'https://wa.me/51999999999');
        $supportPhone = env('ZUMA_SUPPORT_PHONE', '+51 999 999 999');

        return (new MailMessage)
            ->subject('ZUMA - Registraste el nombre de otra entidad bancaria en tu formulario')
            ->view('emails.bankaccounts.wrong_bank_notification', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,
                'userName'     => $notifiable->name ?? 'Nombre del usuario',
                'ctaUrl'       => env('CLIENT_APP_URL', 'https://zuma.com.pe'),
                'whatsappUrl'  => $whatsUrl,
                'supportPhone' => $supportPhone,
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'footerYear'   => date('Y'),
                'prefsUrl'     => env('ZUMA_EMAIL_PREFS_URL', '#'),
            ]);
    }
}
