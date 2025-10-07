<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountObservedPepEvidenceNotification extends Notification
{
    use Queueable;
   public function __construct(
        public ?string $pepFormUrl   = null,
        public ?string $whatsappUrl  = null,
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
        $brandButton  = '#2563eb'; // botón "Formato PEP" (azul, como el mockup)
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo-dark.png';

        return (new MailMessage)
            ->subject('ZUMA - Completa el formato PEP para continuar con tu registro')
            ->view('emails.investor.observedpep', [
                // Marca
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,

                // Contenido
                'title'        => 'Completa el formato PEP',
                'userName'     => $notifiable->name ?? 'Usuario',
                'pepFormUrl'   => $this->pepFormUrl ?: 'https://tusitio.com/pep-formato.pdf',

                // Soporte & footer
                'whatsappUrl'  => $this->whatsappUrl ?: 'https://wa.me/51999999999',
                'supportPhone' => $this->supportPhone ?: '+51 999 999 999',
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'prefsUrl'     => rtrim(env('CLIENT_APP_URL', 'https://zuma.com.pe'), '/') . '/preferencias',
            ]);
    }
}
