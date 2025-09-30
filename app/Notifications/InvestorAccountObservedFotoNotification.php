<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountObservedFotoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?string $title = 'Registro - Validación facial por baja calidad',
        public array $reasons = [
            'Usa buena iluminación (de preferencia luz natural).',
            'Asegúrate de que tu rostro esté enfocado y visible completamente.',
            'Evita sombras, accesorios o filtros.',

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
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo-dark.png'; // usa URL absoluta
        $whatsUrl     = $this->whatsappUrl ?: 'https://wa.me/51999999999';
        $supportPhone = $this->supportPhone ?: '+51 999 999 999';

        // Se envía una vista Blade en lugar de texto plano.
        return (new MailMessage)
            ->subject($this->title)
            ->view('emails.investor.observedfoto', [
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
                'prefsUrl'     => rtrim(env('CLIENT_APP_URL', 'https://zuma.com.pe'), '/') . '/preferencias', // “Gestionar preferencias”
                'footerYear'   => date('Y'),
            ]);
    }
}
