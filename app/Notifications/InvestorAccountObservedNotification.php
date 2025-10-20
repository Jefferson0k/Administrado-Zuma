<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorAccountObservedNotification extends Notification
{
      use Queueable;

    public function __construct(
        public ?string $title = 'ZUMA - Tu usuario fue observado',
        public ?string $intro = 'Detectamos observaciones en tu cuenta que requieren tu atención para continuar con el proceso.',
        public array $reasons = [], // opcional, si tienes motivos específicos
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
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo-dark.png';
        $whatsUrl     = $this->whatsappUrl ?: 'https://wa.me/51986351267';
        $supportPhone = $this->supportPhone ?: '+51 986 351 267';

        return (new MailMessage)
            ->subject($this->title)
            ->view('emails.investor.observed', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,

                'title'        => $this->title,
                'intro'        => $this->intro,
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
