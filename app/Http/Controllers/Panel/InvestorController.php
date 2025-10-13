<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investor\LoginInvestorRequest;
use App\Http\Requests\Investor\StoreInvestorRequest;
use App\Http\Requests\Investor\UpdateInvestorAvatarRequest;
use App\Http\Requests\Investor\UpdateInvestorConfirmAccountRequest;
use App\Http\Requests\Investor\UpdateInvestorEmailVerificationRequest;
use App\Http\Requests\Investor\UpdateInvestorPasswordRequest;
use App\Http\Resources\Factoring\Investor\InvestorResources;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Alias;
use App\Models\InvestorCode;
use App\Models\Movement;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\InvestorSpectroEvidence;
use App\Models\InvestorPepEvidence;
use Illuminate\Support\Facades\Log;
use App\Models\HistoryAprobadorInvestor;
use Throwable;
use Illuminate\Auth\Events\Registered;


class InvestorController extends Controller
{
    public function index(Request $request)
    {
        try {
            Gate::authorize('viewAny', Investor::class);

            $perPage = (int) $request->input('per_page', 15);
            $search  = $request->input('search', '');

            // ---- Sorting flexible con lista blanca y fallbacks
            $defaultSort = Schema::hasColumn('investors', 'created_at') ? 'created_at' : 'id';
            $allowed     = array_values(array_filter(
                ['name', 'email', 'created_at', 'id'],
                fn($c) => Schema::hasColumn('investors', $c)
            ));

            $sortBy  = $request->input('sort_by', $defaultSort);
            if (!in_array($sortBy, $allowed, true)) {
                $sortBy = $defaultSort;
            }
            $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            $query = Investor::query();

            // ---- Búsqueda (solo en columnas existentes para evitar errores)
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    if (Schema::hasColumn('investors', 'name'))         $q->orWhere('name', 'like', "%{$search}%");
                    if (Schema::hasColumn('investors', 'email'))        $q->orWhere('email', 'like', "%{$search}%");
                    if (Schema::hasColumn('investors', 'document'))     $q->orWhere('document', 'like', "%{$search}%");
                    if (Schema::hasColumn('investors', 'razon_social')) $q->orWhere('razon_social', 'like', "%{$search}%");
                });
            }

            // ---- Orden (más recientes primero por defecto) + desempate por id
            $query->orderBy($sortBy, $sortDir)->orderBy('id', 'desc');

            $investors = $query->paginate($perPage)->appends($request->query());

            return InvestorResources::collection($investors)
                ->additional(['total' => $investors->total()]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver los inversionistas.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar los inversionistas.'
            ], 500);
        }
    }

    public function showInvestor($id)
    {
        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('view', $investor);
            return response()->json([
                'data' => new InvestorResources($investor),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver este inversionista.'
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Inversionista no encontrado.'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener el inversionista.'
            ], 500);
        }
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // 'nacionalidad' => 'required|string|max:255',
            'first_last_name' => 'required|string|max:255',
            'second_last_name' => 'required|string|max:255',
            'document' => 'required|string|max:20|unique:investors,document',
        ]);
        $validated['type'] = 'cliente';
        $investor = Investor::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Inversionista creado correctamente.',
            'data' => $investor
        ], 201);
    }
    public function resendEmailVerification(Request $request, string $id)
    {
        try {
            $investor = Investor::find(htmlspecialchars($id));
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado.',
                ], 404);
            }
            if ($investor->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Correo ya esta verificado.'
                ], 307);
            }

            $investor->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un correo de verificación.',
                'data' => $investor
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {
        $investor = Investor::select([
            'id',
            'name',
            'first_last_name',
            'second_last_name',
            'document',
        ])->find($id);
        if (!$investor) {
            return response()->json([
                'success' => false,
                'message' => 'Inversionista no encontrado'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $investor
        ]);
    }
    public function update(Request $request, $id)
    {
        $investor = Investor::find($id);
        if (!$investor) {
            return response()->json([
                'success' => false,
                'message' => 'Inversionista no encontrado',
            ], 404);
        }
        $validated = $request->validate([
            'alias' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:investors,email,' . $id,
            'telephone' => 'sometimes|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
            'nacionalidad' => 'sometimes|string|max:255',
        ]);
        if (!empty($validated['alias'])) {
            $aliasSlug = Str::slug($validated['alias']);
            $aliasProhibido = Alias::pluck('slug')->some(function ($prohibido) use ($aliasSlug) {
                return Str::contains($aliasSlug, $prohibido);
            });
            if ($aliasProhibido) {
                return response()->json([
                    'success' => false,
                    'message' => 'El alias ingresado no está permitido, por favor elige otro.',
                ], 422);
            }
        }
        $investor->fill($validated);
        if (!empty($validated['password'])) {
            $investor->password = Hash::make($validated['password']);
        }
        $wasEmailChanged = $investor->isDirty('email');
        $investor->save();
        if ($wasEmailChanged) {
            $investor->email_verified_at = null;
            $investor->save();
            $investor->sendEmailVerificationNotification();
        }
        return response()->json([
            'success' => true,
            'message' => 'Datos actualizados correctamente',
            'data' => $investor->only([
                'name',
                'first_last_name',
                'second_last_name',
                'document',
                'alias',
                'email',
                'telephone',
            ]),
        ]);
    }
    public function register(StoreInvestorRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $aliasSlug = Str::slug($validatedData['alias']);
            $aliasProhibido = Alias::pluck('slug')->some(function ($prohibido) use ($aliasSlug) {
                return Str::contains($aliasSlug, $prohibido);
            });
            if ($aliasProhibido) {
                return response()->json([
                    'success' => false,
                    'message' => 'El alias ingresado no está permitido, por favor elige otro.',
                ], 422);
            }
            DB::beginTransaction();
            /** @var \App\Models\Investor $investor */
            $investor = Investor::create([
                'name' => $request->name,
                'first_last_name' => $request->first_last_name,
                'second_last_name' => $request->second_last_name,
                'alias' => $request->alias,
                'tipo_documento_id' => $request->tipo_documento_id,
                'document' => $request->document,
                'nacionalidad' => $request->nacionalidad,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'telephone' => $request->telephone,
            ]);
            $investor->createBalance('PEN', 0);
            $investor->createBalance('USD', 0);
            $investorCode = InvestorCode::create([
                'codigo' => 'TEMP',
                'usado' => true,
                'investor_id' => $investor->id,
            ]);
            $correlativo = str_pad($investorCode->id, 6, '0', STR_PAD_LEFT);
            $codigo = "INV-0000-{$correlativo}";
            $investorCode->codigo = $codigo;
            $investorCode->save();
            $investor->codigo = $codigo;
            $investor->save();
            Log::info("Nuevo código de inversor generado: {$codigo} para el inversor ID: {$investor->id}");
            
            DB::commit();

            $investor->sendEmailVerificationNotification();
            return response()->json([
                'success' => true,
                'message' => 'Te has registrado con éxito, te enviaremos un correo para confirmar tu cuenta.',
                'data' => [
                    'codigo' => $codigo,
                ],
            ], 201);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function login(LoginInvestorRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $investor = Investor::where('email', $request->email)->first();

            if (!$investor || !Hash::check($request->password, $investor->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }


            return response()->json([
                'success' => true,
                'message' => "Bienvenido {$investor->name}",
                'data' => $investor,
                'api_token' => $investor->createToken('token')->plainTextToken,
                'user_type' => $investor->type,
                'redirect_route' => $this->getRedirectRoute($investor->type)
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => null,
            ], 500);
        }
    }
    private function getRedirectRoute($userType)
    {
        switch ($userType) {
            case 'cliente':
                return '/cliente';
            case 'inversionista':
            case 'mixto':
                return '/hipotecas';
            default:
                return '/hipotecas';
        }
    }





    public function logout(Request $request)
    {
        $token = PersonalAccessToken::findToken($request->bearerToken());
        $investor = $token->tokenable;
        $investor->tokens()->delete();
        return response()->json([
            'logout' => true,
        ]);
    }
    public function profile(Request $request)
    {
        try {
            $investor = Auth::user();
            $investor->loadCount('bankAccounts');
            $investor->movements_count =  Movement::where('investor_id', $investor->id)->count();
            return response()->json([
                'success' => true,
                'message' => null,
                'data' => $investor,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => null,
            ], 500);
        }
    }





    public function updateAvatar(UpdateInvestorAvatarRequest $request)
    {
        $validatedData = $request->validated();
        $avatar = $validatedData['avatar'];
        try {
            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();
            if ($investor->getProfilePhotoPathRaw()) {
                Storage::delete($investor->getProfilePhotoPathRaw());
            }
            $path = Storage::putFile('avatares', $avatar, [
                'ContentType' => $avatar->getClientMimeType(),
                'metadata' => [
                    'investor_document' => $investor->document,
                    'investor_name' => $investor->name,
                    'investor_last_name' => $investor->first_last_name,
                    'investor_second_last_name' => $investor->second_last_name,
                ]
            ]);
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo.',
                ], 500);
            }
            DB::beginTransaction();
            $investor->update([
                'profile_photo_path' => Storage::path($path),
            ]);
            $investor->avatar()->updateOrCreate([
                'investor_id' => $investor->id,
            ], [
                'avatar_type' => $validatedData['avatar_type'],
                'clothing_color' => $validatedData['clothing_color'],
                'background_color' => $validatedData['background_color'],
                'medal' => $validatedData['medal'],
                'medal_position' => $validatedData['medal_position'],
                'hat' => $validatedData['hat'],
                'hat_position' => $validatedData['hat_position'],
                'trophy' => $validatedData['trophy'],
                'other' => $validatedData['other'],
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Tu avatar ha sido actualizado correctamente.',
                'data' => null,
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error interno del servidor",
            ], 500);
        }
    }

    public function updatePassword(UpdateInvestorPasswordRequest $request)
    {
        try {
            $validatedData = $request->validated();

            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $investor->update([
                'password' => Hash::make($validatedData['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.',
                'data' => $investor
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email ingresado no es válido.',
        ]);

        try {
            /**
             * Busca un inversor por su correo electrónico proporcionado en la solicitud.
             * 
             * Si no se encuentra un inversor con el correo dado, retorna una respuesta JSON
             * con un mensaje de error y un código de estado 404.
             *
             * @param \Illuminate\Http\Request $request La solicitud HTTP que contiene el email.
             * @return \Illuminate\Http\JsonResponse Respuesta JSON indicando éxito o error.
             * @var \App\Models\User $investor
             */
            $investor = Investor::where('email', $request->email)->first();
            if (!$investor) return response()->json([
                'success' => false,
                'message' => 'No se ha encontrado un usuario con ese email.',
            ], 404);

            $status = Password::broker('investors')
                ->sendResetLink($request->only('email'));

            /**
             * Checks if the password reset request has been throttled.
             * If the status is not Password::RESET_THROTTLED, returns a JSON response indicating
             * that the user has exceeded the allowed number of attempts and should try again later.
             *
             * @return \Illuminate\Http\JsonResponse JSON response with success status, error message, and HTTP 429 status code.
             */
            if ($status === Password::RESET_THROTTLED) return response()->json([
                'success' => false,
                'message' => 'Has superado el límite de intentos. vuelve a intentarlo en unos minutos.',
            ], 429);

            /**
             * Verifica si el enlace de restablecimiento de contraseña fue enviado correctamente.
             * 
             * Si el estado de la operación no es exitoso (Password::RESET_LINK_SENT), retorna una respuesta JSON
             * indicando el fallo en el envío del correo de recuperación de contraseña, con un código de estado 400.
             *
             * @return \Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la operación.
             */
            if ($status !== Password::RESET_LINK_SENT) return response()->json([
                'success' => false,
                'message' => 'No se ha podido enviar el correo de recuperación de contraseña.',
            ], 400);


            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un correo de recuperación de contraseña.',
                'data' => null,
            ]);
        } catch (Throwable $th) {
            $errorCode = $th->getCode();
            $errorCode = $errorCode < 100 || $errorCode > 500 ? 500 : $errorCode;
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => null,
            ], $errorCode);
        }
    }
    public function resetPasswordRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email ingresado no es válido.',
            'token.required' => 'El token es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La contraseña no coincide.',
            'password.regex' => 'La Contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.',
        ]);

        try {

            /** @var \App\Models\User $investor */
            $investor = Investor::where('email', $request->email)->first();

            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el usuario con ese correo electrónico.'
                ], 404);
            }

            // Usa el broker correcto para verificar el token
            $response = Password::broker('investors')->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->save();

                    // Opcional: eliminar todos los tokens del usuario
                    DB::table('password_reset_tokens')
                        ->where('email', $user->email)
                        ->delete();
                }
            );
            // TODO: send email notification

            if ($response !== Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.',
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }





    public function emailVerification(UpdateInvestorEmailVerificationRequest $request, $id, $hash)
    {
        try {
            $validatedData = $request->validated();
            $investor = Investor::find($id);
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado.',
                ], 404);
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
                ], 307);
            }
            $investor->markEmailAsVerified();
            event(new Verified($investor));
            return response()->json([
                'success' => true,
                'message' => 'Tu cuenta ha sido activada correctamente.',
                'data' => $investor
            ], 201);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function updateConfirmAccount(UpdateInvestorConfirmAccountRequest $request)
    {
        try {
            $validated = $request->validated();

            // Disk S3 (MinIO)
            $disk = Storage::disk('s3');

            // Sube los archivos a MinIO. El tercer parámetro 'public' intenta marcar visibilidad pública
            // (en MinIO la visibilidad depende de la política del bucket).
            $documentFrontKey = $disk->putFile('inversores/documentos', $validated['document_front'], 'public');
            $documentBackKey  = $disk->putFile('inversores/documentos', $validated['document_back'], 'public');
            $photoKey         = $disk->putFile('inversores/fotos',      $validated['investor_photo_path'], 'public');

            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            // Guarda SOLO la clave/llave del objeto S3 (NO Storage::path)
            $investor->update([
                'is_pep'              => $validated['is_pep'],
                'has_relationship_pep' => $validated['has_relationship_pep'],
                'department'          => $validated['department'],
                'province'            => $validated['province'],
                'district'            => $validated['district'],
                'address'             => $validated['address'],
                'document_front'      => $documentFrontKey,
                'document_back'       => $documentBackKey,
                'investor_photo_path' => $photoKey,
            ]);

            // Si el bucket es público y config('filesystems.disks.s3.url') está bien seteado,
            // puedes devolver URLs públicas directas:
            $publicFrontUrl = $disk->url($documentFrontKey);
            $publicBackUrl  = $disk->url($documentBackKey);
            $publicPhotoUrl = $disk->url($photoKey);

            // Si el bucket es privado, usa URLs firmadas:
            // $publicFrontUrl = $disk->temporaryUrl($documentFrontKey, now()->addMinutes(15));
            // $publicBackUrl  = $disk->temporaryUrl($documentBackKey,  now()->addMinutes(15));
            // $publicPhotoUrl = $disk->temporaryUrl($photoKey,         now()->addMinutes(15));

            return response()->json([
                'success' => true,
                'message' => 'Tu cuenta ha sido confirmada correctamente.',
                'data'    => [
                    'user' => $investor->fresh(),
                    'files' => [
                        'document_front'      => $documentFrontKey,
                        'document_back'       => $documentBackKey,
                        'investor_photo_path' => $photoKey,
                    ],
                    'urls' => [
                        'document_front'      => $publicFrontUrl,
                        'document_back'       => $publicBackUrl,
                        'investor_photo_path' => $publicPhotoUrl,
                    ],
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function lastInvoiceInvested()
    {
        /** @var \App\Models\Investor $investor */
        $investor = Auth::user();
        try {
            $lastInvoiceInvested = $investor->investments()
                ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
                ->join('companies', 'invoices.company_id', '=', 'companies.id')
                ->select(
                    'invoices.*',
                    'companies.name as company_name',
                    'companies.risk as company_risk',
                    DB::raw('(invoices.financed_amount / 100) as financed_amount'),
                    DB::raw('(invoices.financed_amount_by_garantia / 100) as financed_amount_by_garantia')
                )
                ->orderBy('investments.created_at', 'desc')
                ->first();
            if (!$lastInvoiceInvested) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró ninguna factura para este inversor.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $lastInvoiceInvested,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }


    public function showcliente($id)
    {
        $investor = Investor::select([
            'id',
            'name',
            'first_last_name',
            'second_last_name',
            'document',
        ])->find($id);
        if (!$investor) {
            return response()->json([
                'success' => false,
                'message' => 'Inversionista no encontrado'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $investor
        ]);
    }

    public function updatecliente(Request $request, $id)
    {
        $investor = Investor::find($id);
        if (!$investor) {
            return response()->json([
                'success' => false,
                'message' => 'Inversionista no encontrado',
            ], 404);
        }
        $validated = $request->validate([
            'alias' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:investors,email,' . $id,
            'telephone' => 'sometimes|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);
        $investor->fill($validated);
        if (!empty($validated['password'])) {
            $investor->password = Hash::make($validated['password']);
        }
        $wasEmailChanged = $investor->isDirty('email');
        $investor->save();
        if ($wasEmailChanged) {
            $investor->email_verified_at = null;
            $investor->save();
            $investor->sendEmailVerificationNotification();
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos actualizados correctamente',
            'data' => $investor->only([
                'name',
                'first_last_name',
                'second_last_name',
                'document',
                'alias',
                'email',
                'telephone',
            ]),
        ]);
    }


    public function rechazar(Request $request, $id)
    {
        try {
            $request->validate([
                'approval1_comment' => 'nullable|string',
            ]);

            $investor = Investor::findOrFail($id);

            $investor->update([
                'approval1_status' => 'rejected',
                'approval1_by' => Auth::id(),
                'approval1_comment' => $request->approval1_comment,
                'approval1_at' => now(),
                'status' => 'rejected',
                // Limpiar datos si es necesario
                'district' => null,
                'province' => null,
                'department' => null,
                'address' => null,
                'document_front' => null,
                'document_back' => null,
                'investor_photo_path' => null,
                'updated_by' => Auth::id(),
            ]);

            // Mandar correo de rechazo
            $investor->sendAccountRejectedEmailNotification();

            return response()->json([
                'message' => 'Inversionista rechazado y notificación enviada correctamente.',
                'data' => $investor
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar inversionista.',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    public function observarPrimeraValidacion(Request $request, $id)
    {
        $request->validate([
            'approval1_comment'   => 'nullable|string',
            'notify_templates'    => 'sometimes|array',
            'notify_templates.*'  => 'in:general_observation,dni,investor_photo,evidencia_pep',
        ]);




        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('approve1', $investor);
            DB::transaction(function () use ($investor, $request) {
                // 1) Delete files from MinIO if present
                $this->deleteFromMinioSafe($investor->document_front);
                $this->deleteFromMinioSafe($investor->document_back);
                $this->deleteFromMinioSafe($investor->investor_photo_path);

                // 2) Update investor status + clear file fields
                $investor->update([
                    'approval1_status'      => 'observed',
                    'approval1_by'          => Auth::id(),
                    'approval1_comment'     => $request->approval1_comment,

                    'approval1_at'          => now(),
                    'status'                => 'proceso',
                    'updated_by'            => Auth::id(),
                    'document_front'        => null,
                    'document_back'         => null,
                    'investor_photo_path'   => null,
                ]);
            });



            HistoryAprobadorInvestor::create([
                'investor_id' => $investor->id,
                'aprobador_primera_validacion' => Auth::id(),
                'approval1_by'          => Auth::id(),
                'approval1_at'          => now(),
                'approval1_status'      => 'observed',
                'approval1_comment'     => $request->approval1_comment,
            ]);



            $templates = collect($request->input('notify_templates', []))->unique()->values();

            if ($templates->isEmpty()) {
                // Sin selección: enviar el correo genérico existente.
                $investor->sendAccountObservedEmailNotification();
            } else {
                foreach ($templates as $tpl) {
                    switch ($tpl) {
                        case 'general_observation':
                            $investor->sendAccountObservedEmailNotification();
                            break;

                        case 'dni':

                            Log::info('Sending DNI observation email to investor', ['investor_id' => $investor->id]);
                            // Usa tu método/mailable específico para DNI
                            $investor->sendAccountObservedDNIEmailNotification();
                            break;

                        case 'investor_photo':
                            if (method_exists($investor, 'sendAccountObservedFotoNotification')) {
                                $investor->sendAccountObservedFotoNotification();
                            } else {
                                // Mail::to($investor->email)->queue(new \App\Mail\Investor\RequestInvestorPhotoResubmissionMail($investor));
                                $investor->sendAccountObservedEmailNotification(); // fallback
                            }
                            break;

                        case 'evidencia_pep':
                            if (method_exists($investor, 'sendAccountObservedPepEvidenceNotification')) {
                                $investor->sendAccountObservedPepEvidenceNotification();
                            } else {
                                // Mail::to($investor->email)->queue(new \App\Mail\Investor\RequestPepEvidenceResubmissionMail($investor));
                                $investor->sendAccountObservedEmailNotification(); // fallback
                            }
                            break;
                    }
                }
            }



            // 3) Notify AFTER commit

            return response()->json([
                'message' => 'Inversionista marcado como observado (en proceso). Archivos eliminados y notificación enviada.',
                'data'    => $investor->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error al marcar inversionista como observado', [
                'investor_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Error al marcar como observado.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tries to delete a file from the s3 (MinIO) disk, regardless of how the path was stored.
     * Accepts: absolute MinIO URL, /s3/... URL, path with bucket, or plain key.
     */
    protected function deleteFromMinioSafe(?string $storedPath): void
    {
        if (empty($storedPath)) return;

        $key = $this->extractS3Key($storedPath);
        if (!$key) return;

        try {
            // Avoid exceptions if it doesn’t exist
            if (Storage::disk('s3')->exists($key)) {
                Storage::disk('s3')->delete($key);
            }
        } catch (\Throwable $e) {
            // Swallow errors to avoid blocking the main flow; log if you want:
            // \Log::warning('MinIO delete failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }



    public function aprobarPrimeraValidacion(Request $request, $id)
    {


        try {
            $request->validate([
                'approval1_comment' => 'nullable|string',
            ]);
            $investor = Investor::findOrFail($id);
            Gate::authorize('approve1', $investor);
            $investor->update([
                'approval1_status' => 'approved',
                'approval1_by'     => Auth::id(),
                'approval1_comment' => $request->approval1_comment,
                'approval1_at'     => now(),
                'updated_by'       => Auth::id(),
            ]);

            HistoryAprobadorInvestor::create([
                'investor_id' => $investor->id,
                'aprobador_primera_validacion' => Auth::id(),
                'approval1_by'          => Auth::id(),
                'approval1_at'          => now(),
                'approval1_status'      => 'approved',
                'approval1_comment'     => $request->approval1_comment,
            ]);

            return response()->json([
                'message' => 'Primera validación aprobada correctamente.',
                'data'    => $investor,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error en la primera validación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function rechazarPrimeraValidacion(Request $request, $id)
    {
        try {
            $request->validate([
                'approval1_comment' => 'required|string',
            ]);
            $investor = Investor::findOrFail($id);
            Gate::authorize('approve1', $investor);
            $investor->update([
                'approval1_status' => 'rejected',
                'approval1_by'     => Auth::id(),
                'approval1_comment' => $request->approval1_comment,
                'approval1_at'     => now(),
                'status'           => 'rejected',
                // Limpiar datos sensibles si aplica
                'district'         => null,
                'province'         => null,
                'department'       => null,
                'address'          => null,
                'document_front'   => null,
                'document_back'    => null,
                'updated_by'       => Auth::id(),
            ]);
            $investor->sendAccountRejectedEmailNotification();


            HistoryAprobadorInvestor::create([
                'investor_id' => $investor->id,
                'aprobador_primera_validacion' => Auth::id(),
                'approval1_by'          => Auth::id(),
                'approval1_at'          => now(),
                'approval1_status'      => 'rejected',
                'approval1_comment'     => $request->approval1_comment,
            ]);

            return response()->json([
                'message' => 'Primera validación rechazada correctamente.',
                'data'    => $investor,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar en primera validación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function comentarPrimeraValidacion(Request $request, $id)
    {
        try {
            $request->validate([
                'approval1_comment' => 'required|string',
            ]);
            $investor = Investor::findOrFail($id);
            Gate::authorize('approve1', $investor);
            $investor->update([
                'approval1_comment' => $request->approval1_comment,
                'approval1_by'      => Auth::id(),
                'approval1_at'      => now(),
                'updated_by'        => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Comentario guardado correctamente.',
                'data'    => $investor,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar el comentario.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    // ===============================
    // SEGUNDA VALIDACIÓN
    // ===============================


    public function aprobarSegundaValidacion(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'approval2_comment' => 'nullable|string',
            ]);

            $investor = Investor::findOrFail($id);
            Gate::authorize('approve2', $investor);


            $investor->update([
                'approval2_status'  => 'approved',
                'approval2_by'      => Auth::id(),
                'approval2_comment' => $validated['approval2_comment'] ?? null,
                'approval2_at'      => now(),
                'status'            => 'validated',
                'updated_by'        => Auth::id(),
            ]);

            $investor->sendAccountApprovedEmailNotification();


            $history  = HistoryAprobadorInvestor::where('investor_id', $investor->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();


            $history?->update([
                'approval2_by'      => Auth::id(),
                'approval2_at'      => now(),
                'approval2_status'  => 'approved',
                'approval2_comment' => $request->approval2_comment,
            ]);




            return response()->json([
                'message' => 'Segunda validación aprobada correctamente.',
                'data'    => $investor,
            ], 200);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al aprobar en segunda validación.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function observarSegundaValidacion(Request $request, $id)
    {
        $request->validate([
            'approval2_comment' => 'required|string',
        ]);

        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('approve2', $investor);
            DB::transaction(function () use ($investor, $request) {
                // 1) Delete files from MinIO if present
                $this->deleteFromMinioSafe($investor->document_front);
                $this->deleteFromMinioSafe($investor->document_back);
                $this->deleteFromMinioSafe($investor->investor_photo_path);

                // 2) Update flags + clear file columns
                $investor->update([
                    // 👇 Volver a la PRIMERA validación
                    'approval1_status'     => 'observed',
                    'approval1_comment'    => $request->approval2_comment, // opcional: prefijar si deseas
                    'approval1_at'         => now(),

                    // 👇 Limpiar estado de SEGUNDA validación (ya no debe estar activa)
                    'approval2_status'     => null,
                    'approval2_by'         => null,
                    'approval2_comment'    => null,
                    'approval2_at'         => null,

                    // 👇 Mismo comportamiento que en observación de primera
                    'status'               => 'proceso',
                    'updated_by'           => Auth::id(),

                    // 👇 Se vuelven a solicitar archivos
                    'document_front'       => null,
                    'document_back'        => null,
                    'investor_photo_path'  => null,
                ]);
            });



            $history  = HistoryAprobadorInvestor::where('investor_id', $investor->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();


            $history?->update([
                'approval2_by'      => Auth::id(),
                'approval2_at'      => now(),
                'approval2_status'  => 'observed',
                'approval2_comment' => $request->approval2_comment,
            ]);


            // Notificación si aplica
            // $investor->sendAccountObservedEmailNotification($request->approval2_comment);
            $templates = collect($request->input('notify_templates', []))->unique()->values();

            if ($templates->isEmpty()) {
                // Sin selección: enviar el correo genérico existente.
                $investor->sendAccountObservedEmailNotification();
            } else {
                foreach ($templates as $tpl) {
                    switch ($tpl) {
                        case 'general_observation':
                            $investor->sendAccountObservedEmailNotification();
                            break;

                        case 'dni':
                            Log::info('Sending DNI observation email to investor', ['investor_id' => $investor->id]);
                            // Usa tu método/mailable específico para DNI
                            $investor->sendAccountObservedDNIEmailNotification();
                            break;
                            
                                // Mail::to($investor->email)->queue(new \App\Mail\Investor\RequestDniResubmissionMail($investor));
                            

                        case 'investor_photo':
                            if (method_exists($investor, 'sendAccountObservedFotoNotification')) {
                                $investor->sendAccountObservedFotoNotification();
                            } else {
                                // Mail::to($investor->email)->queue(new \App\Mail\Investor\RequestInvestorPhotoResubmissionMail($investor));
                                $investor->sendAccountObservedEmailNotification(); // fallback
                            }
                            break;

                        case 'evidencia_pep':
                            if (method_exists($investor, 'sendAccountObservedPepEvidenceNotification')) {
                                $investor->sendAccountObservedPepEvidenceNotification();
                            } else {
                                // Mail::to($investor->email)->queue(new \App\Mail\Investor\RequestPepEvidenceResubmissionMail($investor));
                                $investor->sendAccountObservedEmailNotification(); // fallback
                            }
                            break;
                    }
                }
            }


            return response()->json([
                'message' => 'Inversionista observado en segunda validación. Archivos eliminados.',
                'data'    => $investor->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error al marcar inversionista como observado en segunda validación', [
                'investor_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Error al observar en segunda validación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function rechazarSegundaValidacion(Request $request, $id)
    {
        try {
            $request->validate([
                'approval2_comment' => 'required|string',
            ]);
            $investor = Investor::findOrFail($id);
            Gate::authorize('approve2', $investor);
            $investor->update([
                'approval2_status'  => 'rejected',
                'approval2_by'      => Auth::id(),
                'approval2_comment' => $request->approval2_comment,
                'approval2_at'      => now(),
                'status'            => 'rejected',
                // Limpiar datos sensibles si aplica
                'district'          => null,
                'province'          => null,
                'department'        => null,
                'address'           => null,
                'document_front'    => null,
                'document_back'     => null,
                'updated_by'        => Auth::id(),
            ]);
            $investor->sendAccountRejectedEmailNotification();


            $history  = HistoryAprobadorInvestor::where('investor_id', $investor->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();


            $history?->update([
                'approval2_by'      => Auth::id(),
                'approval2_at'      => now(),
                'approval2_status'  => 'rejected',
                'approval2_comment' => $request->approval2_comment,
            ]);

            return response()->json([
                'message' => 'Segunda validación rechazada correctamente.',
                'data'    => $investor,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar en segunda validación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function comentarSegundaValidacion(Request $request, $id)
    {
        try {
            $request->validate([
                'approval2_comment' => 'required|string',
            ]);
            $investor = Investor::findOrFail($id);

            Gate::authorize('approve2', $investor);
            $investor->update([
                'approval2_comment' => $request->approval2_comment,
                'approval2_by'      => Auth::id(),
                'approval2_at'      => now(),
                'updated_by'        => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Comentario de segunda validación guardado correctamente.',
                'data'    => $investor,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar comentario en segunda validación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function uploadDocumentFront(Request $request, $id)
    {
        try {
            $request->validate([
                'document_front' => 'required|file|image|mimes:jpg,jpeg,png|max:5120'
            ]);

            $investor = Investor::findOrFail($id);
            if ($investor->document_front && Storage::exists($investor->document_front)) {
                Storage::delete($investor->document_front);
            }

            // Guardar nuevo documento
            $document_front_path = Storage::putFile('inversores/documentos', $request->file('document_front'));

            $investor->update([
                'document_front' => Storage::path($document_front_path),

            ]);

            return response()->json([
                'message' => 'Documento frontal actualizado correctamente.',
                'data' => $investor,
            ], 200);
        } catch (\Exception $e) {

            Log::error('Error al subir documento frontal', [
                'investor_id' => $id,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error al subir documento frontal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir nuevo documento posterior del DNI
     */
    public function uploadDocumentBack(Request $request, $id)
    {
        try {
            $request->validate([
                'document_back' => 'required|file|image|mimes:jpg,jpeg,png|max:5120'
            ]);

            $investor = Investor::findOrFail($id);

            // Eliminar archivo anterior si existe
            if ($investor->document_back && Storage::exists($investor->document_back)) {
                Storage::delete($investor->document_back);
            }

            // Guardar nuevo documento
            $document_back_path = Storage::putFile('inversores/documentos', $request->file('document_back'));

            $investor->update([
                'document_back' => Storage::path($document_back_path),
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Documento posterior actualizado correctamente.',
                'data' => $investor,
            ], 200);
        } catch (\Exception $e) {

            Log::error('Error al subir documento frontal', [
                'investor_id' => $id,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error al subir documento posterior.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir nueva foto del inversionista
     */
    public function uploadInvestorPhoto(Request $request, $id)
    {
        try {
            $request->validate([
                'investor_photo_path' => 'required|file|image|mimes:jpg,jpeg,png|max:5120'
            ]);

            $investor = Investor::findOrFail($id);

            // Eliminar archivo anterior si existe
            if ($investor->investor_photo_path && Storage::exists($investor->investor_photo_path)) {
                Storage::delete($investor->investor_photo_path);
            }

            // Guardar nueva foto
            $investor_photo_path = Storage::putFile('inversores/fotos', $request->file('investor_photo_path'));

            $investor->update([
                'investor_photo_path' => Storage::path($investor_photo_path),

            ]);

            return response()->json([
                'message' => 'Foto del inversionista actualizada correctamente.',
                'data' => $investor,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al subir documento frontal', [
                'investor_id' => $id,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error al subir foto del inversionista.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function approvalHistory($id)
    {
        $rows = HistoryAprobadorInvestor::query()
            ->where('investor_id', $id)
            ->with([
                'approval1By:id,name',
                'approval2By:id,name',
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }






    public function observarDniFrontal(Request $request, $id)
    {
        $request->validate([
            'approval1_comment' => 'required|string',
        ]);

        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('observednifront', $investor);

            $prefixedComment = '[DNI - Parte Frontal] ' . $request->approval1_comment;

            DB::transaction(function () use ($investor, $prefixedComment) {
                // 1) Borrar archivo en MinIO y limpiar columna
                $this->deleteFromMinioSafe($investor->document_front);

                // 2) Actualizar flags de 1ra validación y estado
                $investor->update([
                    'approval1_status'   => 'observed',
                    'approval1_by'       => Auth::id(),
                    'approval1_comment'  => $prefixedComment,
                    'approval1_at'       => now(),
                    'status'             => 'proceso',
                    'updated_by'         => Auth::id(),
                    'document_front'     => null, // limpiar columna
                ]);
            });

            // 3) Notificar
            $investor->sendAccountObservedEmailNotification();

            return response()->json([
                'message' => 'Observación registrada para DNI frontal; archivo eliminado y notificación enviada.',
                'data'    => $investor->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al observar DNI frontal.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Observa y limpia el DNI posterior.
     */
    public function observarDniPosterior(Request $request, $id)
    {
        $request->validate([
            'approval1_comment' => 'required|string',
        ]);

        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('observedniback', $investor);
            $prefixedComment = '[DNI - Parte Posterior] ' . $request->approval1_comment;

            DB::transaction(function () use ($investor, $prefixedComment) {
                // 1) Borrar archivo en MinIO y limpiar columna
                $this->deleteFromMinioSafe($investor->document_back);

                // 2) Actualizar flags de 1ra validación y estado
                $investor->update([
                    'approval1_status'   => 'observed',
                    'approval1_by'       => Auth::id(),
                    'approval1_comment'  => $prefixedComment,
                    'approval1_at'       => now(),
                    'status'             => 'proceso',
                    'updated_by'         => Auth::id(),
                    'document_back'      => null, // limpiar columna
                ]);
            });

            // 3) Notificar
            $investor->sendAccountObservedEmailNotification();

            return response()->json([
                'message' => 'Observación registrada para DNI posterior; archivo eliminado y notificación enviada.',
                'data'    => $investor->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al observar DNI posterior.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Observa y limpia la foto del inversionista.
     */
    public function observarFotoInversionista(Request $request, $id)
    {
        $request->validate([
            'approval1_comment' => 'required|string',
        ]);

        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('observefoto', $investor);
            $prefixedComment = '[Foto del Inversionista] ' . $request->approval1_comment;

            DB::transaction(function () use ($investor, $prefixedComment) {
                // 1) Borrar archivo en MinIO y limpiar columna
                $this->deleteFromMinioSafe($investor->investor_photo_path);

                // 2) Actualizar flags de 1ra validación y estado
                $investor->update([
                    'approval1_status'      => 'observed',
                    'approval1_by'          => Auth::id(),
                    'approval1_comment'     => $prefixedComment,
                    'approval1_at'          => now(),
                    'status'                => 'proceso',
                    'updated_by'            => Auth::id(),
                    'investor_photo_path'   => null, // limpiar columna
                ]);
            });

            // 3) Notificar
            $investor->sendAccountObservedFotoNotification();

            return response()->json([
                'message' => 'Observación registrada para la foto del inversionista; archivo eliminado y notificación enviada.',
                'data'    => $investor->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al observar la foto del inversionista.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }




    protected function extractS3Key(string $raw): ?string
    {
        $norm = trim(str_replace('\\', '/', $raw));
        if ($norm === '') return null;

        // /s3/KEY...
        if (str_starts_with($norm, '/s3/')) {
            return ltrim(substr($norm, 4), '/');
        }

        // Full URL
        if (preg_match('#^https?://#i', $norm)) {
            $parts = parse_url($norm);
            if (!empty($parts['path'])) {
                $path = ltrim($parts['path'], '/'); // "mi-bucket/inversores/documentos/file.jpg"
                // Mantén solo la parte a partir de investors|inversores si existe
                if (preg_match('#(?:^[^/]+/)?((?:investors|inversores)/.+)$#i', $path, $m)) {
                    return $m[1];
                }
                // Fallback: quita el primer segmento (bucket)
                $segments = explode('/', $path);
                if (count($segments) > 1) {
                    array_shift($segments);
                    return implode('/', $segments);
                }
                return $path;
            }
            return null;
        }

        // Rutas que incluyen el key después de investors|inversores
        if (preg_match('#(investors|inversores)/.+$#i', $norm, $m)) {
            return ltrim($m[0], '/');
        }

        // Fallback: tratar como key directo
        return ltrim($norm, '/');
    }




    // --- SPECTRO ---
    public function uploadSpectroEvidence(Request $request, $id)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'notes' => 'nullable|string|max:500',
            ]);

            $investor = Investor::findOrFail($id);

            Gate::authorize('uploadSpectro', $investor);

            $file = $request->file('file');
            $disk = 's3';

            $path = $file->store("investors/{$investor->id}/spectro", $disk); // <= MinIO/S3
            // Persist
            $evidence = InvestorSpectroEvidence::create([
                'investor_id'   => $investor->id,
                'file_path'       => $path,
                'storage_disk'  => $disk,
                'original_name' => $file->getClientOriginalName(),
                'extension'     => $file->getClientOriginalExtension(),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'notes'          => $request->input('notes'),
                'uploaded_by'   => Auth::id(),
            ]);
            $publicUrl = url('/s3/' . ltrim($evidence->file_path, '/'));

            return response()->json([
                'success' => true,
                'message' => 'Evidencia PEP subida correctamente.',
                'data'    => [
                    'id'           => $evidence->id,
                    'name'         => $evidence->original_name,
                    'mime'         => $evidence->mime,
                    'size'         => $evidence->size,
                    'url'          => $publicUrl,
                    'download_url' => $publicUrl,
                    'created_at'   => optional($evidence->created_at)->toISOString(),
                ],
            ], 201);
        } catch (ValidationException $e) {
            Log::error('Spectro upload failed (validation)', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Spectro upload failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al subir evidencia Spectro.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function listSpectroEvidences($id)
    {
        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('viewAny', Investor::class);
            $items = InvestorSpectroEvidence::where('investor_id', $investor->id)
                ->orderByDesc('id')
                ->get()
                ->map(function ($a) {
                    $url = url('/s3/' . ltrim($a->file_path, '/')); // 👈 usa file_path
                    return [
                        'id'           => $a->id,
                        'name'         => $a->original_name,
                        'mime'         => $a->mime,
                        'size'         => $a->size,
                        'url'          => $url,
                        'download_url' => $url,
                        'created_at'   => optional($a->created_at)->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar evidencias Spectro.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSpectroEvidence($id, $evidenceId)
    {
        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('deleteSpectro', $investor);

            $evidence = InvestorSpectroEvidence::where('investor_id', $investor->id)
                ->where('id', $evidenceId)
                ->firstOrFail();

            if ($evidence->s3_path) {
                Storage::disk('s3')->delete($evidence->s3_path);
            }

            $evidence->delete();

            return response()->json([
                'success' => true,
                'message' => 'Evidencia Spectro eliminada correctamente.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Evidencia no encontrada.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar evidencia Spectro.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // --- PEP ---
    public function uploadPepEvidence(Request $request, $id)
    {
        try {
            $request->validate([
                'file'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'notes' => 'nullable|string|max:500', // 👈 usa "notes" (igual que la migración)
            ]);

            $investor = Investor::findOrFail($id);
            Gate::authorize('uploadPep', $investor);

            $file = $request->file('file');
            $disk = 's3';

            // Guarda en S3 (o MinIO)
            $path = $file->store("investors/{$investor->id}/pep", $disk);

            // Persiste usando los nombres de columnas reales de la tabla
            $evidence = InvestorPepEvidence::create([
                'investor_id'   => $investor->id,
                'file_path'     => $path,                                 // 👈 columna real
                'storage_disk'  => $disk,                                 // 👈 columna real
                'original_name' => $file->getClientOriginalName(),
                'extension'     => $file->getClientOriginalExtension(),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'notes'         => $request->input('notes'),              // 👈 columna real
                'uploaded_by'   => Auth::id(),
            ]);

            // URL estable vía proxy /s3/<key> (si usas ese proxy)
            $publicUrl = url('/s3/' . ltrim($evidence->file_path, '/'));

            return response()->json([
                'success' => true,
                'message' => 'Evidencia PEP subida correctamente.',
                'data'    => [
                    'id'           => $evidence->id,
                    'name'         => $evidence->original_name,
                    'mime'         => $evidence->mime,
                    'size'         => $evidence->size,
                    'url'          => $publicUrl,
                    'download_url' => $publicUrl,
                    'created_at'   => optional($evidence->created_at)->toISOString(),
                ],
            ], 201);
        } catch (ValidationException $e) {
            Log::error('PEP upload failed (validation)', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('PEP upload failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al subir evidencia PEP.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function listPepEvidences($id)
    {
        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('viewAny', Investor::class);

            $items = InvestorPepEvidence::where('investor_id', $investor->id)
                ->orderByDesc('id')
                ->get()
                ->map(function ($a) {
                    $url = url('/s3/' . ltrim($a->file_path, '/')); // 👈 usa file_path
                    return [
                        'id'           => $a->id,
                        'name'         => $a->original_name,
                        'mime'         => $a->mime,
                        'size'         => $a->size,
                        'url'          => $url,
                        'download_url' => $url,
                        'created_at'   => optional($a->created_at)->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar evidencias PEP.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deletePepEvidence($id, $evidenceId)
    {
        try {
            $investor = Investor::findOrFail($id);
            Gate::authorize('deletePep', $investor);

            $evidence = InvestorPepEvidence::where('investor_id', $investor->id)
                ->where('id', $evidenceId)
                ->firstOrFail();

            if ($evidence->file_path) { // 👈 usa file_path y su disk
                Storage::disk($evidence->storage_disk ?? 's3')->delete($evidence->file_path);
            }

            $evidence->delete();

            return response()->json([
                'success' => true,
                'message' => 'Evidencia PEP eliminada correctamente.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Evidencia no encontrada.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar evidencia PEP.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
