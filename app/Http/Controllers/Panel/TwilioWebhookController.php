<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Twilio\TwiML\MessagingResponse;

class TwilioWebhookController extends Controller
{
    /**
     * Webhook que recibe las respuestas de WhatsApp de los usuarios
     */
    public function webhook(Request $request)
    {
        $fromRaw = $request->input('From'); // ej: whatsapp:+51987654321
        $body = strtoupper(trim($request->input('Body')));
        
        // Extraer solo los dígitos del número
        $telephone = preg_replace('/\D/', '', str_replace("whatsapp:", "", $fromRaw));
        
        Log::info("WhatsApp webhook recibido", [
            'from' => $fromRaw,
            'telephone' => $telephone,
            'body' => $body
        ]);
        
        // Buscar el inversor por teléfono
        $investor = Investor::where('telephone', $telephone)
            ->orWhere('telephone', '51' . $telephone)
            ->orWhere('telephone', substr($telephone, 2)) // Sin código de país
            ->first();
        
        $response = new MessagingResponse();
        
        if (!$investor) {
            Log::warning("Inversor no encontrado para el teléfono: $telephone");
            $response->message("❌ No encontramos tu registro. Por favor, regístrate primero en nuestra plataforma.");
            return response($response, 200)->header('Content-Type', 'text/xml');
        }
        
        // Validar si responde "SI" o "SÍ"
        if ($body === "SI" || $body === "SÍ") {
            $investor->update([
                'verified' => 1,
                'status_verified' => 'verified',
                'whatsapp_verified_at' => now()
            ]);
            
            Log::info("WhatsApp verificado exitosamente para inversor ID: {$investor->id}");
            
            $response->message("✅ ¡Perfecto! Tu número de WhatsApp ha sido confirmado exitosamente. Ya puedes acceder a tu cuenta. 🎉");
        } else {
            // Si responde otra cosa, mantener como pendiente
            Log::info("Respuesta no válida de inversor ID: {$investor->id}, respuesta: $body");
            $response->message("⚠️ Por favor responde con *SI* para confirmar tu número de WhatsApp.");
        }
        
        return response($response, 200)->header('Content-Type', 'text/xml');
    }
    
    /**
     * Endpoint para verificar el estado de verificación de un teléfono
     */
    public function checkPhone($telephoneRaw)
    {
        $telephone = preg_replace('/\D/', '', $telephoneRaw);
        
        $investor = Investor::where('telephone', $telephone)
            ->orWhere('telephone', '51' . $telephone)
            ->orWhere('telephone', substr($telephone, 2))
            ->first();
        
        if (!$investor) {
            return response()->json([
                'verified' => false,
                'status_verified' => 'not_found',
                'whatsapp_verified_at' => null
            ]);
        }
        
        return response()->json([
            'verified' => (bool) $investor->verified,
            'status_verified' => $investor->status_verified,
            'whatsapp_verified_at' => $investor->whatsapp_verified_at
        ]);
    }
}
