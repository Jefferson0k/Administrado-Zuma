<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountObservedPepEvidenceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?string $title = 'Necesitamos validar tu evidencia PEP',
        public array $reasons = [
            'La imagen está borrosa o poco legible.',
            'El documento no permite verificar el cargo/relación PEP declarado.',
        ],
        public ?string $whatsappUrl = null,
        public ?string $supportPhone = null,
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appName      = config('app.name', 'ZUMA');
        $brandPrimary = '#F0372D'; // barra/logo bg (rojo)
        $brandButton  = '#22c55e'; // botón WhatsApp (verde)
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo-dark.png'; // URL absoluta
        $whatsUrl     = $this->whatsappUrl ?: 'https://wa.me/51999999999';
        $supportPhone = $this->supportPhone ?: '+51 999 999 999';

        return (new MailMessage)
            ->subject($this->title)
            ->view('emails.investor.observedpep', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,

                'title'        => $this->title,
                'userName'     => $notifiable->name ?? 'Usuario',
                'reasons'      => $this->reasons,

                'whatsappUrl'  => $whatsUrl,
                'supportPhone' => $supportPhone,
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'prefsUrl'     => rtrim(env('CLIENT_APP_URL', 'https://zuma.com.pe'), '/') . '/preferencias',
                'footerYear'   => date('Y'),
            ]);
    }
}
