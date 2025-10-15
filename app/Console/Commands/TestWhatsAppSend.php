<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;

class TestWhatsAppSend extends Command
{
    protected $signature = 'whatsapp:test-send {phone} {message}';
    protected $description = 'Test WhatsApp sending';

    public function handle(WhatsAppService $whatsappService)
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');
        
        $this->info("Sending WhatsApp to: {$phone}");
        $this->info("Message: {$message}");
        
        $result = $whatsappService->sendMessage($phone, $message);
        
        if ($result['success']) {
            $this->info("✅ Message sent successfully! SID: {$result['sid']}");
        } else {
            $this->error("❌ Failed to send message: {$result['error']}");
        }
    }
}