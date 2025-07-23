<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificacionController extends Controller
{
    /**
     * Obtiene las notificaciones pendientes para el inversionista autenticado
     */
    public function list(Request $request): JsonResponse
    {
        try {
            // Obtener el inversionista autenticado
            $investor = $request->user();
            
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $notifications = [];

            // 1. Verificar datos personales faltantes
            $personalDataNotification = $this->checkPersonalData($investor);
            if ($personalDataNotification) {
                $notifications[] = $personalDataNotification;
            }

            // 2. Verificar cuenta bancaria
            $bankAccountNotification = $this->checkBankAccount($investor);
            if ($bankAccountNotification) {
                $notifications[] = $bankAccountNotification;
            }

            // 3. Verificar depósitos (si tienes esta lógica)
            $depositNotification = $this->checkDeposits($investor);
            if ($depositNotification) {
                $notifications[] = $depositNotification;
            }

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica si faltan datos personales por completar
     */
    private function checkPersonalData(Investor $investor): ?array
    {
        $requiredFields = [
            'department' => 'departamento',
            'province' => 'provincia', 
            'district' => 'distrito',
            'document_front' => 'documento frontal',
            'document_back' => 'documento posterior'
        ];

        $missingFields = [];

        foreach ($requiredFields as $field => $label) {
            if (empty($investor->$field)) {
                $missingFields[] = $label;
            }
        }

        if (!empty($missingFields)) {
            $missingText = implode(', ', $missingFields);
            
            return [
                'id' => 'personal_data',
                'icon' => 'pi pi-user',
                'text' => "Te faltan completar los siguientes datos personales: {$missingText}. Complétalos para continuar usando la web.",
                'action' => 'Completar',
                'type' => 'personal',
                'priority' => 1,
                'missing_fields' => array_keys($requiredFields)
            ];
        }

        return null;
    }

    /**
     * Verifica si el inversionista tiene al menos una cuenta bancaria
     */
    private function checkBankAccount(Investor $investor): ?array
    {
        $bankAccountsCount = BankAccount::where('investor_id', $investor->id)
            ->where('status', 'active') // Asumiendo que tienen un status activo
            ->count();

        if ($bankAccountsCount === 0) {
            return [
                'id' => 'bank_account',
                'icon' => 'pi pi-wallet',
                'text' => 'Aún no tienes ninguna cuenta bancaria asociada. Agrega una para continuar usando la web.',
                'action' => 'Agregar cuenta',
                'type' => 'cuenta',
                'priority' => 2
            ];
        }

        return null;
    }

    /**
     * Verifica si el inversionista tiene depósitos
     * Ajusta esta lógica según tu modelo de depósitos
     */
    private function checkDeposits(Investor $investor): ?array
    {
        // Verificar si tiene balances o inversiones
        $hasBalance = $investor->balances()->where('amount', '>', 0)->exists();
        $hasInvestments = $investor->investments()->exists();

        if (!$hasBalance && !$hasInvestments) {
            return [
                'id' => 'deposits',
                'icon' => 'pi pi-dollar',
                'text' => 'Aún no tienes ningún movimiento. Realiza un depósito para continuar usando la web.',
                'action' => 'Depositar',
                'type' => 'deposito',
                'priority' => 3
            ];
        }

        return null;
    }

    /**
     * Obtiene detalles específicos de datos faltantes
     */
    public function getMissingData(Request $request): JsonResponse
    {
        try {
            $investor = $request->user();
            
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $missingData = [
                'personal_data' => [],
                'bank_accounts' => BankAccount::where('investor_id', $investor->id)->count() === 0,
                'has_deposits' => $investor->balances()->where('amount', '>', 0)->exists()
            ];

            // Verificar campos personales faltantes
            $personalFields = [
                'department' => 'Departamento',
                'province' => 'Provincia',
                'district' => 'Distrito', 
                'document_front' => 'Documento Frontal',
                'document_back' => 'Documento Posterior'
            ];

            foreach ($personalFields as $field => $label) {
                if (empty($investor->$field)) {
                    $missingData['personal_data'][$field] = $label;
                }
            }

            return response()->json([
                'success' => true,
                'missing_data' => $missingData,
                'completion_percentage' => $this->calculateCompletionPercentage($investor)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos faltantes'
            ], 500);
        }
    }

    /**
     * Calcula el porcentaje de completitud del perfil
     */
    private function calculateCompletionPercentage(Investor $investor): float
    {
        $totalFields = 8; // Campos básicos + campos adicionales
        $completedFields = 3; // name, email, document (asumiendo que siempre están)

        $additionalFields = ['department', 'province', 'district', 'document_front', 'document_back'];
        
        foreach ($additionalFields as $field) {
            if (!empty($investor->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / $totalFields) * 100, 2);
    }

    /**
     * Marca una notificación como completada (opcional)
     */
    public function markAsCompleted(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|string'
        ]);

        // Aquí podrías implementar lógica para marcar notificaciones como vistas
        // por ejemplo, guardando en una tabla de notificaciones_vistas

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como completada'
        ]);
    }
}