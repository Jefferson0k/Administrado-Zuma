<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestorWithdrawPendingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Withdraw $withdraw
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
        $withdrawAmount = MoneyFormatter::formatFromDecimal($this->withdraw->amount, $this->withdraw->currency);
        $withdrawDate = Carbon::parse($this->withdraw->created_at)->format('d/m/Y H:i');
        $moneda = $this->withdraw->currency === 'PEN' ? 'S/.' : '$';

        return (new MailMessage)
            ->subject('ZUMA - Retiro pendiente de aprobación')
            ->view('emails.withdraws.investorwithdrawpending', [
                'appName'      => $appName,
                'brandPrimary' => $brandPrimary,
                'brandButton'  => $brandButton,
                'logoUrl'      => $logoUrl,
                'userName'     => $notifiable->name ?? 'Usuario',
                'supportPhone' => $supportPhone,
                'withdrawAmount' => $withdrawAmount,
                'withdrawDate'  => $withdrawDate,
                'moneda'        => $moneda,
                'companyAddr'  => 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú',
                'footerYear'   => date('Y'),
            ]);
    }
}
