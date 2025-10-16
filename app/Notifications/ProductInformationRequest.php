<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductInformationRequest extends Notification
{
    use Queueable;

    protected $contactData;

    public function __construct($contactData)
    {
        $this->contactData = $contactData;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Versión simple para testing
        return (new MailMessage)
            ->from('admin@zuma.com.pe', 'ZUMA')
            ->subject("ZUMA - Nueva solicitud: {$this->contactData['interested_product']}")
            ->line('Nueva solicitud de información recibida:')
            ->line('Nombre: ' . $this->contactData['full_name'])
            ->line('Email: ' . $this->contactData['email'])
            ->line('Teléfono: ' . $this->contactData['phone'])
            ->line('Producto: ' . $this->contactData['interested_product'])
            ->action('Contactar por WhatsApp', 'https://wa.me/51986351267')
            ->line('Gracias por usar nuestra aplicación!');
    }
}