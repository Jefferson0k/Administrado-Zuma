<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Email\UpdateCustomerEmailVerificationRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Customer;
use App\Models\Investor;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Auth\Events\Verified;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function profile(Request $request){
        try {
            $customer = Auth::guard('customer')->user();
            return response()->json([
                'success' => true,
                'message' => null,
                'data' => $customer,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function resendEmailVerification(Request $request, string $id){
        try {
            $customer = Customer::find(htmlspecialchars($id));

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado.',
                ], 404);
            }

            if ($customer->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo ya está verificado.'
                ], 307);
            }

            $customer->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un correo de verificación.',
                'data' => $customer,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function emailVerification(UpdateCustomerEmailVerificationRequest $request, $id, $hash){
        try {
            $validatedData = $request->validated();
            
            $investor = Investor::find($id);
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado.',
                ], 404);
            }

            // Verificar expiración solo si se proporciona expires
            if (isset($validatedData['expires'])) {
                $expires = intval($validatedData['expires']);
                if (Carbon::now()->timestamp > $expires) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El enlace de verificación ha expirado.'
                    ], 400);
                }
            }

            $expectedHash = sha1($investor->getEmailForVerification());

            if (!hash_equals($expectedHash, $hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enlace de verificación inválido.'
                ], 400);
            }

            if ($investor->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Correo ya esta verificado.'
                ], 409);
            }

            $investor->markEmailAsVerified();
            event(new Verified($investor));

            return response()->json([
                'success' => true,
                'message' => 'Tu cuenta ha sido activada correctamente.',
                'data' => $investor
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
