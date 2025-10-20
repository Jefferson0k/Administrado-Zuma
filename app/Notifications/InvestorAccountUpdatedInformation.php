<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountUpdatedInformation extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName      = config('app.name', 'ZUMA');
        $brandPrimary = '#F0372D'; // rojo corporativo
        $brandButton  = '#3B82F6'; // azul CTA
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo.png';
        $supportPhone = env('SUPPORT_PHONE', '+51 986 351 267');

        return (new MailMessage)
            ->subject('ZUMA – Tu perfil de inversionista está en proceso de validación')
            ->view('emails.investor.investorupdatedinformation', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,
                'userName'     => $notifiable->name ?? 'Usuario',
                'supportPhone' => $supportPhone,
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'footerYear'   => date('Y'),
            ]);
    }
}
