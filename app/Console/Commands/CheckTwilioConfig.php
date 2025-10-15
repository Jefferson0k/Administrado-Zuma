<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;

class CheckTwilioConfig extends Command
{
    protected $signature = 'twilio:check-config';
    protected $description = 'Check Twilio configuration';

    public function handle()
    {
        $whatsappService = app(WhatsAppService::class);
        
        $config = $whatsappService->checkConfiguration();
        
        $this->info('Twilio Configuration Check:');
        $this->line("SID configured: " . ($config['sid_configured'] ? '✅' : '❌'));
        $this->line("Token configured: " . ($config['token_configured'] ? '✅' : '❌'));
        $this->line("From configured: " . ($config['from_configured'] ? '✅' : '❌'));
        $this->line("From value: " . $config['from_value']);
        
        // Mostrar valores actuales del env
        $this->info('\nCurrent ENV values:');
        $this->line("TWILIO_SID: " . env('TWILIO_SID'));
        $this->line("TWILIO_AUTH_TOKEN: " . (env('TWILIO_AUTH_TOKEN') ? '***' : 'NULL'));
        $this->line("TWILIO_WHATSAPP_FROM: " . env('TWILIO_WHATSAPP_FROM'));
    }
}