<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Models\Admin;
use App\Models\ContactRequest;
use App\Notifications\LoanRequestNotification;
use App\Notifications\ProductInformationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Notification;

class ContactRequestController extends Controller{
    public function storeContactUs(StoreContactRequest $request){
        try {
            Log::info('Starting storeContactUs', ['data' => $request->validated()]);
            $contactRequest = ContactRequest::create(attributes: [
                ...$request->validated(),
                'status' => 'contact_us',
            ]);
            Log::info('Contact request created successfully', ['id' => $contactRequest->id]);
            $this->sendNotificationEmail($request->validated());
            return response()->json([
                'message' => 'Solicitud recibida. Nos pondremos en contacto pronto.',
                'success' => true,
            ], 201);
        } catch (Exception $e) {
            Log::error('Error in storeContactUs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'message' => 'Hubo un error procesando tu solicitud. Por favor intenta nuevamente.',
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
    private function sendNotificationEmail($contactData){
        try {
            Log::info('Attempting to send notification email', ['contact_data' => $contactData]);
            $adminEmail = env('MAIL_INVERSIONES_EMAIL', 'info@zuma.com.pe');
            Notification::route('mail', $adminEmail)
                ->notify(new ProductInformationRequest($contactData));
            Log::info('Product information email sent successfully', [
                'to' => $adminEmail,
                'product' => $contactData['interested_product'] ?? null
            ]);
        } catch (Exception $e) {
            Log::error('Error sending product information email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    public function storeInternal(StoreContactRequest $request)
    {
        try {
            $contactRequest = ContactRequest::create([
                ...$request->validated(),
                'status' => 'internal',
            ]);

            Log::info('Internal contact request created successfully', ['id' => $contactRequest->id]);

            // Enviar notificación al correo configurado para préstamos
            $loanEmail = env('MAIL_PRESTAMOS_EMAIL', 'irocha@zuma.com.pe');

            Notification::route('mail', $loanEmail)
                ->notify(new LoanRequestNotification($request->validated()));

            return response()->json([
                'message' => 'Su solicitud de préstamo ha sido enviada correctamente.',
                'success' => true,
                'id' => $contactRequest->id
            ], 201);
        } catch (Exception $e) {
            Log::error('Error in storeInternal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ocurrió un error al procesar su solicitud. Intente nuevamente.',
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function testEmail(){
        try {
            Log::info('Testing email configuration...');
            
            $testData = [
                'full_name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '123456789',
                'interested_product' => 'Facturas',
                'message' => 'This is a test message',
                'accepted_policy' => true
            ];

            $this->sendContactEmails($testData);

            return response()->json([
                'message' => 'Email test completed. Check logs for details.',
                'success' => true
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Email test failed.',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function sendContactEmails(array $data): void{
        try {
            $adminEmail = config('mail.admin_email', 'info@zuma.com.pe');
            $fromEmail = config('mail.from.address', 'notificaciones@zuma.com.pe');
            $fromName = config('mail.from.name', 'Zuma Inversiones');
            
            Log::info('Attempting to send contact notification', [
                'to' => $adminEmail,
                'from' => $fromEmail,
                'data' => $data
            ]);
            
            $emailContent = "Nueva solicitud de contacto:\n\n" .
                        "Nombre: {$data['full_name']}\n" .
                        "Email: {$data['email']}\n" .
                        "Teléfono: {$data['phone']}\n" .
                        "Producto de interés: {$data['interested_product']}\n" .
                        "Mensaje: {$data['message']}\n" .
                        "Aceptó políticas: " . ($data['accepted_policy'] ? 'Sí' : 'No') . "\n\n" .
                        "Enviado: " . now()->format('d/m/Y H:i:s');

            Mail::raw($emailContent, function ($message) use ($adminEmail, $fromEmail, $fromName) {
                $message->to($adminEmail)
                    ->subject('Nuevo contacto - Zuma Inversiones')
                    ->from($fromEmail, $fromName);
            });
            
            Log::info('Contact notification sent successfully', [
                'email' => $adminEmail
            ]);
            
        } catch (Exception $e) {
            Log::error('Error sending contact notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (config('app.debug')) {
                throw $e;
            }
        }
    }
    // Método para verificar el estado del sistema
    public function systemCheck()
    {
        $checks = [];

        // Verificar clases de Mail
        $checks['mail_classes'] = [
            'ContactToAdminMail' => class_exists('App\Mail\ContactToAdminMail'),
            'ContactToUserMail' => class_exists('App\Mail\ContactToUserMail'),
            'ContactToSecondaryMail' => class_exists('App\Mail\ContactToSecondaryMail')
        ];

        // Verificar configuración de mail
        try {
            $mailConfig = config('mail');
            $checks['mail_config'] = [
                'driver' => $mailConfig['default'] ?? null,
                'configured' => !empty($mailConfig['mailers'])
            ];
        } catch (Exception $e) {
            $checks['mail_config'] = ['error' => $e->getMessage()];
        }

        // Verificar modelo ContactRequest
        $checks['model'] = [
            'ContactRequest' => class_exists('App\Models\ContactRequest')
        ];

        return response()->json([
            'success' => true,
            'checks' => $checks
        ]);
    }
}