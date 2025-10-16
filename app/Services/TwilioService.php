<?php

namespace App\Services;

use Exception;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService {
    protected $client;
    protected $whatsappNumber;

    public function __construct(){
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        $this->whatsappNumber = config('services.twilio.whatsapp_number');
    }
    public function sendTemplateMessage($to, $templateName, $templateData = [], $language = 'es'){
        try {
            $message = $this->client->messages->create(
                "whatsapp:{$to}",
                [
                    'from' => "whatsapp:{$this->whatsappNumber}",
                    'contentSid' => $templateName,
                    'contentVariables' => json_encode($templateData),
                ]
            );
            Log::info("Plantilla WhatsApp enviada", [
                'to' => $to,
                'template' => $templateName,
                'sid' => $message->sid
            ]);
            return $message;
        } catch (Exception $e) {
            Log::error("Error enviando plantilla WhatsApp", [
                'to' => $to,
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    public function sendSimpleMessage($to, $body){
        try {
            $message = $this->client->messages->create(
                "whatsapp:{$to}",
                [
                    'from' => "whatsapp:{$this->whatsappNumber}",
                    'body' => $body
                ]
            );
            return $message;
        } catch (Exception $e) {
            Log::error("Error enviando mensaje simple WhatsApp", [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}