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
             Log::error("Error: " . $th->getMessage());
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

            Log::info('EmailVerification: start', [
                'id'   => $id,
                'hash' => $hash,
                'url'  => $request->fullUrl(),
            ]);

            // 1) Load investor
            $investor = \App\Models\Investor::find($id);
            if (!$investor) {
                Log::warning('EmailVerification: investor not found', ['id' => $id]);
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404)
                    : redirect()->away($this->frontendUrl('/email-verify', ['status' => 'not_found']));
            }

            // 2) Signature (RELATIVE only)
            $signatureIsValid = \Illuminate\Support\Facades\URL::hasValidSignature($request, false);
            Log::info('EmailVerification: signature', ['relative_valid' => $signatureIsValid]);

            if (!$signatureIsValid) {
                Log::warning('EmailVerification: invalid/expired signature');
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'Enlace de verificación inválido o expirado.'], 400)
                    : redirect()->away($this->frontendUrl('/email-verify', ['status' => 'invalid']));
            }

            // 3) Hash must match the email used at generation time
            $expectedHash = sha1($investor->getEmailForVerification());
            $hashMatch = hash_equals($expectedHash, $hash);
            Log::info('EmailVerification: hash check', [
                'expected' => $expectedHash,
                'got'      => $hash,
                'match'    => $hashMatch,
                'email'    => $investor->getEmailForVerification(),
            ]);
            if (!$hashMatch) {
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'Enlace de verificación inválido.'], 400)
                    : redirect()->away($this->frontendUrl('/email-verify', ['status' => 'invalid']));
            }

            // 4) If already verified, just go to login
            if ($investor->hasVerifiedEmail()) {
                Log::info('EmailVerification: already verified', ['investor_id' => $investor->id]);
                return $request->expectsJson()
                    ? response()->json(['success' => true, 'message' => 'Tu correo ya estaba verificado.'], 200)
                    : redirect()->away($this->frontendUrl('/iniciar-sesion'));
            }

            // 5) Force the verification timestamp and save
            //    (bypasses fillable; works even if model uses guarded)
            $now = now();
            $investor->forceFill(['email_verified_at' => $now])->save();

            // 6) Re-load fresh and confirm
            $investor->refresh();
            $confirmed = $investor->hasVerifiedEmail() || !is_null($investor->email_verified_at);

            Log::info('EmailVerification: saved', [
                'investor_id'       => $investor->id,
                'email_verified_at' => $investor->email_verified_at,
                'confirmed'         => $confirmed,
            ]);

            if (!$confirmed) {
                // Hard fallback if the trait/method behaves unexpectedly
                Log::error('EmailVerification: not confirmed after save (check DB column & migrations)');
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'No se pudo verificar el correo.'], 500)
                    : redirect()->away($this->frontendUrl('/email-verify', ['status' => 'error']));
            }

            // Fire event (optional but standard)
            event(new \Illuminate\Auth\Events\Verified($investor));

            // OK -> login page
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => 'Cuenta activada correctamente.'], 200)
                : redirect()->away($this->frontendUrl('/iniciar-sesion'));
        } catch (\Throwable $th) {
            Log::error('EmailVerification: exception', ['error' => $th->getMessage()]);
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $th->getMessage()], 500)
                : redirect()->away($this->frontendUrl('/email-verify', ['status' => 'error']));
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
