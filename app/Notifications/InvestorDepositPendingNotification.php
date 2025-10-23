<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorDepositPendingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Deposit $deposit
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {

        $appName      = config('app.name', 'ZUMA');
        $brandPrimary = '#F0372D'; // rojo corporativo
        $brandButton  = '#3B82F6'; // azul CTA
        $logoUrl      = rtrim(env('APP_URL', ''), '/') . '/images/zuma-logo.png';
        $supportPhone = env('SUPPORT_PHONE', '+51 986 351 267');
        $depositAmount = MoneyFormatter::formatFromDecimal($this->deposit->amount, $this->deposit->currency);
        $depositDate = Carbon::parse($this->deposit->created_at)->format('d/m/Y H:i');
        $moneda = $this->deposit->currency === 'PEN' ? 'S/.' : '$';


        return (new MailMessage)
            ->subject('ZUMA – Depósito registrado, en proceso de validación')
            ->view('emails.deposits.investordepositpending', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,
                'userName'     => $notifiable->name ?? 'Usuario',
                'supportPhone' => $supportPhone,
                'depositAmount' => $depositAmount,
                'depositDate'  => $depositDate,
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'footerYear'   => date('Y'),
            ]);
    }
}
