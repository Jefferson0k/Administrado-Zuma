<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactToUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data){
        $this->data = $data;
    }

    public function build(){
        return $this->subject('Gracias por contactarnos')
                    ->view('emails.contact_to_user')
                    ->with($this->data);
    }
}
