<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BAccountObservedJointAccountNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName      = config('app.name', 'ZUMA');
        $brandPrimary = '#F0372D'; // rojo ZUMA
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo-dark.png';
        $whatsUrl     = env('ZUMA_WHATSAPP_URL', 'https://wa.me/51999999999');
        $supportPhone = env('ZUMA_SUPPORT_PHONE', '+51 999 999 999');
        $prefsUrl     = env('ZUMA_EMAIL_PREFS_URL', '#');

        return (new MailMessage)
            ->subject('ZUMA - Verificación de tu cuenta bancaria mancomunada')
            ->view('emails.bankaccounts.joint_account_verification', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'logoUrl'      => $logoUrl,
                'userName'     => $notifiable->name ?? 'Usuario',
                'whatsappUrl'  => $whatsUrl,
                'supportPhone' => $supportPhone,
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'footerYear'   => date('Y'),
                'prefsUrl'     => $prefsUrl,
            ]);
    }
}
