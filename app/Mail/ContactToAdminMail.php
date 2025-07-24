<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactToAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data){
        $this->data = $data;
    }

    public function build(){
        return $this->subject('Nueva solicitud de contacto')
                    ->view('emails.contact_to_admin')
                    ->with('data', $this->data);
    }
}
