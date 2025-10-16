<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public array $data)
    {
    }

    public function build(): self
    {
        return $this->subject('Nueva solicitud de contacto - ' . config('app.name'))
                    ->markdown('emails.contact-request', [
                        'data' => $this->data
                    ]);
    }
}