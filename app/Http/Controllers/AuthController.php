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
        'verified_email','verified_code','new_password'
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
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        // Formatear el número de teléfono
        $phone = request()->phone;
        if (!str_starts_with($phone, '57')) {
            $phone = '57' . $phone;
        }

        // Generar un slug único
        $slug = Str::slug(request()->store_name);
        $originalSlug = $slug;
        $counter = 1;

        // Verificar si el slug ya existe
        while (User::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Crear el usuario
        $user = new User;
        $user->name = request()->name;
        $user->surname = request()->surname;
        $user->phone = $phone;
        $user->type_user = 1;
        $user->email = request()->email;
        $user->uniqd = uniqid();
        $user->store_name = request()->store_name;
        $user->slug = $slug;
        $user->password = bcrypt(request()->password);
        $user->save();

        // Obtener el plan seleccionado
        $plan = Plan::find(request()->plan_id);
        if (!$plan) {
            return response()->json(['error' => 'Plan no encontrado'], 404);
        }

        // Configurar MercadoPago
        MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));

        try {
            // Crear la suscripción en MercadoPago
            $preapprovalClient = new PreApprovalClient();
            $preapprovalData = [
                "back_url" => "https://app.treggio.co/ingresar", // URL de redirección después del pago
                "payer_email" => $user->email,
                "reason" => "Suscripción al plan " . $plan->name,
                "auto_recurring" => [
                    "frequency" => 1,
                    "frequency_type" => "months",
                    "transaction_amount" => (float) $plan->price,
                    "currency_id" => "COP",
                    "start_date" => now()->toISOString(),
                    "end_date" => now()->addYear()->toISOString(),
                ]
            ];

            $subscription = $preapprovalClient->create($preapprovalData);

            if (!isset($subscription->id)) {
                Log::error('Error en MercadoPago: No se generó ID de suscripción.', ['response' => $subscription]);
                return response()->json(['error' => 'No se pudo generar la suscripción'], 500);
            }

            // Asociar el plan al usuario
            $user->plan_id = $plan->id;
            $user->save();

            // Enviar correo de verificación
            Mail::to(request()->email)->send(new VerifiedMail($user));

            // Devolver la URL de pago al frontend
            return response()->json([
                'user' => $user,
                'url_tienda' => "https://app.treggio.co/{$user->slug}",
                'payment_url' => $subscription->init_point, // URL de pago de MercadoPago
            ], 201);

        } catch (MPApiException $e) {
            Log::error('Error en MercadoPago', [
                'message' => $e->getMessage(),
                'status' => $e->getHttpStatusCode(),
                'response' => $e->getApiResponse()
            ]);
            return response()->json(['error' => 'Error en MercadoPago: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Error interno', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }


    // public function update(Request $request) {
    //     $user = User::find(auth("api")->user()->id);

    //     if ($request->has('store_name') && $request->store_name !== $user->store_name) {
    //         $slug = Str::slug($request->store_name);
    //         $originalSlug = $slug;
    //         $counter = 1;

    //         while (User::where('slug', $slug)->where('id', '<>', $user->id)->exists()) {
    //             $slug = $originalSlug . '-' . $counter;
    //             $counter++;
    //         }

    //         $request->merge(['slug' => $slug]);
    //     }

    //     if ($request->has('phone')) {
    //         $phone = $request->phone;
    //         if (!str_starts_with($phone, '57')) {
    //             $phone = '57' . $phone;
    //         }
    //         $request->merge(['phone' => $phone]);
    //     }

    //     if ($request->has('password')) {
    //         $user->update([
    //             "password" => bcrypt($request->password)
    //         ]);
    //         return response()->json([
    //             "message" => 200,
    //         ]);
    //     }

    //     $is_exists_email = User::where("id", "<>", auth("api")->user()->id)
    //                             ->where("email", $request->email)->first();
    //     if ($is_exists_email) {
    //         return response()->json([
    //             "message" => 403,
    //             "message_text" => "El usuario ya existe"
    //         ]);
    //     }

    //     if ($request->hasFile("file_imagen")) {
    //         if ($user->avatar) {
    //             Storage::delete($user->avatar);
    //         }
    //         $path = Storage::putFile("users", $request->file("file_imagen"));
    //         $request->merge(["avatar" => $path]);
    //     }

    //     $user->update($request->all());

    //     return response()->json([
    //         "message" => 200,
    //         "url_tienda" => "https://app.treggio.co/{$user->slug}"
    //     ]);
    // }


    public function update(Request $request) {
        $user = User::find(auth("api")->user()->id);

        // Validación de campos
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'store_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|base64image|max:2048', // Valida Base64 (crearemos esta regla)
        ]);

        // 1. Generar slug si cambia el store_name
        if ($request->has('store_name') && $request->store_name !== $user->store_name) {
            $slug = $this->generateUniqueSlug($request->store_name, $user->id);
            $request->merge(['slug' => $slug]);
        }

        // 2. Formatear teléfono
        if ($request->has('phone')) {
            $request->merge(['phone' => $this->formatPhone($request->phone)]);
        }

        // 3. Manejo de contraseña (sin cambios)
        if ($request->has('password')) {
            $user->update(["password" => bcrypt($request->password)]);
            return response()->json(["message" => 200]);
        }

        // 4. Procesar avatar (Base64 o archivo)
        if ($request->has('avatar') && str_starts_with($request->avatar, 'data:image')) {
            $path = $this->saveBase64Image($request->avatar, 'users', $user->avatar);
            $request->merge(["avatar" => $path]);
        }

        // 5. Actualizar datos
        $user->update($request->except(['password', 'file_imagen']));

        return response()->json([
            "message" => 200,
            "avatar_url" => Storage::url($user->avatar), // URL pública
            "url_tienda" => "https://app.treggio.co/tienda/{$user->slug}"
        ]);
    }

    // -------------------------------
    // Métodos auxiliares (privados)
    // -------------------------------

    private function generateUniqueSlug($storeName, $userId): string {
        $slug = Str::slug($storeName);
        $originalSlug = $slug;
        $counter = 1;

        while (User::where('slug', $slug)->where('id', '<>', $userId)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function formatPhone($phone): string {
        return str_starts_with($phone, '57') ? $phone : '57' . $phone;
    }

    private function saveBase64Image($base64, $folder, $oldPath = null): string {
        // Eliminar el prefijo "data:image/..."
        $imageData = explode(',', $base64)[1];
        $decodedImage = base64_decode($imageData);

        // Determinar el tipo MIME
        $mime = finfo_buffer(finfo_open(), $decodedImage, FILEINFO_MIME_TYPE);
        $extension = explode('/', $mime)[1] ?? 'png';

        // Generar nombre único
        $filename = $folder . '/' . Str::uuid() . '.' . $extension;

        // Eliminar imagen anterior si existe
        if ($oldPath && Storage::exists($oldPath)) {
            Storage::delete($oldPath);
        }

        // Guardar en storage
        Storage::put($filename, $decodedImage);

        return $filename;
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

}
