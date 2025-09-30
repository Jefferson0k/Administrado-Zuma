<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Invoice;
use App\Models\Investment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceAnnulledRefundNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Investment $investment,
        public readonly ?string $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Reembolso por Anulación de Factura #' . $this->invoice->id)
            ->line('Hola ' . $notifiable->name . ',')
            ->line('Te informamos que la factura #' . $this->invoice->id . ' ha sido anulada y tu inversión ha sido reembolsada.')
            ->line('**Detalles del reembolso:**')
            ->line('• Monto reembolsado: ' . MoneyFormatter::formatFromDecimal($this->investment->amount, $this->investment->currency))
            ->line('• Retorno esperado cancelado: ' . MoneyFormatter::formatFromDecimal($this->investment->return, $this->investment->currency))
            ->line('• Fecha de anulación: ' . Carbon::now()->format('d/m/Y H:i'));

        if ($this->comment) {
            $mail->line('**Motivo de anulación:**')
                 ->line($this->comment);
        }

        return $mail->line('El monto ha sido devuelto a tu saldo disponible.')
                    ->line('Gracias por tu comprensión.');
    }
}