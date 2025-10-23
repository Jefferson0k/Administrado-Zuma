<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRequestNotification extends Notification
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
        // Limpieza del número (solo dígitos)
        $phone = preg_replace('/\D/', '', $this->contactData['phone']);

        // Si el número no empieza con "51", se asume Perú
        $phoneWithCode = str_starts_with($phone, '51') ? $phone : "51{$phone}";

        // Enlace a WhatsApp del cliente
        $whatsappUrl = "https://wa.me/{$phoneWithCode}";

        return (new MailMessage)
            ->from(config('mail.from.address', 'notificaciones@zuma.com.pe'), config('mail.from.name', 'Zuma Inversiones'))
            ->subject('ZUMA - Nueva solicitud de préstamo')
            ->line('Se ha recibido una nueva solicitud de préstamo:')
            ->line('Nombre: ' . $this->contactData['full_name'])
            ->line('Email: ' . $this->contactData['email'])
            ->line('Teléfono: ' . $this->contactData['phone'])
            ->line('Monto o producto de interés: ' . $this->contactData['interested_product'])
            ->line('Mensaje: ' . ($this->contactData['message'] ?? '(Sin mensaje)'))
            ->line('Aceptó políticas: ' . (!empty($this->contactData['accepted_policy']) ? 'Sí' : 'No'))
            ->action('Contactar por WhatsApp', $whatsappUrl)
            ->line('Gracias por usar nuestra plataforma.');
    }
}

