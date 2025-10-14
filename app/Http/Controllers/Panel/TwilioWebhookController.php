<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Security\RequestValidator;

class TwilioWebhookController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
        
        // Log de diagnóstico
        Log::info('TwilioWebhookController initialized', [
            'twilio_sid' => config('services.twilio.sid') ? 'set' : 'null',
            'twilio_from' => config('services.twilio.whatsapp_from') ? 'set' : 'null'
        ]);
    }

    /**
     * 📩 Maneja los mensajes entrantes de WhatsApp.
     */
    public function handleIncomingMessage(Request $request)
    {
        Log::info('Incoming WhatsApp message webhook', $request->all());

        try {
            if (!$this->validateTwilioSignature($request)) {
                Log::warning('Invalid Twilio signature for incoming message', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response('Unauthorized', 401);
            }

            $from = $request->input('From');
            $body = trim($request->input('Body', ''));
            $messageSid = $request->input('MessageSid');

            if (!$from || !$body) {
                Log::warning('Missing required data in WhatsApp webhook', [
                    'from' => $from,
                    'body' => $body,
                    'message_sid' => $messageSid
                ]);
                return response('Bad Request', 400);
            }

            if (!str_starts_with($from, 'whatsapp:')) {
                Log::info('Message not from WhatsApp channel', ['from' => $from]);
                return response('OK', 200);
            }

            $phoneNumber = str_replace('whatsapp:', '', $from);
            $normalized = strtolower($body);

            Log::info("Processing WhatsApp message from {$phoneNumber}", ['message' => $body]);

            // 🔹 Detecta si el usuario respondió Sí o No
            if (in_array($normalized, ['si', 'sí', 'yes'])) {
                Log::info("✅ Confirmación positiva de {$phoneNumber}");

                // Opcional: buscar y actualizar el inversionista
                $investor = Investor::where('telephone', $phoneNumber)->first();
                if ($investor) {
                    $investor->confirmed = true;
                    $investor->save();
                    Log::info("Investor {$investor->id} confirmado correctamente.");
                }

                $this->whatsappService->sendMessage($phoneNumber, "Gracias por confirmar ✅");
            } elseif (in_array($normalized, ['no', 'nope'])) {
                Log::info("❌ Confirmación negativa de {$phoneNumber}");

                $investor = Investor::where('telephone', $phoneNumber)->first();
                if ($investor) {
                    $investor->confirmed = false;
                    $investor->save();
                    Log::info("Investor {$investor->id} rechazó la confirmación.");
                }

                $this->whatsappService->sendMessage($phoneNumber, "Hemos registrado su respuesta como negativa ❌");
            } else {
                Log::info("⚠️ Respuesta no válida de {$phoneNumber}", ['body' => $body]);
                $this->whatsappService->sendMessage($phoneNumber, "Por favor responda *Sí* o *No* para continuar.");
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Exception processing incoming WhatsApp message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response('OK', 200);
        }
    }

    /**
     * 📊 Maneja actualizaciones del estado de los mensajes (enviado, entregado, leído, fallido)
     */
    public function handleMessageStatus(Request $request)
    {
        Log::info('WhatsApp message status update webhook', $request->all());

        try {
            if (!$this->validateTwilioSignature($request)) {
                Log::warning('Invalid Twilio signature for status update');
                return response('Unauthorized', 401);
            }

            $messageSid = $request->input('MessageSid');
            $messageStatus = $request->input('MessageStatus');
            $errorCode = $request->input('ErrorCode');
            $errorMessage = $request->input('ErrorMessage');

            Log::info('WhatsApp message status processed', [
                'message_sid' => $messageSid,
                'status' => $messageStatus,
                'error_code' => $errorCode,
                'error_message' => $errorMessage
            ]);

            if ($messageStatus === 'failed' && $errorCode) {
                Log::warning('WhatsApp message delivery failed', [
                    'message_sid' => $messageSid,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage
                ]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Exception processing WhatsApp status update', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response('OK', 200);
        }
    }

    /**
     * 🔁 Reenvía mensaje de confirmación a un inversionista
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'investor_id' => 'required|exists:investors,id'
        ]);

        try {
            $investor = Investor::findOrFail($request->investor_id);

            if (!$investor->telephone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Investor has no telephone number'
                ], 400);
            }

            $message = "Hola {$investor->name} 👋, ¿usted confirma que desea continuar con el proceso de inversión? Responda *Sí* o *No*.";

            $result = $this->whatsappService->sendMessage($investor->telephone, $message);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Verification message sent successfully',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception resending verification', [
                'investor_id' => $request->investor_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * 🔐 Valida la firma de Twilio (en local se salta la validación)
     */
    private function validateTwilioSignature(Request $request): bool
    {
        if (app()->environment('local', 'development')) {
            return true;
        }

        $authToken = config('services.twilio.token');
        $twilioSignature = $request->header('X-Twilio-Signature');

        if (!$authToken || !$twilioSignature) {
            return false;
        }

        $validator = new RequestValidator($authToken);
        $url = $request->fullUrl();
        $postData = $request->all();

        return $validator->validate($twilioSignature, $url, $postData);
    }

    /**
     * 🩺 Endpoint de salud del servicio
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'WhatsApp Confirmation Webhook',
            'timestamp' => now()->toISOString()
        ]);
    }
}
