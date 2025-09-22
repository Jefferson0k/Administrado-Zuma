<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Balance;
use App\Models\Movement;
use App\Models\PropertyInvestor;
use App\Models\Investor;
use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Models\PropertyConfiguracion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CerrarSubastasFinalizadas extends Command
{
    // Firma del comando para Artisan
    protected $signature = 'subastas:cerrar';
    
    // Descripción del comando
    protected $description = 'Cierra subastas vencidas, asigna ganador, devuelve fondos a perdedores, actualiza estados y registra movimientos.';

    public function handle()
    {
        $now = Carbon::now(); // Fecha y hora actual
        $this->info("Ejecutando comando a las: " . $now->format('Y-m-d H:i:s'));

        // PARTE 1: Procesar subastas en estado 'en_subasta'
        $this->procesarSubastasEnSubasta($now);

        // PARTE 2: Cerrar subastas activas que ya terminaron
        $this->cerrarSubastasFinalizadas($now);

        return 0;
    }

    /**
     * Procesa subastas en estado 'en_subasta' según la lógica de tiempo e inversores
     */
    private function procesarSubastasEnSubasta($now)
    {
        $this->info("\n🔄 === PROCESANDO SUBASTAS EN_SUBASTA ===");
        
        // Buscar TODAS las subastas en estado 'en_subasta'
        $subastasEnSubasta = Auction::where('estado', 'en_subasta')
            ->with(['property'])
            ->get();

        $this->info("Encontradas " . count($subastasEnSubasta) . " subastas en estado 'en_subasta'.");

        foreach ($subastasEnSubasta as $subasta) {
            try {
                DB::transaction(function () use ($subasta) {
                    $this->activarSubasta($subasta);
                });
            } catch (\Exception $e) {
                $this->error("Error procesando subasta {$subasta->id}: " . $e->getMessage());
            }
        }
        
        // Pequeña pausa para asegurar que las actualizaciones se reflejen
        if (count($subastasEnSubasta) > 0) {
            $this->info("⏱️ Esperando 1 segundo para sincronizar cambios...");
            sleep(1);
        }
    }

    /**
     * Cierra subastas activas que ya terminaron
     */
    private function cerrarSubastasFinalizadas($now)
    {
        $this->info("\n🏁 === CERRANDO SUBASTAS FINALIZADAS ===");

        // Obtener todas las subastas activas cuyo fin ya pasó
        // (incluyendo las que recién se activaron)
        $subastas = Auction::where('estado', 'activa')
            ->with(['property.currency', 'pujas.investor']) // Cargar relaciones necesarias
            ->where(function ($query) use ($now) {
                // Subastas de días anteriores
                $query->where('dia_subasta', '<', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        // Subastas del día de hoy cuyo fin ya pasó
                        $q->where('dia_subasta', $now->toDateString())
                            ->where('hora_fin', '<=', $now->toTimeString());
                    });
            })->get();

        $this->info("Encontradas " . count($subastas) . " subastas activas para cerrar.");

        // Procesar cada subasta en una transacción para asegurar consistencia
        foreach ($subastas as $subasta) {
            try {
                DB::transaction(function () use ($subasta) {
                    $this->procesarSubasta($subasta);
                });
            } catch (\Exception $e) {
                $this->error("Error procesando subasta {$subasta->id}: " . $e->getMessage());
                Log::error("Error en subasta {$subasta->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Mostrar resumen en la consola
        $this->info("\n✅ Proceso completado. Se procesaron " . count($subastas) . " subastas finalizadas.");
    }

    /**
     * Activa una subasta individual (en_subasta -> activa o procesarla si ya terminó)
     */
    private function activarSubasta($subasta)
    {
        $this->info("=== EVALUANDO SUBASTA ID: {$subasta->id} ===");
        
        $now = Carbon::now();
        
        // Verificar si ya terminó el tiempo
        $yaTermino = false;
        
        if ($subasta->dia_subasta < $now->toDateString()) {
            // Día anterior, ya terminó
            $yaTermino = true;
        } elseif ($subasta->dia_subasta == $now->toDateString() && $subasta->hora_fin <= $now->toTimeString()) {
            // Mismo día pero ya pasó la hora fin
            $yaTermino = true;
        }
        
        // Verificar si hay inversiones/pujas
        $pujas = Bid::where('auction_id', $subasta->id)->count();
        
        $this->info("Estado actual: en_subasta");
        $this->info("Tiempo terminado: " . ($yaTermino ? 'SI' : 'NO'));
        $this->info("Número de pujas: {$pujas}");
        
        if ($yaTermino) {
            // EL TIEMPO YA TERMINÓ
            if ($pujas > 0) {
                // CASO 1: Tiempo terminado + HAY inversores = PROCESAR GANADOR
                $this->info("✅ CASO 1: Tiempo terminado + hay inversores → PROCESANDO GANADOR");
                
                // Cambiar a activa primero para poder procesarla
                $subasta->update(['estado' => 'activa']);
                $this->info("Estado cambiado a 'activa' para procesamiento");
                
                // Procesar inmediatamente
                $this->procesarSubasta($subasta);
                
            } else {
                // CASO 2: Tiempo terminado + NO hay inversores = ACTIVAR
                $this->info("✅ CASO 2: Tiempo terminado + sin inversores → CAMBIAR A ACTIVA");
                
                $subasta->update(['estado' => 'activa']);
                $this->info("Subasta {$subasta->id} cambiada a 'activa' (sin pujas, tiempo terminado)");
            }
            
        } else {
            // EL TIEMPO NO HA TERMINADO
            // CASO 3: Tiempo no terminado = ACTIVAR (con o sin inversores)
            $this->info("✅ CASO 3: Tiempo no terminado → CAMBIAR A ACTIVA");
            
            $subasta->update(['estado' => 'activa']);
            $this->info("Subasta {$subasta->id} cambiada a 'activa' (tiempo no terminado)");
            
            // Actualizar el estado de la propiedad si es necesario
            $property = $subasta->property;
            if ($property && $property->estado !== 'en_subasta') {
                $property->update(['estado' => 'en_subasta']);
                $this->info("✅ Propiedad {$property->id} actualizada a 'en_subasta'");
            }
        }
    }

    // Función que procesa una subasta individual
    private function procesarSubasta($subasta)
    {
        $this->info("=== PROCESANDO SUBASTA ID: {$subasta->id} ===");
        
        // PASO 1: CONSULTAR BIDS - Obtener todas las pujas de la subasta
        $this->info("📋 PASO 1: Consultando pujas en BIDS...");
        $pujas = Bid::where('auction_id', $subasta->id)->with('investor')->get();

        // Si no hay participantes, se finaliza la subasta sin ganador
        if ($pujas->isEmpty()) {
            $subasta->update(['estado' => 'finalizada']);
            $this->info("❌ Subasta {$subasta->id} finalizada sin participantes.");
            return;
        }

        $this->info("Encontradas " . count($pujas) . " pujas en BIDS:");
        
        // Mostrar información de todas las pujas
        foreach ($pujas as $index => $puja) {
            $this->info("  Puja #" . ($index + 1) . " - Inversor: {$puja->investors_id}, Monto: {$puja->monto}, Moneda: " . ($puja->currency ?? 'No definida'));
        }

        // PASO 2: DETERMINAR GANADOR - Determinar la puja ganadora (mayor monto)
        $this->info("🏆 PASO 2: Determinando ganador...");
        $ganadorBid = $pujas->sortByDesc('monto')->first();
        
        $this->info("Puja ganadora: Inversor ID {$ganadorBid->investors_id}, Monto: {$ganadorBid->monto}, Moneda: {$ganadorBid->currency}");

        // Obtener el inversor ganador
        $ganador = $ganadorBid->investor ?? Investor::find($ganadorBid->investors_id);
        if (!$ganador) {
            $this->error("❌ No se encontró el inversor ganador con ID: {$ganadorBid->investors_id}");
            return;
        }

        $this->info("Ganador encontrado: {$ganador->name} (ID: {$ganador->id})");

        // PASO 3: ACTUALIZAR AUCTIONS - Actualizar la tabla auctions con el ganador
        $this->info("📊 PASO 3: Actualizando tabla AUCTIONS...");
        
        $subastaUpdated = $subasta->update([
            'estado' => 'finalizada',
            'ganador_id' => $ganador->id,
        ]);

        $this->info("Actualizando AUCTIONS - Estado: finalizada, Ganador ID: {$ganador->id}");
        $this->info("AUCTIONS actualizada: " . ($subastaUpdated ? '✅ SI' : '❌ NO'));

        // Verificar que se guardó correctamente en AUCTIONS
        $subasta->refresh();
        $this->info("Verificación AUCTIONS - Estado: {$subasta->estado}, Ganador ID: {$subasta->ganador_id}");

        // PASO 4: ACTUALIZAR PROPERTY - Cambiar estado basándose en el ganador de auctions
        $this->info("🏠 PASO 4: Actualizando tabla PROPERTY basándose en ganador de AUCTIONS...");
        
        $property = $subasta->property;
        if (!$property) {
            $this->error("❌ No se encontró la propiedad asociada a la subasta {$subasta->id}");
            return;
        }

        $this->info("Propiedad encontrada: ID {$property->id}, Estado actual: {$property->estado}");
        
        // Cambiar estado de la propiedad porque hay un ganador en auctions
        $propertyUpdated = $property->update([
            'estado' => 'subastada',
        ]);

        $this->info("Actualizando PROPERTY - Estado: subastada (porque subasta tiene ganador)");
        $this->info("PROPERTY actualizada: " . ($propertyUpdated ? '✅ SI' : '❌ NO'));

        // Verificar que se guardó correctamente en PROPERTY
        $property->refresh();
        $this->info("Verificación PROPERTY - Estado: {$property->estado}");

        // PASO 5: PROCESAR MOVIMIENTOS Y BALANCES
        $this->info("💰 PASO 5: Procesando movimientos financieros...");
        
        // Determinar la moneda
        $currency = $this->determinarMoneda($ganadorBid, $property);
        $this->info("Moneda determinada: {$currency}");

        // Crear movimiento descontando el monto al ganador
        $movementData = [
            'investor_id' => $ganador->id,
            'amount' => $ganadorBid->monto,
            'type' => 'investment', // Minúscula según migración
            'status' => MovementStatus::CONFIRMED->value, // Usar enum value
            'confirm_status' => MovementStatus::CONFIRMED->value, // Usar enum value
            'description' => "Zuma descontó el monto por ser ganador de la subasta {$subasta->id}",
            'currency' => $currency, // USD, PEN como string
        ];

        $this->info("Datos del movimiento a crear:");
        $this->info("- Investor ID: {$movementData['investor_id']}");
        $this->info("- Amount: {$movementData['amount']}");
        $this->info("- Currency: {$movementData['currency']}");
        $this->info("- Type: " . (is_object($movementData['type']) ? $movementData['type']->value ?? $movementData['type'] : $movementData['type']));
        $this->info("- Status: " . (is_object($movementData['status']) ? $movementData['status']->value ?? $movementData['status'] : $movementData['status']));

        try {
            $this->info("Intentando crear movimiento...");
            
            // Probar crear el movimiento paso a paso para encontrar el problema
            $this->info("Verificando que el modelo Movement se puede instanciar...");
            $testMovement = new Movement();
            $this->info("✅ Movement model se puede instanciar");
            
            $this->info("Intentando crear movimiento con create()...");
            $movement = Movement::create($movementData);
            $this->info("✅ Movimiento creado para ganador: ID {$movement->id}");
        } catch (\Exception $e) {
            $this->error("❌ Error creando movimiento: " . $e->getMessage());
            $this->info("Stack trace: " . $e->getTraceAsString());
            
            $this->info("Intentando crear sin usar create(), paso a paso...");
            try {
                $movement = new Movement();
                $movement->investor_id = $movementData['investor_id'];
                $movement->currency = $movementData['currency']; // Establecer currency PRIMERO
                $movement->amount = $movementData['amount']; // Luego amount
                $movement->type = $movementData['type'];
                $movement->status = $movementData['status'];
                $movement->confirm_status = $movementData['confirm_status'];
                $movement->description = $movementData['description'];
                $movement->save();
                
                $this->info("✅ Movimiento creado paso a paso: ID {$movement->id}");
            } catch (\Exception $e2) {
                $this->error("❌ Error aún paso a paso: " . $e2->getMessage());
                throw $e2;
            }
        }

        // Actualizar balance del ganador
        $this->actualizarBalanceGanador($ganador->id, $ganadorBid->monto, $currency);

        // PASO 6: ACTUALIZAR PROPERTY INVESTOR
        $this->info("👥 PASO 6: Actualizando PropertyInvestor...");
        
        // Obtener la configuración activa de la propiedad
        $config = $this->obtenerConfiguracionActiva($property->id);
        if ($config) {
            // Actualizar PropertyInvestor con el ganador
            $this->actualizarPropertyInvestor($config, $ganador->id, $property->id);
        }

        // PASO 7: PROCESAR PERDEDORES
        $this->info("😔 PASO 7: Procesando perdedores...");
        
        // Procesar perdedores: devolver fondos y registrar movimientos
        $this->procesarPerdedores($pujas, $ganador->id, $subasta->id, $currency);

        // VERIFICACIÓN FINAL
        $subasta->refresh();
        $property->refresh();
        
        $this->info("=== ✅ VERIFICACIÓN FINAL ===");
        $this->info("BIDS procesadas: " . count($pujas));
        $this->info("AUCTIONS - ID: {$subasta->id}, Estado: {$subasta->estado}, Ganador: {$subasta->ganador_id}");
        $this->info("PROPERTY - ID: {$property->id}, Estado: {$property->estado}");
        $this->info("=== FIN PROCESO SUBASTA {$subasta->id} ===");
    }

    private function determinarMoneda($puja, $property)
    {
        // 1. Si la puja tiene currency definido, usarlo directamente (ya es string: PEN, USD, etc.)
        if (isset($puja->currency) && !empty($puja->currency)) {
            $this->info("Moneda tomada de PUJA: {$puja->currency}");
            return $puja->currency; // Ya viene como 'PEN', 'USD', etc.
        }

        // 2. Si no, usar la moneda de la propiedad (convertir ID a código)
        if ($property->currency_id) {
            // Convertir currency_id a código de moneda
            $currencyCode = $property->currency_id == 1 ? 'PEN' : ($property->currency_id == 2 ? 'USD' : 'PEN');
            $this->info("Moneda tomada de PROPERTY (ID {$property->currency_id}): {$currencyCode}");
            return $currencyCode;
        }

        // 3. Si hay relación currency cargada
        if ($property->currency) {
            $this->info("Moneda tomada de relación PROPERTY->currency: {$property->currency->codigo}");
            return $property->currency->codigo ?? 'PEN';
        }

        // 4. Por defecto PEN
        $this->info("Moneda por defecto: PEN");
        return 'PEN';
    }

    private function actualizarBalanceGanador($investorId, $monto, $currency)
    {
        $balanceGanador = Balance::where('investor_id', $investorId)
            ->where('currency', $currency)
            ->first();
        
        if ($balanceGanador) {
            $balanceGanador->increment('invested_amount', $monto);
            $this->info("Balance actualizado para ganador - invested_amount incrementado en {$monto}");
        } else {
            $this->warn("⚠️ No se encontró balance para el ganador en moneda {$currency}");
        }
    }

    private function obtenerConfiguracionActiva($propertyId)
    {
        $config = PropertyConfiguracion::where('property_id', $propertyId)
            ->where('estado', 1)
            ->orderByDesc('id')
            ->first();

        if (!$config) {
            $this->error("❌ No se encontró configuración activa (estado = 1) para la propiedad {$propertyId}");
            return null;
        }

        $this->info("Configuración activa encontrada: ID {$config->id}");
        return $config;
    }

    private function actualizarPropertyInvestor($config, $ganadorId, $propertyId)
    {
        $propertyInvestor = PropertyInvestor::where('config_id', $config->id)->first();
        
        if ($propertyInvestor) {
            $propertyInvestor->update([
                'investor_id' => $ganadorId,
            ]);
            $this->info("PropertyInvestor actualizado con ganador ID: {$ganadorId}");
        } else {
            $this->error("❌ No se encontró PropertyInvestor con config_id {$config->id} para la propiedad {$propertyId}");
        }
    }

    private function procesarPerdedores($pujas, $ganadorId, $subastaId, $currency)
    {
        $perdedores = $pujas->where('investors_id', '!=', $ganadorId);
        
        $this->info("Procesando " . $perdedores->count() . " perdedores...");
        
        foreach ($perdedores as $puja) {
            // La moneda ya viene como string en la puja (PEN, USD, etc.)
            $pujaCurrency = $puja->currency ?? $currency;

            $this->info("Procesando perdedor - Inversor: {$puja->investors_id}, Monto: {$puja->monto}, Moneda: {$pujaCurrency}");

            // Actualizar balance del perdedor
            $balance = Balance::where('investor_id', $puja->investors_id)
                ->where('currency', $pujaCurrency)
                ->first();
            
            if ($balance) {
                $balance->increment('amount', $puja->monto);
                $this->info("✅ Balance devuelto a inversor {$puja->investors_id}: +{$puja->monto} {$pujaCurrency}");
            } else {
                $this->warn("⚠️ No se encontró balance para inversor {$puja->investors_id} en moneda {$pujaCurrency}");
                // Crear balance si no existe
                Balance::create([
                    'investor_id' => $puja->investors_id,
                    'currency' => $pujaCurrency,
                    'amount' => $puja->monto,
                    'invested_amount' => 0
                ]);
                $this->info("✅ Balance creado para inversor {$puja->investors_id} con {$puja->monto} {$pujaCurrency}");
            }

            // Crear movimiento de devolución
            Movement::create([
                'investor_id' => $puja->investors_id,
                'amount' => $puja->monto,
                'type' => 'withdraw', // Minúscula según migración
                'status' => MovementStatus::CONFIRMED->value, // Usar enum value
                'confirm_status' => MovementStatus::CONFIRMED->value, // Usar enum value
                'description' => "Devolución por perder subasta {$subastaId}",
                'currency' => $pujaCurrency,
            ]);

            $this->info("✅ Movimiento de devolución creado para inversor {$puja->investors_id}");
        }

        $this->info("💰 Se devolvieron fondos a " . $perdedores->count() . " perdedores.");
    }
}