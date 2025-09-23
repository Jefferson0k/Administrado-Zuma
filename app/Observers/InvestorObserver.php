<?php

namespace App\Observers;

use App\Models\Investor;
use App\Services\WhatsAppVerificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class InvestorObserver
{
    protected $whatsAppVerificationService;

    public function __construct(WhatsAppVerificationService $whatsAppVerificationService)
    {
        $this->whatsAppVerificationService = $whatsAppVerificationService;
    }

    /**
     * Handle the Investor "created" event.
     * Se ejecuta cuando se crea un nuevo inversionista
     */
    public function created(Investor $investor)
    {
        // Solo enviar verificación si tiene teléfono y no está ya verificado
        if ($investor->telephone && !$investor->whatsapp_verified) {
            
            Log::info('New investor created, preparing WhatsApp verification', [
                'investor_id' => $investor->id,
                'telephone' => $investor->telephone,
                'name' => $investor->name
            ]);

            // Usar queue para evitar bloquear la creación del usuario
            // Si no tienes queue configurado, esto se ejecutará inmediatamente
            dispatch(function () use ($investor) {
                $this->sendVerificationWithRetry($investor, 'creation');
            })->delay(now()->addSeconds(5)); // Pequeño delay para asegurar que la transacción se completó
        }
    }

    /**
     * Handle the Investor "updated" event.
     * Se ejecuta cuando se actualiza un inversionista
     */
    public function updated(Investor $investor)
    {
        // Evitar que se dispare en la misma creación
        if ($investor->wasRecentlyCreated) {
            return;
        }

        // Si se actualiza el teléfono
        if ($investor->isDirty('telephone')) {
            $oldPhone = $investor->getOriginal('telephone');
            $newPhone = $investor->telephone;

            Log::info('Investor telephone updated', [
                'investor_id' => $investor->id,
                'old_telephone' => $oldPhone,
                'new_telephone' => $newPhone
            ]);

            // Si ahora tiene teléfono y antes no tenía, o cambió el teléfono
            if ($newPhone && ($newPhone !== $oldPhone)) {
                
                // Si no está verificado, enviar nueva verificación
                if (!$investor->whatsapp_verified) {
                    Log::info('Sending WhatsApp verification due to phone update', [
                        'investor_id' => $investor->id,
                        'new_phone' => $newPhone
                    ]);

                    // Resetear estado de verificación
                    $investor->updateQuietly([
                        'whatsapp_verified' => false,
                        'whatsapp_verified_at' => null,
                        'whatsapp_verification_code' => null,
                        'whatsapp_verification_sent_at' => null,
                        'validacion_whatsapp' => null
                    ]);

                    // Limpiar cache relacionado
                    Cache::forget("whatsapp_code_{$investor->id}");
                    Cache::forget("whatsapp_verification_rate_limit_{$oldPhone}");

                    // Enviar nueva verificación con delay
                    dispatch(function () use ($investor) {
                        $this->sendVerificationWithRetry($investor, 'phone_update');
                    })->delay(now()->addSeconds(3));
                }
            } elseif (!$newPhone && $oldPhone) {
                // Si removió el teléfono, limpiar verificación
                Log::info('Phone number removed, clearing verification', [
                    'investor_id' => $investor->id
                ]);

                $investor->updateQuietly([
                    'whatsapp_verified' => false,
                    'whatsapp_verified_at' => null,
                    'whatsapp_verification_code' => null,
                    'whatsapp_verification_sent_at' => null,
                    'validacion_whatsapp' => null
                ]);

                // Limpiar cache
                Cache::forget("whatsapp_code_{$investor->id}");
                Cache::forget("whatsapp_verification_rate_limit_{$oldPhone}");
            }
        }
    }

    /**
     * Enviar verificación con reintentos
     */
    private function sendVerificationWithRetry(Investor $investor, string $trigger, int $attempt = 1)
    {
        $maxAttempts = 3;

        try {
            // Verificar si puede enviar mensaje
            if (!$this->whatsAppVerificationService->canSendMessage($investor)) {
                Log::warning('Cannot send WhatsApp verification due to rate limit', [
                    'investor_id' => $investor->id,
                    'trigger' => $trigger,
                    'attempt' => $attempt
                ]);

                // Reintentar en 2 minutos si no es el último intento
                if ($attempt < $maxAttempts) {
                    dispatch(function () use ($investor, $trigger, $attempt) {
                        $this->sendVerificationWithRetry($investor, $trigger, $attempt + 1);
                    })->delay(now()->addMinutes(2));
                }

                return;
            }

            $result = $this->whatsAppVerificationService->sendVerificationMessage($investor);

            if ($result && $result['success']) {
                Log::info('WhatsApp verification sent successfully', [
                    'investor_id' => $investor->id,
                    'trigger' => $trigger,
                    'attempt' => $attempt,
                    'message_sid' => $result['message_sid'] ?? null
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error sending verification');
            }

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp verification', [
                'investor_id' => $investor->id,
                'trigger' => $trigger,
                'attempt' => $attempt,
                'error' => $e->getMessage()
            ]);

            // Reintentar si no es el último intento
            if ($attempt < $maxAttempts) {
                $delay = $attempt * 5; // 5, 10, 15 minutos
                
                Log::info('Scheduling WhatsApp verification retry', [
                    'investor_id' => $investor->id,
                    'next_attempt' => $attempt + 1,
                    'delay_minutes' => $delay
                ]);

                dispatch(function () use ($investor, $trigger, $attempt) {
                    $this->sendVerificationWithRetry($investor, $trigger, $attempt + 1);
                })->delay(now()->addMinutes($delay));
            } else {
                Log::error('Max attempts reached for WhatsApp verification', [
                    'investor_id' => $investor->id,
                    'trigger' => $trigger,
                    'max_attempts' => $maxAttempts
                ]);

                // Opcionalmente, podrías notificar al equipo de soporte
                $this->notifyVerificationFailure($investor, $trigger, $e->getMessage());
            }
        }
    }

    /**
     * Notificar falla en verificación (opcional)
     */
    private function notifyVerificationFailure(Investor $investor, string $trigger, string $error)
    {
        // Aquí podrías enviar una notificación al equipo de soporte
        // Por ejemplo, un email, Slack, etc.
        
        Log::critical('WhatsApp verification completely failed', [
            'investor_id' => $investor->id,
            'investor_name' => $investor->name,
            'investor_email' => $investor->email,
            'investor_phone' => $investor->telephone,
            'trigger' => $trigger,
            'final_error' => $error,
            'timestamp' => now()
        ]);

        // Ejemplo: Marcar para revisión manual
        $investor->updateQuietly([
            'validacion_whatsapp' => 'failed_auto_verification'
        ]);
    }

    /**
     * Handle the Investor "deleting" event.
     */
    public function deleting(Investor $investor)
    {
        // Limpiar cache relacionado al inversionista
        Cache::forget("whatsapp_code_{$investor->id}");
        if ($investor->telephone) {
            Cache::forget("whatsapp_verification_rate_limit_{$investor->telephone}");
        }

        Log::info('Cleaning up WhatsApp verification data for deleted investor', [
            'investor_id' => $investor->id
        ]);
    }
}