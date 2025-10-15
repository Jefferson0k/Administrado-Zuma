<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Models\ContactRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class ContactRequestController extends Controller
{
    public function storeContactUs(StoreContactRequest $request)
    {
        try {
            // Primero, crear el registro
            $contactRequest = ContactRequest::create([
                ...$request->validated(),
                'status' => 'contact_us',
            ]);

            Log::info('Contact request created successfully', ['id' => $contactRequest->id]);

            // Intentar enviar emails solo si el registro se creó exitosamente
            //$this->sendContactEmails($request->validated());

            return response()->json([
                'message' => 'Contact request received. We will get back to you soon.',
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
                'message' => 'There was an error processing your request. Please try again.',
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function storeInternal(StoreContactRequest $request)
    {
        try {
            // Primero, crear el registro
            $contactRequest = ContactRequest::create([
                ...$request->validated(),
                'status' => 'internal',
            ]);
            
            

            
            Log::info('Internal contact request created successfully', ['id' => $contactRequest->id]);

            // Intentar enviar emails solo si el registro se creó exitosamente
            // $this->sendContactEmails($request->validated());

            return response()->json([
                'message' => 'Su mensaje ha sido enviado',
                'success' => true,
                'id' => $contactRequest->id
            ], 201);

        } catch (Exception $e) {
            Log::error('Error in storeInternal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'message' => 'There was an error processing your request. Please try again.',
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function sendContactEmails(array $data)
    {
        // Verificar configuración de Resend
        try {
            $resendKey = config('services.resend.key') ?? env('RESEND_API_KEY');
            $mailDriver = config('mail.default');
            
            Log::info('Email configuration check', [
                'mail_driver' => $mailDriver,
                'resend_key_exists' => !empty($resendKey),
                'from_address' => config('mail.from.address')
            ]);

            if (empty($resendKey)) {
                Log::warning('Resend API key not configured');
                return;
            }

        } catch (Exception $e) {
            Log::error('Email configuration error', ['error' => $e->getMessage()]);
            return;
        }

        // Enviar email al admin
        try {
            $adminMail = new \App\Mail\ContactToAdminMail($data);
            Mail::to('adminzuma@zuma.com.pe')->send($adminMail);
            Log::info('Admin email sent successfully');
        } catch (Exception $e) {
            Log::error('Failed to send admin email', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }

        // Enviar email de confirmación al usuario
        try {
            $userMail = new \App\Mail\ContactToUserMail($data);
            Mail::to($data['email'])->send($userMail);
            Log::info('User confirmation email sent successfully', ['email' => $data['email']]);
        } catch (Exception $e) {
            Log::error('Failed to send user email', [
                'error' => $e->getMessage(),
                'email' => $data['email'],
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }

        // Enviar email secundario
        try {
            $secondaryMail = new \App\Mail\ContactToSecondaryMail($data);
            Mail::to('jefersoncovenas7@gmail.com')->send($secondaryMail);
            Log::info('Secondary email sent successfully');
        } catch (Exception $e) {
            Log::error('Failed to send secondary email', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    // Método para probar la configuración de email sin crear registros
    public function testEmail()
    {
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