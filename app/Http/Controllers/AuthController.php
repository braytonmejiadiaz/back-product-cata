<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use App\Models\Plan;
use App\Mail\VerifiedMail;
use Illuminate\Http\Request;
use App\Mail\ForgotPasswordMail;
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

     public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'surname' => 'required|string|max:255',
        'phone' => 'required|string',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:8',
        'store_name' => 'required|string|max:255',
        'plan_id' => 'required|exists:plans,id',
        'country_code' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    $country = collect($this->countries)->firstWhere('dial_code', $request->country_code);
    if (!$country) {
        return response()->json(['error' => 'Código de país no válido'], 400);
    }

    $plan = Plan::findOrFail($request->plan_id);

    MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));

    try {
        $preapprovalClient = new PreApprovalClient();

        // Generar un ID único para esta transacción
        $transactionId = 'treggio_' . uniqid();

        $preapprovalData = [
            "back_url" => env('APP_URL') . "/ingresar",
            "payer_email" => $request->email,
            "external_reference" => $transactionId,
            "reason" => "Suscripción al plan " . $plan->name,
            "auto_recurring" => [
                "frequency" => 1,
                "frequency_type" => "months",
                "transaction_amount" => (float) $plan->price,
                "currency_id" => "COP",
                "start_date" => now()->addDay(10)->toISOString(),
                "end_date" => now()->addYears(1)->toISOString(),
            ],
            "payment_methods_allowed" => [
                "payment_types" => [
                    ["id" => "credit_card"],
                    ["id" => "debit_card"],
                ],
                "payment_methods" => [
                    ["id" => "visa"],
                    ["id" => "master"],
                    ["id" => "amex"],
                ]
            ],
            "notification_url" => env('APP_URL') . "/api/webhook",
            "status" => "authorized"
        ];

        $subscription = $preapprovalClient->create($preapprovalData);

        if (!isset($subscription->id)) {
            return response()->json(['error' => 'No se pudo generar la suscripción'], 500);
        }

        // Guardar datos del usuario en caché por 24 horas (1440 minutos)
        \Cache::put('pending_user_' . $subscription->id, [
            'transaction_id' => $transactionId,
            'name' => $request->name,
            'surname' => $request->surname,
            'phone' => $request->country_code . $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'store_name' => $request->store_name,
            'plan_id' => $plan->id,
            'country_code' => $request->country_code,
        ], now()->addMinutes(1440));

        return response()->json([
            'success' => true,
            'message' => 'Redirigiendo a Mercado Pago...',
            'payment_url' => $subscription->init_point,
            'subscription_id' => $subscription->id,
            'transaction_id' => $transactionId
        ]);

    } catch (\MPApiException $e) {
        \Log::error('Error de Mercado Pago', [
            'status' => $e->getApiResponse()->getStatusCode(),
            'response' => $e->getApiResponse()->getContent()
        ]);
        return response()->json([
            'error' => 'Error en Mercado Pago: ' . $e->getMessage()
        ], 500);
    } catch (\Exception $e) {
        \Log::error('Error en registro', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Error en el registro: ' . $e->getMessage()
        ], 500);
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


    public function webhook(Request $request)
    {
        \Log::info('Webhook recibido:', $request->all());

        MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));

        try {
            $data = $request->all();
            $eventType = $data['type'] ?? ($data['topic'] ?? null);
            $subscriptionId = $data['data']['id'] ?? $data['id'] ?? $data['data_id'] ?? null;

            if (!$subscriptionId) {
                \Log::error('ID de suscripción no encontrado en webhook');
                return response()->json(['error' => 'ID de suscripción no encontrado'], 400);
            }

            // Obtener datos del usuario pendiente desde caché
            $userData = \Cache::get('pending_user_' . $subscriptionId);

            if (!$userData) {
                \Log::error('Datos de usuario pendiente no encontrados para suscripción: ' . $subscriptionId);
                return response()->json(['error' => 'Datos de usuario no encontrados'], 404);
            }

            // Manejar diferentes tipos de eventos
            switch ($eventType) {
                case 'subscription_preapproval':
                case 'preapproval':
                    return $this->processSubscription($subscriptionId, $data, $userData);

                case 'payment':
                    return $this->processPayment($data, $userData);

                default:
                    \Log::info('Evento no manejado:', ['type' => $eventType]);
                    return response()->json(['message' => 'Evento no manejado'], 200);
            }

        } catch (\MPApiException $e) {
            \Log::error('Error de API Mercado Pago en webhook', [
                'status' => $e->getApiResponse()->getStatusCode(),
                'response' => $e->getApiResponse()->getContent()
            ]);
            return response()->json(['error' => 'Error en Mercado Pago: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            \Log::error('Error en webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al procesar webhook'], 500);
        }
    }

    protected function processSubscription($subscriptionId, $data, $userData)
    {
        $preapprovalClient = new PreApprovalClient();
        $subscription = $preapprovalClient->get($subscriptionId);

        \Log::info('Estado de suscripción:', [
            'status' => $subscription->status,
            'id' => $subscription->id,
            'external_reference' => $subscription->external_reference ?? null
        ]);

        // Verificar que la referencia externa coincida
        if (($subscription->external_reference ?? null) !== ($userData['transaction_id'] ?? null)) {
            \Log::error('La referencia externa no coincide', [
                'expected' => $userData['transaction_id'],
                'received' => $subscription->external_reference
            ]);
            return response()->json(['error' => 'Referencia externa no válida'], 400);
        }

        // Si la suscripción está autorizada, crear el usuario
        if ($subscription->status === 'authorized') {
            // Verificar si el usuario ya existe
            if (User::where('email', $userData['email'])->exists()) {
                \Log::info('Usuario ya existe: ' . $userData['email']);
                \Cache::forget('pending_user_' . $subscriptionId);
                return response()->json(['message' => 'Usuario ya registrado'], 200);
            }

            // Crear slug único
            $slug = $this->generateUniqueSlug($userData['store_name']);

            // Crear el usuario
            $user = User::create([
                'name' => $userData['name'],
                'surname' => $userData['surname'],
                'phone' => $userData['phone'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'store_name' => $userData['store_name'],
                'slug' => $slug,
                'type_user' => 1,
                'plan_id' => $userData['plan_id'],
                'mercadopago_subscription_id' => $subscriptionId,
                'email_verified_at' => now(),
            ]);

            // Enviar email de confirmación
            try {
                Mail::to($user->email)->send(new VerifiedMail($user));
                \Log::info('Correo de verificación enviado a: ' . $user->email);
            } catch (\Exception $e) {
                \Log::error('Error al enviar correo', [
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }

            // Eliminar datos de caché
            \Cache::forget('pending_user_' . $subscriptionId);

            \Log::info('Usuario creado exitosamente', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json(['message' => 'Usuario registrado exitosamente'], 200);
        }

        // Si la suscripción fue cancelada, limpiar caché
        if ($subscription->status === 'cancelled') {
            \Cache::forget('pending_user_' . $subscriptionId);
            \Log::info('Suscripción cancelada, datos pendientes eliminados');
            return response()->json(['message' => 'Suscripción cancelada'], 200);
        }

        // Para otros estados (pending, paused, etc.)
        return response()->json(['message' => 'Suscripción en estado: ' . $subscription->status], 200);
    }

    protected function processPayment($data, $userData)
    {
        $paymentId = $data['data']['id'] ?? null;
        if (!$paymentId) {
            \Log::error('ID de pago no encontrado en webhook');
            return response()->json(['error' => 'ID de pago no encontrado'], 400);
        }

        $paymentClient = new PaymentClient();
        $payment = $paymentClient->get($paymentId);

        \Log::info('Estado de pago:', [
            'status' => $payment->status,
            'id' => $payment->id,
            'external_reference' => $payment->external_reference ?? null
        ]);

        // Si el pago está aprobado, verificar si el usuario existe y notificar
        if ($payment->status === 'approved') {
            $user = User::where('email', $userData['email'])->first();

            if ($user) {
                try {
                    Mail::to($user->email)->send(new VerifiedMail($user));
                    \Log::info('Correo de confirmación enviado por pago aprobado');
                } catch (\Exception $e) {
                    \Log::error('Error al enviar correo', [
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Evento de pago procesado'], 200);
    }

}
