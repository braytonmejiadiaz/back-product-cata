<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use App\Models\Plan;
use App\Mail\VerifiedMail;
use App\Mail\ForgotPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;
use MercadoPago\Preference;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Resources\Payment;
use MercadoPago\Resources\Preference\Item;
use MercadoPago\Client\Preference\PreferenceClient;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register','login_ecommerce','verified_auth',
        'verified_email','verified_code','new_password', 'webhook', 'getCountries'
        ]]);
    }


    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

     public function register()
     {
         $validator = Validator::make(request()->all(), [
             'name' => 'required',
             'surname' => 'required',
             'phone' => 'required',
             'email' => 'required|email|unique:users',
             'password' => 'required',
             'store_name' => 'required',
             'plan_id' => 'required|exists:plans,id',
             'country_code' => 'required|string',
         ]);

         if ($validator->fails()) {
             return response()->json($validator->errors()->toJson(), 400);
         }

         $country = collect($this->countries)->firstWhere('dial_code', request()->country_code);
         if (!$country) {
             return response()->json(['error' => 'Código de país no válido'], 400);
         }

         $plan = Plan::find(request()->plan_id);

         // Si es plan gratis, crear usuario directamente
         if ($plan->is_free) {
             $user = User::create([
                 'name' => request()->name,
                 'surname' => request()->surname,
                 'phone' => request()->country_code . request()->phone,
                 'type_user' => 1,
                 'email' => request()->email,
                 'uniqd' => uniqid(),
                 'store_name' => request()->store_name,
                 'slug' => $this->generateUniqueSlug(request()->store_name),
                 'password' => bcrypt(request()->password),
                 'plan_id' => $plan->id,
                 'email_verified_at' => now()
             ]);

             $token = auth('api')->login($user);

             return response()->json([
                 'message' => 'Registro exitoso',
                 'access_token' => $token,
                 'token_type' => 'bearer',
                 'expires_in' => auth('api')->factory()->getTTL() * 60,
                 'user' => $user
             ], 200);
         }

         MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));

         try {
             $preapprovalClient = new PreApprovalClient();
             $preapprovalData = [
                 "back_url" => env('FRONTEND_URL') . "/ingresar",
                 "payer_email" => request()->email,
                 "external_reference" => json_encode([
                     'email' => request()->email,
                     'name' => request()->name,
                     'surname' => request()->surname,
                     'phone' => request()->country_code . request()->phone,
                     'store_name' => request()->store_name,
                     'password' => bcrypt(request()->password),
                     'plan_id' => $plan->id,
                     'slug' => $this->generateUniqueSlug(request()->store_name),
                     'action' => 'register'
                 ]),
                 "reason" => "Suscripción al plan " . $plan->name,
                 "auto_recurring" => [
                     "frequency" => 1,
                     "frequency_type" => "months",
                     "transaction_amount" => (float) $plan->price,
                     "currency_id" => "COP",
                     "start_date" => now()->addDay(1)->toISOString(),
                     "end_date" => now()->addYears(3)->toISOString(),
                 ]
             ];

             $subscription = $preapprovalClient->create($preapprovalData);

             return response()->json([
                 'message' => 'Por favor completa el pago para activar tu cuenta',
                 'payment_url' => $subscription->init_point,
                 'subscription_id' => $subscription->id
             ], 200);

         } catch (MPApiException $e) {
             Log::error('Error en MercadoPago', [
                 'message' => $e->getMessage(),
                 'status' => $e->getHttpStatusCode()
             ]);
             return response()->json(['error' => 'Error en el procesamiento de pago: ' . $e->getMessage()], 500);
         }
}


/**
 * Generate a unique slug for store
 */
