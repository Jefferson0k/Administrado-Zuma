<?php

namespace App\Services;

use App\Models\Investor;
use Twilio\Rest\Client;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhatsAppVerificationService
{
    protected $twilio;
    protected $from;

    public function __construct()
{
    $sid = config('services.twilio.sid');
    $token = config('services.twilio.token');
    $from = config('services.twilio.whatsapp_number');

    if ($sid && $token && $from) {
        // Evita duplicar el prefijo whatsapp:
        if (str_starts_with($from, 'whatsapp:')) {
            $this->from = $from;
        } else {
            $this->from = 'whatsapp:' . $from;
        }

        $this->twilio = new Client($sid, $token);
    } else {
        $this->twilio = null;
        $this->from = null;
        Log::error('Twilio configuration missing', [
            'sid' => $sid,
            'token' => $token ? '***' : null,
            'from' => $from,
        ]);
    }
}



    /**
     * Enviar mensaje de verificación automático cuando se registra un inversionista
     */
    public function sendVerificationMessage(Investor $investor)
    {
        try {
            // 🔒 Verifica que Twilio esté correctamente inicializado
            if (!$this->twilio || !$this->from) {
                Log::error('Twilio client not initialized properly', [
                    'twilio' => $this->twilio,
                    'from' => $this->from,
                ]);
                return [
                    'success' => false,
                    'error' => 'Twilio client not initialized properly',
                ];
            }

            // 📱 Verifica que el inversor tenga teléfono válido
            if (!$investor->telephone) {
                Log::warning('Investor has no telephone number', [
                    'investor_id' => $investor->id,
                ]);
                return [
                    'success' => false,
                    'error' => 'Investor has no telephone number',
                ];
            }

            // 🔢 Generar código de verificación
            $verificationCode = $this->generateVerificationCode();

            // 💾 Guardar el código temporalmente
            $investor->verification_code = $verificationCode;
            $investor->verification_sent_at = now();
            $investor->save();

            // 🧠 Construir mensaje
            $message = "¡Hola {$investor->fullname}! 🎉\n\n"
                . "Tu registro ha sido exitoso. Por favor, verifica tu cuenta usando el siguiente código:\n\n"
                . "🔢 *{$verificationCode}*\n\n"
                . "¡Bienvenido a nuestra plataforma de inversiones!";

            // 📞 Asegurar formato correcto del número destino
            $to = $investor->telephone;
            if (!str_starts_with($to, 'whatsapp:')) {
                $to = 'whatsapp:' . $to;
            }

            Log::info('Enviando WhatsApp', [
                'to' => $to,
                'from' => $this->from,
                'message' => $message,
            ]);

            // 🚀 Enviar mensaje vía Twilio
            $this->twilio->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            Log::info('Mensaje de verificación enviado exitosamente', [
                'investor_id' => $investor->id,
                'code' => $verificationCode,
            ]);

            return [
                'success' => true,
                'message' => 'Verification message sent successfully',
            ];
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp verification message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Procesar respuesta de verificación del inversionista
     */
    public function processVerificationResponse($fromNumber, $messageBody, $twilioMessageSid = null)
    {
        // Limpiar y normalizar la respuesta
        $response = strtoupper(trim($messageBody));
        
        // Buscar al inversionista por número de teléfono
        $phoneNumber = $this->formatPhoneNumber($fromNumber);
        
        $investor = $this->findInvestorByPhone($phoneNumber, $fromNumber);

        if (!$investor) {
            Log::warning('WhatsApp response from unknown number', [
                'from' => $fromNumber,
                'message' => $messageBody
            ]);
            
            // Enviar mensaje informativo a número desconocido
            $this->sendUnknownNumberMessage($phoneNumber);
            
            return ['success' => false, 'error' => 'Número no encontrado'];
        }

        // Verificar si ya está verificado
        if ($investor->whatsapp_verified) {
            $this->sendAlreadyVerifiedMessage($investor);
            return ['success' => true, 'message' => 'Ya está verificado'];
        }

        // Verificar que tenga una verificación pendiente
        if (!$investor->whatsapp_verification_code || !$investor->whatsapp_verification_sent_at) {
            $this->sendNoVerificationPendingMessage($investor);
            return ['success' => false, 'error' => 'No hay verificación pendiente'];
        }

        // Verificar que la verificación no haya expirado (30 minutos)
        if ($investor->whatsapp_verification_sent_at->diffInMinutes(now()) > 30) {
            $this->sendExpiredVerificationMessage($investor);
            return ['success' => false, 'error' => 'Verificación expirada'];
        }

        // Procesar respuesta
        if ($this->isPositiveResponse($response)) {
            return $this->handlePositiveResponse($investor);
        } elseif ($this->isNegativeResponse($response)) {
            return $this->handleNegativeResponse($investor);
        } else {
            return $this->handleUnknownResponse($investor, $response);
        }
    }

    /**
     * Construir mensaje de verificación
     */
    private function buildVerificationMessage(Investor $investor, $verificationCode)
    {
        $name = $investor->name ?? 'Inversionista';
        
        $message = "🔐 *Verificación de WhatsApp - Zuma*\n\n";
        $message .= "¡Hola *{$name}*! 👋\n\n";
        $message .= "Para completar tu registro en Zuma, necesitamos verificar tu número de WhatsApp.\n\n";
        $message .= "📱 *¿Es este tu número correcto?*\n\n";
        $message .= "Responde exactamente:\n";
        $message .= "✅ *SI* - para confirmar\n";
        $message .= "❌ *NO* - si no es tu número\n\n";
        $message .= "🔑 Código: *{$verificationCode}*\n";
        $message .= "⏱️ Este código expira en 30 minutos\n\n";
        $message .= "¡Gracias por elegir Zuma! 🚀";

        return $message;
    }

    /**
     * Generar código de verificación
     */
    private function generateVerificationCode()
    {
        return str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Buscar inversionista por teléfono
     */
    private function findInvestorByPhone($phoneNumber, $originalFrom)
    {
        return Investor::where(function ($query) use ($phoneNumber, $originalFrom) {
            $query->where('telephone', $phoneNumber)
                  ->orWhere('telephone', $originalFrom)
                  ->orWhere('telephone', str_replace('+51', '', $phoneNumber))
                  ->orWhere('telephone', str_replace('+', '', $phoneNumber));
        })->first();
    }

    /**
     * Verificar respuesta positiva
     */
    private function isPositiveResponse($response)
    {
        return in_array($response, ['SI', 'SÍ', 'YES', 'Y', '1', 'OK', 'CONFIRMO', 'CORRECTO']);
    }

    /**
     * Verificar respuesta negativa
     */
    private function isNegativeResponse($response)
    {
        return in_array($response, ['NO', 'N', '0', 'INCORRECTO', 'CANCEL', 'CANCELAR']);
    }

    /**
     * Manejar respuesta positiva
     */
    private function handlePositiveResponse(Investor $investor)
    {
        $investor->update([
            'whatsapp_verified' => true,
            'whatsapp_verified_at' => now(),
            'validacion_whatsapp' => 'confirmed'
        ]);

        // Limpiar cache del código
        Cache::forget("whatsapp_code_{$investor->id}");

        $this->sendConfirmationMessage($investor, true);

        Log::info('WhatsApp number verified successfully', [
            'investor_id' => $investor->id,
            'phone' => $investor->telephone
        ]);

        return [
            'success' => true,
            'verified' => true,
            'investor_id' => $investor->id
        ];
    }

    /**
     * Manejar respuesta negativa
     */
    private function handleNegativeResponse(Investor $investor)
    {
        $investor->update([
            'whatsapp_verified' => false,
            'validacion_whatsapp' => 'rejected'
        ]);

        // Limpiar cache del código
        Cache::forget("whatsapp_code_{$investor->id}");

        $this->sendConfirmationMessage($investor, false);

        Log::info('WhatsApp number rejected by investor', [
            'investor_id' => $investor->id,
            'phone' => $investor->telephone
        ]);

        return [
            'success' => true,
            'verified' => false,
            'investor_id' => $investor->id
        ];
    }

    /**
     * Manejar respuesta desconocida
     */
    private function handleUnknownResponse(Investor $investor, $response)
    {
        $this->sendHelpMessage($investor);

        Log::info('Unrecognized WhatsApp verification response', [
            'investor_id' => $investor->id,
            'phone' => $investor->telephone,
            'response' => $response
        ]);

        return [
            'success' => false,
            'error' => 'Respuesta no reconocida'
        ];
    }

    /**
     * Enviar mensaje de confirmación según la respuesta
     */
    private function sendConfirmationMessage(Investor $investor, $verified)
    {
        $phoneNumber = $this->formatPhoneNumber($investor->telephone);

        if ($verified) {
            $message = "🎉 *¡Verificación Exitosa!*\n\n";
            $message .= "✅ Tu número de WhatsApp ha sido verificado correctamente.\n\n";
            $message .= "🚀 Ya puedes continuar con tu proceso de registro en Zuma.\n\n";
            $message .= "📧 Revisa tu email para los siguientes pasos.\n\n";
            $message .= "¡Bienvenido a la familia Zuma! 💙";
        } else {
            $message = "❌ *Número Rechazado*\n\n";
            $message .= "Hemos registrado que este número no es correcto.\n\n";
            $message .= "📝 *Próximos pasos:*\n";
            $message .= "1. Actualiza tu número en tu perfil\n";
            $message .= "2. O contacta a nuestro soporte\n\n";
            $message .= "📞 *Soporte:* +51 XXX XXX XXX\n";
            $message .= "📧 *Email:* soporte@zuma.com.pe";
        }

        $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Enviar mensaje de ayuda cuando no se entiende la respuesta
     */
    private function sendHelpMessage(Investor $investor)
    {
        $phoneNumber = $this->formatPhoneNumber($investor->telephone);

        $message = "❓ *No entendí tu respuesta*\n\n";
        $message .= "Para verificar tu número, responde exactamente:\n\n";
        $message .= "✅ *SI* - si este ES tu número\n";
        $message .= "❌ *NO* - si este NO es tu número\n\n";
        $message .= "⚠️ *Solo estas opciones son válidas*\n\n";
        $message .= "¿Necesitas ayuda? Contacta soporte:\n";
        $message .= "📞 +51 XXX XXX XXX";

        $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Enviar mensaje a número desconocido
     */
    private function sendUnknownNumberMessage($phoneNumber)
    {
        $message = "👋 Hola!\n\n";
        $message .= "Este número no está registrado en nuestro sistema.\n\n";
        $message .= "Si deseas registrarte en Zuma, visita:\n";
        $message .= "🌐 www.zuma.com.pe\n\n";
        $message .= "O contacta nuestro soporte:\n";
        $message .= "📞 +51 XXX XXX XXX";

        $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Enviar mensaje de ya verificado
     */
    private function sendAlreadyVerifiedMessage(Investor $investor)
    {
        $phoneNumber = $this->formatPhoneNumber($investor->telephone);
        
        $message = "✅ *Número ya verificado*\n\n";
        $message .= "Tu WhatsApp ya fue verificado el ";
        $message .= $investor->whatsapp_verified_at->format('d/m/Y à\s H:i') . "\n\n";
        $message .= "🚀 Puedes continuar con tu proceso en Zuma.\n\n";
        $message .= "¿Necesitas ayuda? Contacta soporte:\n";
        $message .= "📞 +51 XXX XXX XXX";

        $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Enviar mensaje de sin verificación pendiente
     */
    private function sendNoVerificationPendingMessage(Investor $investor)
    {
        $phoneNumber = $this->formatPhoneNumber($investor->telephone);
        
        $message = "⚠️ *No hay verificación pendiente*\n\n";
        $message .= "No encontramos una verificación pendiente para este número.\n\n";
        $message .= "🔄 Si necesitas verificar tu número, contacta soporte:\n";
        $message .= "📞 +51 XXX XXX XXX\n";
        $message .= "📧 soporte@zuma.com.pe";

        $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Enviar mensaje de verificación expirada
     */
    private function sendExpiredVerificationMessage(Investor $investor)
    {
        $phoneNumber = $this->formatPhoneNumber($investor->telephone);
        
        $message = "⏰ *Verificación Expirada*\n\n";
        $message .= "Tu código de verificación ha expirado (30 min máximo).\n\n";
        $message .= "🔄 Para obtener un nuevo código, contacta soporte:\n";
        $message .= "📞 +51 XXX XXX XXX\n";
        $message .= "📧 soporte@zuma.com.pe";

        $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Enviar mensaje genérico
     */
    private function sendMessage($phoneNumber, $message)
    {
        try {
            $this->twilio->messages->create(
                'whatsapp:' . $phoneNumber,
                [
                    'from' => $this->from,
                    'body' => $message
                ]
            );
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp message', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Formatear número de teléfono
     */
    private function formatPhoneNumber($phone)
    {
        // Remover espacios y caracteres especiales excepto +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si no tiene código de país, agregar +51 para Perú
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '51')) {
                $phone = '+' . $phone;
            } else {
                $phone = '+51' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Reenviar mensaje de verificación
     */
    public function resendVerificationMessage(Investor $investor)
    {
        // Limpiar estado anterior
        $investor->update([
            'whatsapp_verified' => false,
            'whatsapp_verification_code' => null,
            'whatsapp_verification_sent_at' => null,
            'validacion_whatsapp' => null
        ]);

        return $this->sendVerificationMessage($investor);
    }

    /**
     * Verificar si un número puede recibir mensajes
     */
    public function canSendMessage(Investor $investor)
    {
        return $investor->telephone && 
               !Cache::has("whatsapp_verification_rate_limit_{$investor->telephone}");
    }
}