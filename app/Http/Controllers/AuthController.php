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
        'verified_email','verified_code','new_password', 'webhook'
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
        'password' => 'required|min:8',
        'store_name' => 'required',
        'plan_id' => 'required|exists:plans,id',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors()->toJson(), 400);
    }

    // Obtener el plan seleccionado
    $plan = Plan::find(request()->plan_id);
    if (!$plan) {
        Log::error('El plan no existe.', ['plan_id' => request()->plan_id]);
        return response()->json(['error' => 'Plan no encontrado'], 400);
    }

    // Configurar MercadoPago
    MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));

    try {
        // 1. PRIMERO crear la suscripción en MercadoPago
        $preapprovalClient = new PreApprovalClient();
        $preapprovalData = [
            "back_url" => "https://app.treggio.co/ingresar",
            "payer_email" => request()->email,
            "external_reference" => "temp_" . uniqid(), // Referencia temporal
            "reason" => "Suscripción al plan " . $plan->name,
            "auto_recurring" => [
                "frequency" => 1,
                "frequency_type" => "months",
                "transaction_amount" => (float) $plan->price,
                "currency_id" => "COP",
                "start_date" => now()->addDay()->toISOString(), // Comienza mañana
                "end_date" => now()->addYears(2)->toISOString(),
            ]
        ];

        Log::info('Creando suscripción en MercadoPago...');
        $subscription = $preapprovalClient->create($preapprovalData);

        if (!isset($subscription->id)) {
            Log::error('Error en MercadoPago: No se generó ID de suscripción.');
            return response()->json(['error' => 'No se pudo generar la suscripción'], 500);
        }

        Log::info('Suscripción creada con éxito', ['subscription_id' => $subscription->id]);

        // 2. SOLO SI MercadoPago responde con éxito, crear el usuario
        $phone = request()->phone;
        if (!str_starts_with($phone, '57')) {
            $phone = '57' . $phone;
        }

        $slug = $this->generateUniqueSlug(request()->store_name);

        $user = User::create([
            'name' => request()->name,
            'surname' => request()->surname,
            'phone' => $phone,
            'type_user' => 1,
            'email' => request()->email,
            'uniqd' => uniqid(),
            'store_name' => request()->store_name,
            'slug' => $slug,
            'password' => bcrypt(request()->password),
            'plan_id' => $plan->id,
            'mercadopago_subscription_id' => $subscription->id // Guardar ID de suscripción
        ]);

        // 3. Devolver URL de pago al frontend
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
    } catch (\Exception $e) {
        Log::error('Error general en registro', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return response()->json(['error' => 'Error en el registro: ' . $e->getMessage()], 500);
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
            $phone = $request->phone;
            if (!str_starts_with($phone, '57')) {
                $phone = '57' . $phone;
            }
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
    Log::info('Webhook recibido', ['data' => $request->all()]);

    // Verificar si es una notificación de pago
    if ($request->has('type') && $request->type === 'payment' && isset($request->data['id'])) {
        $paymentId = $request->data['id'];

        // Configurar Mercado Pago
        MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));

        // Obtener detalles del pago
        try {
            $client = new PaymentClient();  // Usamos PaymentClient en vez de Payment::get()
            $payment = $client->get($paymentId);

            if ($payment && $payment->status === 'approved') {
                // Buscar el usuario por su email
                $payerEmail = $payment->payer->email ?? null;

                if ($payerEmail) {
                    $user = User::where('email', $payerEmail)->first();

                    if ($user) {
                        // Enviar email de confirmación
                        Mail::to($user->email)->send(new VerifiedMail($user));
                        Log::info('Correo enviado a usuario', ['email' => $user->email]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener el pago de Mercado Pago', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Error al procesar el pago'], 500);
        }
    }

      return response()->json(['status' => 'success'], 200);
    }
}
