<?php

namespace App\Services;

use App\Models\FixedTermRate;
use App\Models\AmountRange;
use App\Models\TermPlan;
use App\Models\PaymentFrequency;

class LoanSimulationService
{
    /**
     * Simular un préstamo basado en los parámetros dados
     */
    public function simulate($corporate_entity_id, $amount, $days, $payment_frequency_id)
    {
        try {
            // 1. Buscar el rango de monto apropiado
            $amount_range = $this->findAmountRange($corporate_entity_id, $amount);
            if (!$amount_range) {
                return $this->errorResponse('No se encontró rango de monto válido para esta cantidad');
            }

            // 2. Buscar el plan de término apropiado
            $term_plan = $this->findTermPlan($days);
            if (!$term_plan) {
                return $this->errorResponse('No se encontró plan de término válido para estos días');
            }

            // 3. Buscar la tasa fija correspondiente
            $fixed_term_rate = $this->findFixedTermRate(
                $corporate_entity_id, 
                $amount_range->id, 
                $term_plan->id
            );
            
            if (!$fixed_term_rate) {
                return $this->errorResponse('No se encontró configuración de tasa para estos parámetros');
            }

            // 4. Obtener frecuencia de pago
            $payment_frequency = PaymentFrequency::find($payment_frequency_id);
            if (!$payment_frequency) {
                return $this->errorResponse('Frecuencia de pago no válida');
            }

            // 5. Calcular la simulación
            $simulation = $this->calculateSimulation(
                $amount, 
                $days, 
                $fixed_term_rate->valor, 
                $payment_frequency
            );

            // 6. Preparar respuesta completa
            return $this->successResponse($simulation, [
                'corporate_entity' => $fixed_term_rate->corporateEntity,
                'amount_range' => $amount_range,
                'term_plan' => $term_plan,
                'rate_type' => $fixed_term_rate->rateType,
                'payment_frequency' => $payment_frequency
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error en la simulación: ' . $e->getMessage());
        }
    }

    /**
     * Buscar rango de monto apropiado
     */
    private function findAmountRange($corporate_entity_id, $amount)
    {
        return AmountRange::where('corporate_entity_id', $corporate_entity_id)
            ->where('desde', '<=', $amount)
            ->where('hasta', '>=', $amount)
            ->first();
    }

    /**
     * Buscar plan de término apropiado
     */
    private function findTermPlan($days)
    {
        return TermPlan::where('dias_minimos', '<=', $days)
            ->where('dias_maximos', '>=', $days)
            ->first();
    }

    /**
     * Buscar tasa fija correspondiente
     */
    private function findFixedTermRate($corporate_entity_id, $amount_range_id, $term_plan_id)
    {
        return FixedTermRate::where('corporate_entity_id', $corporate_entity_id)
            ->where('amount_range_id', $amount_range_id)
            ->where('term_plan_id', $term_plan_id)
            ->where('estado', true)
            ->with(['corporateEntity', 'amountRange', 'termPlan', 'rateType'])
            ->first();
    }

    /**
     * Calcular la simulación del préstamo
     */
    private function calculateSimulation($principal_amount, $days, $rate_percentage, $payment_frequency)
    {
        // Convertir tasa porcentual a decimal
        $rate = $rate_percentage / 100;
        
        // Calcular interés total (asumiendo tasa simple por el período)
        $total_interest = $principal_amount * $rate * ($days / 365);
        $total_return = $principal_amount + $total_interest;
        
        // Calcular número de pagos según frecuencia
        $payment_frequency_days = $payment_frequency->dias;
        $number_of_payments = ceil($days / $payment_frequency_days);
        
        // Monto por pago
        $payment_amount = $total_return / $number_of_payments;
        
        // Generar cronograma de pagos
        $payment_schedule = $this->generatePaymentSchedule(
            $number_of_payments, 
            $payment_amount, 
            $payment_frequency_days
        );
        
        return [
            'principal_amount' => round($principal_amount, 2),
            'rate_percentage' => $rate_percentage,
            'days' => $days,
            'total_interest' => round($total_interest, 2),
            'total_return' => round($total_return, 2),
            'payment_amount' => round($payment_amount, 2),
            'number_of_payments' => $number_of_payments,
            'payment_schedule' => $payment_schedule
        ];
    }

    /**
     * Generar cronograma de pagos
     */
    private function generatePaymentSchedule($number_of_payments, $payment_amount, $frequency_days)
    {
        $schedule = [];
        
        for ($i = 1; $i <= $number_of_payments; $i++) {
            $schedule[] = [
                'payment_number' => $i,
                'payment_amount' => round($payment_amount, 2),
                'due_days' => $i * $frequency_days,
                'due_date' => now()->addDays($i * $frequency_days)->format('Y-m-d')
            ];
        }
        
        return $schedule;
    }

    /**
     * Obtener todas las opciones disponibles para simulación
     */
    public function getSimulationOptions()
    {
        return [
            'corporate_entities' => \App\Models\CorporateEntity::where('estado', true)
                ->select('id', 'nombre', 'tipo_entidad')
                ->get(),
            'payment_frequencies' => PaymentFrequency::select('id', 'nombre', 'dias')->get(),
            'rate_types' => \App\Models\RateType::select('id', 'nombre', 'descripcion')->get()
        ];
    }

    /**
     * Obtener rangos de monto para una entidad corporativa
     */
    public function getAmountRanges($corporate_entity_id)
    {
        return AmountRange::where('corporate_entity_id', $corporate_entity_id)
            ->select('id', 'desde', 'hasta', 'moneda')
            ->get();
    }

    /**
     * Obtener planes de término disponibles
     */
    public function getTermPlans()
    {
        return TermPlan::select('id', 'nombre', 'dias_minimos', 'dias_maximos')->get();
    }

    /**
     * Validar parámetros de simulación
     */
    public function validateSimulationParams($corporate_entity_id, $amount, $days, $payment_frequency_id)
    {
        $errors = [];

        // Validar entidad corporativa
        if (!\App\Models\CorporateEntity::where('id', $corporate_entity_id)->where('estado', true)->exists()) {
            $errors[] = 'Entidad corporativa no válida o inactiva';
        }

        // Validar monto
        if ($amount <= 0) {
            $errors[] = 'El monto debe ser mayor a 0';
        }

        // Validar días
        if ($days <= 0) {
            $errors[] = 'Los días deben ser mayor a 0';
        }

        // Validar frecuencia de pago
        if (!PaymentFrequency::where('id', $payment_frequency_id)->exists()) {
            $errors[] = 'Frecuencia de pago no válida';
        }

        return $errors;
    }

    /**
     * Respuesta de éxito
     */
    private function successResponse($simulation, $additional_data = [])
    {
        return [
            'success' => true,
            'data' => array_merge($simulation, [
                'details' => $additional_data
            ])
        ];
    }

    /**
     * Respuesta de error
     */
    private function errorResponse($message)
    {
        return [
            'success' => false,
            'message' => $message
        ];
    }
}
