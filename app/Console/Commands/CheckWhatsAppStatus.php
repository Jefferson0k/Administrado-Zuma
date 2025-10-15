<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class CheckWhatsAppStatus extends Command
{
    protected $signature = 'whatsapp:check-status {sid}';
    protected $description = 'Check WhatsApp message status and details';

    public function handle()
    {
        $sid = $this->argument('sid');
        
        $twilioSid = config('services.twilio.sid');
        $twilioToken = config('services.twilio.token');
        
        $this->info("🔍 Checking WhatsApp message status...");
        $this->line("Message SID: {$sid}");
        $this->line("Twilio SID: {$twilioSid}");
        
        if (!$twilioSid || !$twilioToken) {
            $this->error('Twilio configuration missing!');
            return;
        }
        
        try {
            $client = new Client($twilioSid, $twilioToken);
            $message = $client->messages($sid)->fetch();
            
            $this->info("\n✅ MESSAGE FOUND:");
            $this->line("SID: " . $message->sid);
            $this->line("Status: " . $message->status);
            $this->line("To: " . $message->to);
            $this->line("From: " . $message->from);
            $this->line("Body: " . ($message->body ?? 'N/A'));
            $this->line("Direction: " . ($message->direction ?? 'N/A'));
            $this->line("Price: " . ($message->price ?? 'N/A'));
            $this->line("Error Code: " . ($message->errorCode ?? 'None'));
            $this->line("Error Message: " . ($message->errorMessage ?? 'None'));
            $this->line("Date Created: " . ($message->dateCreated ? $message->dateCreated->format('Y-m-d H:i:s') : 'N/A'));
            $this->line("Date Sent: " . ($message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : 'Not sent'));
            $this->line("Date Updated: " . ($message->dateUpdated ? $message->dateUpdated->format('Y-m-d H:i:s') : 'N/A'));
            
            // Interpretación del estado
            $this->info("\n📊 STATUS INTERPRETATION:");
            switch ($message->status) {
                case 'queued':
                    $this->line("🟡 Message is queued - waiting to be sent");
                    break;
                case 'sent':
                    $this->line("🟢 Message sent to carrier - may still be delivered");
                    break;
                case 'delivered':
                    $this->line("✅ Message delivered to phone");
                    break;
                case 'undelivered':
                    $this->line("❌ Message undelivered - check error code");
                    break;
                case 'failed':
                    $this->line("💥 Message failed - check error code");
                    break;
                default:
                    $this->line("ℹ️ Status: " . $message->status);
            }
            
            if ($message->errorCode) {
                $this->error("\n❌ ERROR DETAILS:");
                $this->line("Code: " . $message->errorCode);
                $this->line("Message: " . $message->errorMessage);
                
                // Common error interpretations
                $commonErrors = [
                    30007 => 'BLOCKED: Message blocked by carrier/country',
                    21408 => 'BLOCKED: Permission to send a message has not been granted',
                    21610 => 'BLOCKED: Cannot send to this number',
                    63032 => 'WHATSAPP: Template not approved or missing',
                    21211 => 'INVALID: Invalid phone number format',
                    21612 => 'BLOCKED: Cannot send to this number (blocked)',
                ];
                
                if (isset($commonErrors[$message->errorCode])) {
                    $this->line("Meaning: " . $commonErrors[$message->errorCode]);
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error fetching message: " . $e->getMessage());
            Log::error('Error checking WhatsApp status', [
                'sid' => $sid,
                'error' => $e->getMessage()
            ]);
        }
    }
}