<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactToSecondaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data){
        $this->data = $data;
    }

    public function build(){
        return $this->subject('Copia: Solicitud de contacto recibida')
                    ->view('emails.contact_to_secondary')
                    ->with('data', $this->data);
    }
}
