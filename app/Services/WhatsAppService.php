<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService{
    // En WhatsAppService.php, agrega esta validación:
public function sendMessage(string $to, string $message): array
{
    try {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from');

        if (!$sid || !$token || !$from) {
            throw new \Exception('Twilio configuration incomplete');
        }

        $client = new Client($sid, $token);

        // ✅ VALIDACIÓN MEJORADA DEL FORMATO DEL NÚMERO
        $to = $this->formatPhoneNumber($to);

        Log::info('Enviando WhatsApp con validación mejorada', [
            'to_final' => $to,
            'from' => $from,
            'message_preview' => substr($message, 0, 100) . '...'
        ]);

        $msg = $client->messages->create($to, [
            'from' => $from,
            'body' => $message
        ]);

        Log::info('WhatsApp API Response', [
            'sid' => $msg->sid,
            'status' => $msg->status,
            'error_code' => $msg->errorCode ?? 'none',
            'error_message' => $msg->errorMessage ?? 'none',
            'to' => $to
        ]);

        return ['success' => true, 'sid' => $msg->sid, 'status' => $msg->status];
        
    } catch (\Exception $e) {
        Log::error('WhatsApp Service Error', [
            'error' => $e->getMessage(),
            'to' => $to,
            'trace' => $e->getTraceAsString()
        ]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Formatea correctamente el número de teléfono para WhatsApp
 */
private function formatPhoneNumber(string $phone): string
{
    // Si ya tiene formato whatsapp:, mantenerlo
    if (str_starts_with($phone, 'whatsapp:')) {
        $number = str_replace('whatsapp:', '', $phone);
        
        // Si no tiene código de país, agregar +51 (Perú)
        if (!str_starts_with($number, '+')) {
            $number = '+51' . $number;
        }
        
        return 'whatsapp:' . $number;
    }
    
    // Si no tiene formato, agregar whatsapp: y +51
    if (!str_starts_with($phone, '+')) {
        $phone = '+51' . $phone;
    }
    
    return 'whatsapp:' . $phone;
}
}