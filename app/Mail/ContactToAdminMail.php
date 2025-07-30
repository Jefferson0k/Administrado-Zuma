<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactToAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactData;

    public function __construct($contactData)
    {
        $this->contactData = $contactData;
    }

    public function build()
    {
        return $this->subject('Nueva solicitud de contacto - ' . $this->contactData['interested_product'])
                    ->view('emails.contact-to-admin')
                    ->with('data', $this->contactData);
    }
}