private function generateUniqueSlug($storeName)
{
    $slug = Str::slug($storeName);
    $originalSlug = $slug;
    $counter = 1;

    while (User::where('slug', $slug)->exists()) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}


    public function update(Request $request) {
        $user = User::find(auth("api")->user()->id);

        // Verificar si el nombre de la tienda ha cambiado
        if ($request->has('store_name') && $request->store_name !== $user->store_name) {
            $slug = Str::slug($request->store_name);
            $originalSlug = $slug;
            $counter = 1;

            // Verificar si el slug ya existe
            while (User::where('slug', $slug)->where('id', '<>', $user->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $request->merge(['slug' => $slug]);
        }

            // Formatear el número de teléfono si se actualiza
            if ($request->has('phone')) {
                // Extraer el código de país actual del teléfono (los primeros dígitos antes del número)
                $currentPhone = $user->phone;
                $countryCode = substr($currentPhone, 0, strlen($currentPhone) - strlen($request->phone));

                // Si no se puede extraer el código de país, usar el del request si existe, o mantener el actual
                if (empty($countryCode) && $request->has('country_code')) {
                    $countryCode = $request->country_code;
                }

                $phone = $countryCode . $request->phone;
                $request->merge(['phone' => $phone]);
            }

        if ($request->has('password')) {
            $user->update([
                "password" => bcrypt($request->password)
            ]);
            return response()->json([
                "message" => 200,
            ]);
        }

        // Verificar si el email ya existe
        $is_exists_email = User::where("id", "<>", auth("api")->user()->id)
                                ->where("email", $request->email)->first();
        if ($is_exists_email) {
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe"
            ]);
        }

        // Manejar la imagen de avatar si se actualiza
        if ($request->hasFile("file_imagen")) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("users", $request->file("file_imagen"));
            $request->merge(["avatar" => $path]);
        }

        $user->update($request->all());

        return response()->json([
            "message" => 200,
            "url_tienda" => "https://app.treggio.co/{$user->slug}" // Devuelve la nueva URL de la tienda
        ]);
    }

    public function verified_email(Request $request){
        $user = User::where("email",$request->email)->first();
        if($user){
            $user->update(["code_verified" => uniqid()]);
            Mail::to($request->email)->send(new ForgotPasswordMail($user));
            return response()->json(["message" => 200]);
        }else{
            return response()->json(["message" => 403]);
        }
    }
    public function verified_code(Request $request){
        $user = User::where("code_verified",$request->code)->first();
        if($user){
            return response()->json(["message" => 200]);
        }else{
            return response()->json(["message" => 403]);
        }
    }
    public function new_password(Request $request){
        $user = User::where("code_verified",$request->code)->first();
        $user->update(["password" => bcrypt($request->new_password),"code_verified" => null]);
        return response()->json(["message" => 200]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt([
            "email" => request()->email,
            "password" => request()->password,
            "type_user" => 1])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function login_ecommerce()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt([
            "email" => request()->email,
            "password" => request()->password,
            "type_user" => 2])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if(!auth('api')->user()->email_verified_at){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function verified_auth(Request $request){
        $user = User::where("uniqd", $request->code_user)->first();

        if($user){
            $user->update(["email_verified_at" => now()]);
            return response()->json(["message" => 200]);
        }

        return response()->json(["message" => 403]);
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = User::find(auth("api")->user()->id);
        return response()->json([
            'name' => $user->name,
            'surname' => $user->surname,
            'phone' => $user->phone,
            'email' => $user->email,
            'description' => $user->description,
            'fb' => $user->fb,
            'ins' => $user->ins,
            'tikTok' => $user->tikTok,
            'youtube' => $user->youtube,
            'sexo' => $user->sexo,
            'address' => $user->address,
            'store_name' => $user->store_name,
            'slug' => $user->slug,
            'avatar' => $user->avatar,
            'popup' => $user->popup,
            'mision' => $user->mision,
            'vision' => $user->vision,
            'menu_color' => $user->menu_color,
            'button_color' => $user->button_color,
            'button_radio' => $user->button_radio,
            'plan_id' => $user->plan_id,
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            "user" => [
                "full_name" => auth('api')->user()->name . ' ' . auth('api')->user()->surname,
                "email" => auth('api')->user()->email,
            ],
        ]);
    }

    public function deletePopupImage()
{
    $user = User::find(auth("api")->user()->id);

    if ($user->popup) {
        Storage::delete($user->popup); // Elimina la imagen del almacenamiento
        $user->update(["popup" => null]); // Elimina la referencia en la base de datos

        return response()->json(["message" => 200]);
    }

    return response()->json([
        "message" => 404,
        "message_text" => "No se encontró una imagen de popup"
    ]);
}


public function webhook(Request $request)
    {
        Log::info('Webhook recibido', $request->all());

        $type = $request->get('type');
        if (!$type && $request->has('topic')) {
            $type = $request->get('topic');
        }

        if (in_array($type, ['preapproval', 'subscription_preapproval'])) {
            MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));
            $client = new PreApprovalClient();

            try {
                $preapprovalId = $request->get('data')['id'] ?? $request->get('data_id');
                Log::info('ID de preapproval recibido', ['preapproval_id' => $preapprovalId]);

                $subscription = $client->get($preapprovalId);
                Log::info('Datos de suscripción obtenidos desde MercadoPago', ['status' => $subscription->status]);

                $external = json_decode($subscription->external_reference, true);
                if (!is_array($external)) {
                    Log::error('external_reference no es un array válido', ['external_reference' => $subscription->external_reference]);
                    return response()->json(['error' => 'external_reference inválido'], 400);
                }

                // Manejar ambos casos: registro nuevo y actualización de plan
                if (isset($external['action'])) {
                    switch ($external['action']) {
                        case 'update':
                            // ACTUALIZACIÓN DE PLAN EXISTENTE
                            $user = User::find($external['user_id']);
                            if ($user && $subscription->status === 'authorized') {
                                // Cancelar suscripción anterior si existe
                                if ($user->mercadopago_subscription_id) {
                                    try {
                                        $client->cancel($user->mercadopago_subscription_id);
                                    } catch (\Exception $e) {
                                        Log::error('Error cancelando suscripción anterior', ['error' => $e->getMessage()]);
                                    }
                                }

                                $user->update([
                                    'plan_id' => $external['plan_id'],
                                    'mercadopago_subscription_id' => $subscription->id
                                ]);
                                Log::info('Plan actualizado exitosamente', ['user_id' => $user->id]);
                            }
                            break;

                        case 'register':
                            // REGISTRO DE NUEVO USUARIO
                            if ($subscription->status === 'authorized' && !User::where('email', $external['email'])->exists()) {
                                $user = User::create([
                                    'name' => $external['name'],
                                    'surname' => $external['surname'],
                                    'phone' => $external['phone'],
                                    'type_user' => 1,
                                    'email' => $external['email'],
                                    'uniqd' => uniqid(),
                                    'store_name' => $external['store_name'],
                                    'slug' => $external['slug'],
                                    'password' => $external['password'],
                                    'plan_id' => $external['plan_id'],
                                    'mercadopago_subscription_id' => $subscription->id,
                                    'email_verified_at' => now()
                                ]);
                                Log::info('✅ Usuario creado exitosamente después de suscripción', ['user_id' => $user->id]);
                            }
                            break;

                        default:
                            Log::warning('Acción desconocida en external_reference', ['action' => $external['action']]);
                            break;
                    }
                } else {
                    // Compatibilidad con versiones anteriores (solo registro)
                    if ($subscription->status === 'authorized' && !User::where('email', $external['email'])->exists()) {
                        $user = User::create([
                            'name' => $external['name'],
                            'surname' => $external['surname'],
                            'phone' => $external['phone'],
                            'type_user' => 1,
                            'email' => $external['email'],
                            'uniqd' => uniqid(),
                            'store_name' => $external['store_name'],
                            'slug' => $external['slug'],
                            'password' => $external['password'],
                            'plan_id' => $external['plan_id'],
                            'mercadopago_subscription_id' => $subscription->id,
                            'email_verified_at' => now()
                        ]);
                        Log::info('✅ Usuario creado exitosamente (compatibilidad)', ['user_id' => $user->id]);
                    }
                }

                return response()->json(['status' => 'ok'], 200);

            } catch (\Exception $e) {
                Log::error('Error procesando webhook de MercadoPago', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Error procesando el webhook'], 500);
            }
        }

        Log::info('Evento ignorado por tipo no manejado', ['type' => $type]);
        return response()->json(['message' => 'Tipo de evento no manejado'], 200);
    }



    public $countries = [
        ['name' => 'CO', 'dial_code' => '57'],
        ['name' => 'AR', 'dial_code' => '54'],
        ['name' => 'BO', 'dial_code' => '591'],
        ['name' => 'CL', 'dial_code' => '56'],
        ['name' => 'CR', 'dial_code' => '506'],
        ['name' => 'CU', 'dial_code' => '53'],
        ['name' => 'EC', 'dial_code' => '593'],
        ['name' => 'SV', 'dial_code' => '503'],
        ['name' => 'ES', 'dial_code' => '34'],
        ['name' => 'GT', 'dial_code' => '502'],
        ['name' => 'HN', 'dial_code' => '504'],
        ['name' => 'MX', 'dial_code' => '52'],
        ['name' => 'NI', 'dial_code' => '505'],
        ['name' => 'PA', 'dial_code' => '507'],
        ['name' => 'PY', 'dial_code' => '595'],
        ['name' => 'PE', 'dial_code' => '51'],
        ['name' => 'DO', 'dial_code' => '1'],
        ['name' => 'UY', 'dial_code' => '598'],
        ['name' => 'VE', 'dial_code' => '58'],
    ];

    public function getCountries()
    {
    return response()->json($this->countries);
    }


    public function updatePlanPayment(Request $request)
    {
        // Obtiene el usuario autenticado desde el token JWT
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:plans,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $plan = Plan::findOrFail($request->plan_id);

            // Plan gratuito - actualización directa
            if ($plan->is_free) {
                $user->update(['plan_id' => $plan->id]);
                return response()->json([
                    'success' => true,
                    'message' => 'Plan actualizado exitosamente',
                    'plan' => $plan->name
                ]);
            }

            // Configuración de MercadoPago
            $mpToken = env('MERCADO_PAGO_ACCESS_TOKEN');
            if (empty($mpToken)) {
                throw new \Exception("Configuración de MercadoPago incompleta");
            }
            MercadoPagoConfig::setAccessToken($mpToken);

            // Preparar datos para la suscripción
            $preapprovalData = [
                "back_url" => rtrim(env('FRONTEND_URL'), '/') . "/payment-result",
                "payer_email" => $user->email,
                "external_reference" => json_encode([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'action' => 'update',
                    'source' => 'plan_update'
                ]),
                "reason" => "Actualización a plan {$plan->name}",
                "auto_recurring" => [ // ¡ATENCIÓN A LA ORTOGRAFÍA! Debe ser "auto_recurring"
                    "frequency" => 1,
                    "frequency_type" => "months",
                    "transaction_amount" => (float) number_format($plan->price, 2, '.', ''),
                    "currency_id" => "COP",
                    "start_date" => now()->addDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s.000\Z'),
                    "end_date" => now()->addYears(3)->setTimezone('UTC')->format('Y-m-d\TH:i:s.000\Z')
                ]
            ];

            // Validaciones adicionales
            if (!filter_var($preapprovalData['payer_email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Email del pagador no válido");
            }

            if ($preapprovalData['auto_recurring']['transaction_amount'] <= 0) {
                throw new \Exception("El monto debe ser mayor a cero");
            }

            // Debug: Ver datos que se enviarán
            Log::debug('Datos para suscripción MercadoPago', $preapprovalData);

            // Crear la suscripción
            $subscription = (new PreApprovalClient())->create($preapprovalData);

            // Verificar respuesta
            if (empty($subscription->id)) {
                throw new \Exception("No se recibió ID de suscripción");
            }

            return response()->json([
                'success' => true,
                'payment_url' => $subscription->init_point ?? $subscription->sandbox_init_point,
                'subscription_id' => $subscription->id,
                'plan_name' => $plan->name,
                'amount' => $plan->price
            ]);

        } catch (MPApiException $e) {
            // Manejo específico de errores de MercadoPago
            $errorDetails = [
                'status' => $e->getStatusCode(),
                'response' => $e->getApiResponse(),
                'message' => $e->getMessage()
            ];
            Log::error('Error MercadoPago API', $errorDetails);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error' => $errorDetails['response']['message'] ?? $e->getMessage(),
                'details' => $errorDetails['response']['error'] ?? null
            ], 500);

        } catch (\Throwable $e) {
            Log::error('Error en updatePlanPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}
