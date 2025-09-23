<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Security\RequestValidator;

class TwilioWebhookController extends Controller
{
    protected $verificationService;

    public function __construct(WhatsAppVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Manejar mensajes entrantes de WhatsApp
     */
    public function handleIncomingMessage(Request $request)
    {
        // Log del mensaje entrante para debugging
        Log::info('Incoming WhatsApp message webhook', $request->all());

        try {
            // Validar que la petición viene de Twilio (opcional pero recomendado en producción)
            if (!$this->validateTwilioSignature($request)) {
                Log::warning('Invalid Twilio signature for incoming message', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response('Unauthorized', 401);
            }

            // Obtener datos del webhook de Twilio
            $from = $request->input('From'); // Ej: whatsapp:+51905850578
            $body = trim($request->input('Body', '')); // El mensaje del usuario
            $messageSid = $request->input('MessageSid');
            $accountSid = $request->input('AccountSid');

            // Validar datos requeridos
            if (!$from || !$body) {
                Log::warning('Missing required data in WhatsApp webhook', [
                    'from' => $from,
                    'body' => $body,
                    'message_sid' => $messageSid
                ]);
                return response('Bad Request', 400);
            }

            // Validar que es un mensaje de WhatsApp
            if (!str_starts_with($from, 'whatsapp:')) {
                Log::info('Message not from WhatsApp channel', [
                    'from' => $from,
                    'message_sid' => $messageSid
                ]);
                return response('OK', 200);
            }

            // Extraer el número de teléfono
            $phoneNumber = str_replace('whatsapp:', '', $from);

            // Log de procesamiento
            Log::info('Processing WhatsApp verification response', [
                'from' => $phoneNumber,
                'body_length' => strlen($body),
                'message_sid' => $messageSid
            ]);

            // Procesar la respuesta de verificación
            $result = $this->verificationService->processVerificationResponse(
                $phoneNumber,
                $body,
                $messageSid
            );

            if ($result['success']) {
                Log::info('WhatsApp verification response processed successfully', [
                    'phone' => $phoneNumber,
                    'result' => $result
                ]);
            } else {
                Log::warning('Failed to process WhatsApp verification response', [
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }

            // Twilio espera una respuesta 200 para confirmar que recibimos el webhook
            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Exception processing incoming WhatsApp message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            // Aún así devolver 200 para que Twilio no reintente
            return response('OK', 200);
        }
    }

    /**
     * Manejar estados de mensaje (entregado, leído, failed, etc.)
     */
    public function handleMessageStatus(Request $request)
    {
        Log::info('WhatsApp message status update webhook', $request->all());

        try {
            // Validar firma de Twilio
            if (!$this->validateTwilioSignature($request)) {
                Log::warning('Invalid Twilio signature for status update');
                return response('Unauthorized', 401);
            }

            $messageSid = $request->input('MessageSid');
            $messageStatus = $request->input('MessageStatus'); // sent, delivered, read, failed, etc.
            $errorCode = $request->input('ErrorCode');
            $errorMessage = $request->input('ErrorMessage');

            // Log del estado del mensaje
            Log::info('WhatsApp message status processed', [
                'message_sid' => $messageSid,
                'status' => $messageStatus,
                'error_code' => $errorCode,
                'error_message' => $errorMessage
            ]);

            // Si el mensaje falló, podríamos hacer algo específico
            if ($messageStatus === 'failed' && $errorCode) {
                Log::warning('WhatsApp message delivery failed', [
                    'message_sid' => $messageSid,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage
                ]);

                // Aquí podrías implementar lógica para manejar mensajes fallidos
                // Por ejemplo, marcar al inversionista para reenvío manual
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
     * Endpoint manual para reenviar verificación (para soporte)
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'investor_id' => 'required|exists:investors,id'
        ]);

        try {
            $investor = \App\Models\Investor::findOrFail($request->investor_id);

            if (!$investor->telephone) {
                return response()->json([
                    'success' => false,
                    'error' => 'Investor has no telephone number'
                ], 400);
            }

            $result = $this->verificationService->resendVerificationMessage($investor);

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
     * Validar firma de Twilio (para producción)
     */
    private function validateTwilioSignature(Request $request): bool
    {
        // En desarrollo, puedes desactivar esta validación
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
     * Endpoint de salud para verificar que el webhook está funcionando
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'WhatsApp Verification Webhook',
            'timestamp' => now()->toISOString()
        ]);
    }
}