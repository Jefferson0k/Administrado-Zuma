<?php

namespace App\Notifications;

use App\Models\Investment;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentRefundNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Investment $investment;
    protected Invoice $invoice;
    protected Payment $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Investment $investment, Invoice $invoice, Payment $payment)
    {
        $this->investment = $investment;
        $this->invoice = $invoice;
        $this->payment = $payment;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->payment->pay_type === 'reembloso'
            ? "💸 Reembolso registrado - Factura {$this->invoice->invoice_number}"
            : "📄 Pago registrado - Factura {$this->invoice->invoice_number}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line("Se ha registrado un {$this->payment->pay_type} asociado a la factura **{$this->invoice->invoice_number}**.")
            ->line("💰 Monto: {$this->investment->amount} {$this->investment->currency}")
            ->line("📅 Fecha: {$this->payment->pay_date}")
            ->line($this->investment->comment ? "📝 Comentario: {$this->investment->comment}" : '');

        if ($this->investment->receipt_path) {
            $mail->line("📎 El comprobante está disponible en la plataforma.");
        }

        $dashboardUrl = env('CLIENT_APP_URL', 'https://zuma.com.pe');
        $mail->action('Ir a ZUMA', $dashboardUrl);

        return $mail;
    }
}
