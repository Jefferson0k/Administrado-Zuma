<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class InvestorEmailVerificationNotification extends VerifyEmail
{
    use Queueable;

    protected function verificationUrl($notifiable)
    {
        // 1) Create a RELATIVE signed URL (absolute=false)
        $relativePath = URL::temporarySignedRoute(
            'investor.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            false // 👈 relative signature (no scheme/host baked into the signature)
        );

        // 2) Prepend APP_URL so the email has a full absolute URL to click
        $root = rtrim(config('app.url'), '/'); // uses APP_URL (https://admin.zuma.com.pe)
        return $root . $relativePath;
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirma tu correo en ZUMA')
            ->view('emails.investor-verify', [
                'url'      => $verificationUrl,
                'investor' => $notifiable,
            ]);
    }
}

