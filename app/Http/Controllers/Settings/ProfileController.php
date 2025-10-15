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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Notifications\InvestorEmailVerificationNotification;


class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function profile(Request $request)
    {
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

    public function resendEmailVerification(Request $request, string $id)
    {
        try {
            $customer = Investor::find(htmlspecialchars($id));


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

            $customer->notify(new InvestorEmailVerificationNotification());


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
    public function emailVerification(UpdateCustomerEmailVerificationRequest $request, $id, $hash)
    {
        try {
            $frontend = rtrim(env('CLIENT_APP_URL', 'https://zuma.com.pe'), '/');

            Log::info('EmailVerification: inicio', [
                'action'     => 'verify',
                'route_id'   => $id,
                'route_hash' => $hash,
                'full_url'   => $request->fullUrl(),
                'ip'         => $request->ip(),
                'agent'      => $request->userAgent(),
            ]);

            $validatedData = $request->validated();

            $investor = Investor::find($id);
            if (!$investor) {
                Log::warning('EmailVerification: usuario no encontrado', ['route_id' => $id]);

                // Browser -> redirige al frontend
                if (!$request->expectsJson()) {
                    return redirect()->away($this->frontendUrl('/email-verify', ['status' => 'not_found']));

                }
                return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
            }

            // Firma (relativa)
            $signatureIsValid = URL::hasValidSignature($request) || URL::hasValidSignature($request, false);
            Log::info('EmailVerification: check firma', ['valid_signature' => $signatureIsValid]);

            if (! $signatureIsValid) {
                Log::warning('EmailVerification: firma inválida/expirada', ['reason' => 'invalid_signature_or_expired']);
                return response()->json([
                    'success' => false,
                    'message' => 'Enlace de verificación inválido o expirado.'
                ], 400);
            }

            // Hash
            $expectedHash = sha1($investor->getEmailForVerification());
            $hashMatch = hash_equals($expectedHash, $hash);
            Log::info('EmailVerification: check hash', [
                'expected_hash' => $expectedHash,
                'received_hash' => $hash,
                'match'         => $hashMatch,
            ]);
            if (! $hashMatch) {
                Log::warning('EmailVerification: hash inválido');

                if (!$request->expectsJson()) {
                    return redirect()->away("{$frontend}/email-verify?status=invalid");
                }
                return response()->json(['success' => false, 'message' => 'Enlace de verificación inválido.'], 400);
            }

            Log::info('EmailVerification: estado previo', [
                'already_verified'  => $investor->hasVerifiedEmail(),
                'email_verified_at' => $investor->email_verified_at,
                'investor_id'       => $investor->id,
            ]);

            if ($investor->hasVerifiedEmail()) {
                if (!$request->expectsJson()) {
                    return redirect()->away($this->frontendUrl('/iniciar-sesion'));
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Tu correo ya estaba verificado.',
                    'data'    => $investor
                ], 200);
            }

            $investor->markEmailAsVerified();
            event(new Verified($investor));

            Log::info('EmailVerification: marcado como verificado', [
                'investor_id'       => $investor->id,
                'email_verified_at' => $investor->email_verified_at,
            ]);

            // Browser: redirige a tu frontend (página de éxito)
            if (!$request->expectsJson()) {
                return redirect()->away($this->frontendUrl('/iniciar-sesion'));
            }

            // API: responde JSON
            return response()->json([
                'success' => true,
                'message' => 'Tu cuenta ha sido activada correctamente.',
                'data'    => $investor
            ], 200);
        } catch (\Throwable $th) {
            Log::error('EmailVerification: excepción en verify', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            // Browser: redirige a error genérico del frontend
            if (!$request->expectsJson()) {
                $code = $th->getCode();
                return redirect()->away(rtrim(env('CLIENT_APP_URL', 'https://zuma.com.pe'), '/') . '/email-verify?status=error');
            }

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


    // Add this helper at the bottom of the class (before the closing brace)
    private function frontendUrl(string $path = '', array $query = []): string
    {
        // .env
        // FRONTEND_URL=http://localhost:5173
        // FRONTEND_BASE=/factoring
        $frontend = rtrim(env('CLIENT_APP_URL', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        $base     = '/' . trim(env('FRONTEND_BASE', ''), '/'); // e.g. /factoring
        if ($base === '/') $base = '';                      // no base configured

        $path = '/' . ltrim($path, '/');
        $url  = $frontend . $base . $path;

        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return $url;
    }
}
