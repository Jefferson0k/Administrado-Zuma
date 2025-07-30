<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\BankAccount;
use App\Models\PropertyReservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificacionController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        try {
            $investor = $request->user();

            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $notifications = [];

            $personalDataNotification = $this->checkPersonalData($investor);
            if ($personalDataNotification) {
                $notifications[] = $personalDataNotification;
            }

            $bankAccountNotification = $this->checkBankAccount($investor);
            if ($bankAccountNotification) {
                $notifications[] = $bankAccountNotification;
            }

            $depositNotification = $this->checkDeposits($investor);
            if ($depositNotification) {
                $notifications[] = $depositNotification;
            }

            $pendingInvestmentNotification = $this->checkPendingInvestment($investor);
            if ($pendingInvestmentNotification) {
                $notifications[] = $pendingInvestmentNotification;
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

    private function checkBankAccount(Investor $investor): ?array
    {
        $bankAccountsCount = BankAccount::where('investor_id', $investor->id)->count();

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

    private function checkDeposits(Investor $investor): ?array
    {
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
    private function checkPendingInvestment(Investor $investor): ?array{
        $count = PropertyReservation::where('investor_id', $investor->id)
            ->where('status', 'pendiente')
            ->count();
        if ($count > 0) {
            return [
                'id' => 'pending_reservation',
                'icon' => 'pi pi-clock',
                'text' => $count === 1
                    ? 'Tienes 1 reserva pendiente por completar. Confirma tu inversión para asegurar tu participación.'
                    : "Tienes {$count} reservas pendientes por completar. Confirma tus inversiones para asegurar tu participación.",
                'action' => 'Ver reservas',
                'type' => 'reserva_pendiente',
                'priority' => 4
            ];
        }
        return null;
    }
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

    private function calculateCompletionPercentage(Investor $investor): float
    {
        $totalFields = 8;
        $completedFields = 3;

        $additionalFields = ['department', 'province', 'district', 'document_front', 'document_back'];

        foreach ($additionalFields as $field) {
            if (!empty($investor->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / $totalFields) * 100, 2);
    }

    public function markAsCompleted(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|string'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como completada'
        ]);
    }
}
