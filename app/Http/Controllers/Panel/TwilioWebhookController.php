<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Twilio\TwiML\MessagingResponse;

class TwilioWebhookController extends Controller{
    public function webhook(Request $request){
        Log::info("=== INICIO WEBHOOK WHATSAPP ===", $request->all());
        $fromRaw = $request->input('From');
        $body = strtoupper(trim($request->input('Body')));
        $buttonPayload = $request->input('ButtonPayload');
        $buttonText = $request->input('ButtonText');
        $telephone = preg_replace('/\D/', '', str_replace("whatsapp:", "", $fromRaw));  
        Log::info("Datos recibidos del webhook", [
            'from_raw' => $fromRaw,
            'telephone_cleaned' => $telephone,
            'body' => $body,
            'button_payload' => $buttonPayload,
            'button_text' => $buttonText,
            'all_data' => $request->all()
        ]);
        
        $investor = Investor::where('telephone', $telephone)
            ->orWhere('telephone', '51' . $telephone)
            ->orWhere('telephone', substr($telephone, 2))
            ->first();
        
        $response = new MessagingResponse();
        
        if (!$investor) {
            Log::warning("❌ Inversor no encontrado para el teléfono", [
                'telephone_buscado' => $telephone,
                'formatos_intentados' => [
                    $telephone,
                    '51' . $telephone,
                    substr($telephone, 2)
                ]
            ]);
            
            $response->message("❌ No encontramos tu registro. Por favor, regístrate primero en nuestra plataforma.");
            return response($response, 200)->header('Content-Type', 'text/xml');
        }
        
        Log::info("✅ Inversor encontrado", [
            'investor_id' => $investor->id,
            'nombre' => $investor->name,
            'telephone_bd' => $investor->telephone,
            'verified_actual' => $investor->verified,
            'status_verified_actual' => $investor->status_verified
        ]);
        
        $isConfirmation = false;
        $isRejection = false;
        
        if ($buttonPayload === 'confirm_yes' || $buttonText === 'Sí, verificar') {
            $isConfirmation = true;
            Log::info("✅ Confirmación recibida por BOTÓN", [
                'tipo' => 'boton',
                'payload' => $buttonPayload,
                'text' => $buttonText
            ]);
        }
        else if (in_array($body, ['SI', 'SÍ', 'SÍ, VERIFICAR', 'SI, VERIFICAR', 'VERIFICAR'])) {
            $isConfirmation = true;
            Log::info("✅ Confirmación recibida por TEXTO", [
                'tipo' => 'texto',
                'body' => $body
            ]);
        }
        else if ($buttonPayload === 'confirm_no' || $buttonText === 'No verificar' || in_array($body, ['NO', 'NO VERIFICAR'])) {
            $isRejection = true;
            Log::info("❌ Rechazo recibido", [
                'tipo' => $buttonPayload ? 'boton' : 'texto',
                'payload' => $buttonPayload,
                'text' => $buttonText,
                'body' => $body
            ]);
        }
        
        if ($isConfirmation) {
            try {
                // ACTUALIZAR LOS CAMPOS DE VERIFICACIÓN
                $updateData = [
                    'verified' => 1,
                    'status_verified' => 'verified',
                    'whatsapp_verified_at' => now()
                ];
                
                $investor->update($updateData);
                
                // Recargar el modelo para ver los cambios
                $investor->refresh();
                
                Log::info("🎉 WhatsApp VERIFICADO exitosamente", [
                    'investor_id' => $investor->id,
                    'verified_nuevo' => $investor->verified,
                    'status_verified_nuevo' => $investor->status_verified,
                    'whatsapp_verified_at' => $investor->whatsapp_verified_at
                ]);
                
                $response->message("✅ ¡Perfecto! Tu número de WhatsApp ha sido confirmado exitosamente. Ya puedes acceder a tu cuenta. 🎉");
                
            } catch (\Exception $e) {
                Log::error("❌ Error actualizando verificación", [
                    'investor_id' => $investor->id,
                    'error' => $e->getMessage()
                ]);
                
                $response->message("⚠️ Ocurrió un error al verificar tu cuenta. Por favor, intenta nuevamente.");
            }
            
        } else if ($isRejection) {
            try {
                // ACTUALIZAR COMO RECHAZADO
                $updateData = [
                    'verified' => 0,
                    'status_verified' => 'rejected',
                    'whatsapp_verified_at' => null
                ];
                
                $investor->update($updateData);
                $investor->refresh();
                
                Log::info("📝 Verificación RECHAZADA por usuario", [
                    'investor_id' => $investor->id,
                    'verified_nuevo' => $investor->verified,
                    'status_verified_nuevo' => $investor->status_verified
                ]);
                
                $response->message("⚠️ Has decidido no verificar tu número por ahora. Podrás hacerlo más tarde desde tu cuenta.");
                
            } catch (\Exception $e) {
                Log::error("❌ Error actualizando rechazo", [
                    'investor_id' => $investor->id,
                    'error' => $e->getMessage()
                ]);
                
                $response->message("⚠️ Ocurrió un error al procesar tu respuesta.");
            }
            
        } else {
            // Respuesta no válida
            Log::info("⚠️ Respuesta no válida recibida", [
                'investor_id' => $investor->id,
                'body' => $body,
                'button_payload' => $buttonPayload,
                'button_text' => $buttonText
            ]);
            
            $response->message("⚠️ Por favor responde con *SI* o selecciona *Sí, verificar* para confirmar tu número de WhatsApp.");
        }
        
        Log::info("=== FIN WEBHOOK WHATSAPP ===");
        
        return response($response, 200)->header('Content-Type', 'text/xml');
    }
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

    public function statusCallback(Request $request)
{
    Log::info("📱 WhatsApp Status Update:", $request->all());
    return response()->json(['status' => 'received'], 200);
}
}
