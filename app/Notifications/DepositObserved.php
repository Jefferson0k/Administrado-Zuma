<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str; // optional
use Carbon\Carbon;

class DepositObserved extends Notification
{
    use Queueable;

    protected $deposit;
    protected ?string $customMessage;

    public function __construct($deposit, ?string $customMessage = null)
    {
        $this->deposit       = $deposit;
        $this->customMessage = $customMessage;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $dashboardUrl = rtrim(env('CLIENT_APP_URL', 'https://zuma.com.pe'), '/') ;
        $logoUrl      = rtrim(config('app.url', 'https://zuma.com.pe'), '/') . '/images/logo.png'; // public/images/logo.png

        $op     = $this->deposit->nro_operation ?? '—';
        $bank   = $this->deposit?->bankAccount?->bank?->name ?? '—';
        $amount = $this->formatAmount($this->deposit->amount ?? null);
        $curr   = $this->deposit->currency ?? '—';

        try {
            $date = $this->deposit->created_at
                ? Carbon::parse($this->deposit->created_at)->format('d/m/Y H:i')
                : '—';
        } catch (\Throwable $e) {
            $date = (string) ($this->deposit->created_at ?? '—');
        }

        $custom = $this->customMessage ?: 'Por favor revisa las observaciones y actualiza la información solicitada.';

        return (new MailMessage)
            ->subject('Depósito observado - ZUMA')
            ->markdown('emails.deposit_observed', [
                'name'         => $notifiable->name ?? '',
                'logoUrl'      => $logoUrl,
                'dashboardUrl' => $dashboardUrl,
                'op'           => $op,
                'bank'         => $bank,
                'amount'       => $amount,
                'curr'         => $curr,
                'date'         => $date,
                'custom'       => $custom,
            ]);
    }

    protected function formatAmount($amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }
        try {
            return number_format((float) $amount, 2, '.', ',');
        } catch (\Throwable $e) {
            return (string) $amount;
        }
    }

    // tiny escaper for HTML text nodes
    private function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
