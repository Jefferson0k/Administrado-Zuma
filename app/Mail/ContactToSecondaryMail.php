<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactToSecondaryMail extends Mailable{
    use Queueable, SerializesModels;
    public $contactData;
    public function __construct($contactData){
        $this->contactData = $contactData;
    }
    public function build(){
        return $this->subject('Nueva solicitud de contacto')
                    ->view('emails.contact-to-secondary')
                    ->with('data', $this->contactData);
    }
}