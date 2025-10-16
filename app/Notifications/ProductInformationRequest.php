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
        $phone = preg_replace('/\D/', '', $this->contactData['phone']); // Limpia el número
        $whatsappUrl = "https://wa.me/51{$phone}"; // WhatsApp directo al cliente

        return (new MailMessage)
            ->from('admin@zuma.com.pe', 'ZUMA')
            ->subject('ZUMA - Nueva solicitud de inversión')
            ->line('Se ha recibido una nueva solicitud de información sobre inversiones:')
            ->line('Nombre: ' . $this->contactData['full_name'])
            ->line('Email: ' . $this->contactData['email'])
            ->line('Teléfono: ' . $this->contactData['phone'])
            ->line('Producto de interés: ' . $this->contactData['interested_product'])
            ->action('Contactar por WhatsApp', $whatsappUrl)
            ->line('Gracias por usar nuestra plataforma.');
    }
}
