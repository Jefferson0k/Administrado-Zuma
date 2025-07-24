<?php

namespace App\Services;

use Resend;

class ResendService{
    protected $client;
    public function __construct(){
        $this->client = Resend::client(env('RESEND_API_KEY'));
    }
    public function send($to, $subject, $html){
        return $this->client->emails->send([
            'from' => 'Zuma <admin@zuma.com.pe>',
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
        ]);
    }
}
