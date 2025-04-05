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
use MercadoPago\Http\HttpClient;

class AuthController extends Controller
{
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

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'login', 'register', 'login_ecommerce', 'verified_auth',
            'verified_email', 'verified_code', 'new_password', 'webhook', 'getCountries'
        ]]);

        MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));
        MercadoPagoConfig::setRuntimeEnvironment(new HttpClient([
            'max_retries' => env('MERCADO_PAGO_MAX_RETRIES', 3),
            'timeout' => env('MERCADO_PAGO_TIMEOUT', 15),
        ]));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        $country = collect($this->countries)->firstWhere('dial_code', $request->country_code);
        if (!$country) {
            return response()->json(['error' => 'Código de país no válido'], 400);
        }

        $plan = Plan::find($request->plan_id);

        try {
            $preapprovalClient = new PreApprovalClient();
            $preapprovalData = [
                "back_url" => "https://app.treggio.co/ingresar",
                "payer_email" => $request->email,
                "external_reference" => uniqid('ref_'), // para poder usarlo en el webhook
                "reason" => "Suscripción al plan " . $plan->name,
                "auto_recurring" => [
                    "frequency" => 1,
                    "frequency_type" => "months",
                    "transaction_amount" => (float) $plan->price,
                    "currency_id" => "COP",
                    "start_date" => now()->addDay(10)->toISOString(),
                    "end_date" => now()->addYears(2)->toISOString(),
                    "retry_attempts" => 3,
                ],
                "notification_url" => route('webhook'),
            ];

            $subscription = $preapprovalClient->create($preapprovalData);

            if (!isset($subscription->id)) {
                return response()->json(['error' => 'No se pudo generar la suscripción'], 500);
            }

            Storage::put("pending_users/{$subscription->id}.json", json_encode([
                'name' => $request->name,
                'surname' => $request->surname,
                'phone' => $request->country_code . $request->phone,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'store_name' => $request->store_name,
                'plan_id' => $plan->id,
                'country_code' => $request->country_code,
            ]));

            return response()->json([
                'message' => 'Redirigiendo a la pasarela de pago...',
                'payment_url' => $subscription->init_point,
                'subscription_id' => $subscription->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error en el registro: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error en el registro: ' . $e->getMessage()], 500);
        }
    }

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

    public function update(Request $request)
    {
        $user = User::find(auth("api")->user()->id);

        if ($request->has('store_name') && $request->store_name !== $user->store_name) {
            $slug = Str::slug($request->store_name);
            $originalSlug = $slug;
            $counter = 1;

            while (User::where('slug', $slug)->where('id', '<>', $user->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $request->merge(['slug' => $slug]);
        }

        if ($request->has('phone')) {
            $currentPhone = $user->phone;
            $countryCode = substr($currentPhone, 0, strlen($currentPhone) - strlen($request->phone));

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

        $is_exists_email = User::where("id", "<>", auth("api")->user()->id)
            ->where("email", $request->email)->first();
        if ($is_exists_email) {
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe"
            ]);
        }

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
            "url_tienda" => "https://app.treggio.co/{$user->slug}"
        ]);
    }

    public function verified_email(Request $request)
    {
        $user = User::where("email", $request->email)->first();
        if ($user) {
            $user->update(["code_verified" => uniqid()]);
            Mail::to($request->email)->send(new ForgotPasswordMail($user));
            return response()->json(["message" => 200]);
        } else {
            return response()->json(["message" => 403]);
        }
    }

    public function verified_code(Request $request)
    {
        $user = User::where("code_verified", $request->code)->first();
        if ($user) {
            return response()->json(["message" => 200]);
        } else {
            return response()->json(["message" => 403]);
        }
    }

    public function new_password(Request $request)
    {
        $user = User::where("code_verified", $request->code)->first();
        $user->update(["password" => bcrypt($request->new_password), "code_verified" => null]);
        return response()->json(["message" => 200]);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt([
            "email" => request()->email,
            "password" => request()->password,
            "type_user" => 1
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function login_ecommerce()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt([
            "email" => request()->email,
            "password" => request()->password,
            "type_user" => 2
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!auth('api')->user()->email_verified_at) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function verified_auth(Request $request)
    {
        $user = User::where("uniqd", $request->code_user)->first();

        if ($user) {
            $user->update(["email_verified_at" => now()]);
            return response()->json(["message" => 200]);
        }

        return response()->json(["message" => 403]);
    }

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

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

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
            Storage::delete($user->popup);
            $user->update(["popup" => null]);

            return response()->json(["message" => 200]);
        }

        return response()->json([
            "message" => 404,
            "message_text" => "No se encontró una imagen de popup"
        ]);
    }

    public function getCountries()
    {
        return response()->json($this->countries);
    }

    public function webhook(Request $request)
    {
        $data = $request->all();
        Log::info('Webhook recibido:', $data);

        $eventType = $data['type'] ?? $data['topic'] ?? null;

        if (!in_array($eventType, ['subscription_preapproval', 'payment', 'preapproval'])) {
            Log::info('Evento no manejado:', ['type' => $eventType]);
            return response()->json(['message' => 'Evento no manejado'], 200);
        }

        try {
            $resourceId = $data['data']['id'] ?? $data['data_id'] ?? $data['id'] ?? null;

            if ($eventType === 'payment') {
                return $this->handlePaymentEvent($resourceId);
            }

            return $this->handleSubscriptionEvent($resourceId, $data['action'] ?? null);
        } catch (\Exception $e) {
            Log::error('Error en webhook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return response()->json(['error' => 'Error interno'], 500);
        }
    }

    private function handlePaymentEvent($paymentId)
    {
        $paymentClient = new PaymentClient();
        $payment = $paymentClient->get($paymentId);

        Log::info('Estado de pago recibido:', ['payment_id' => $paymentId, 'status' => $payment->status]);

        if ($payment->status === 'approved') {
            $payerEmail = $payment->payer->email ?? null;

            if ($payerEmail) {
                $user = User::where('email', $payerEmail)->first();

                if ($user) {
                    Mail::to($user->email)->send(new VerifiedMail($user));
                    Log::info('Correo enviado por pago aprobado', ['email' => $user->email]);
                    return response()->json(['message' => 'Correo de verificación reenviado'], 200);
                } else {
                    Log::warning('Usuario no encontrado para el correo', ['email' => $payerEmail]);
                    return response()->json(['message' => 'Usuario no encontrado'], 404);
                }
            } else {
                Log::warning('Correo del pagador no encontrado en el pago', ['payment_id' => $paymentId]);
                return response()->json(['message' => 'Correo del pagador no encontrado'], 400);
            }
        }

        return response()->json(['message' => 'Evento de pago procesado'], 200);
    }

    private function handleSubscriptionEvent($subscriptionId, $action)
    {
        $preapprovalClient = new PreApprovalClient();
        $subscription = $preapprovalClient->get($subscriptionId);

        Log::info("Estado de suscripción recibido:", [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'action' => $action
        ]);

        switch ($subscription->status) {
            case 'authorized':
                return $this->activateSubscription($subscription);

            case 'pending':
                Log::info("Suscripción pendiente: {$subscription->id}");
                return response()->json(['message' => 'Pendiente'], 200);

            case 'cancelled':
                $this->cleanupPendingSubscription($subscription->id);
                return response()->json(['message' => 'Cancelada'], 200);

            default:
                Log::warning("Estado no manejado: {$subscription->status}");
                return response()->json(['message' => 'Estado desconocido'], 200);
        }
    }

    private function activateSubscription($subscription)
    {
        if (User::where('mercadopago_subscription_id', $subscription->id)->exists()) {
            Log::info("Suscripción ya activa: {$subscription->id}");
            return response()->json(['message' => 'Usuario existente'], 200);
        }

        $pendingUserPath = "pending_users/{$subscription->id}.json";

        if (!Storage::exists($pendingUserPath)) {
            Log::error("Datos de usuario pendiente no encontrados: {$subscription->id}");
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $userData = json_decode(Storage::get($pendingUserPath), true);

        $user = User::create([
            ...$userData,
            'mercadopago_subscription_id' => $subscription->id,
            'email_verified_at' => now(),
        ]);

        Storage::delete($pendingUserPath);

        Mail::to($user->email)->send(new VerifiedMail($user));

        Log::info("Usuario creado exitosamente: {$user->email}");
        return response()->json(['message' => 'Suscripción activada'], 200);
    }

    private function cleanupPendingSubscription($subscriptionId)
    {
        $pendingUserPath = "pending_users/{$subscriptionId}.json";

        if (Storage::exists($pendingUserPath)) {
            Storage::delete($pendingUserPath);
            Log::info("Suscripción cancelada limpiada: {$subscriptionId}");
        }
    }
}
