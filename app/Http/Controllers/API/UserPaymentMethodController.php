<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserPaymentMethodController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return response()->json([
            'available' => PaymentMethod::where('is_active', true)->get(),
            'selected' => $user->paymentMethods()->get() // Devuelve objetos completos, no solo IDs
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'methods' => 'required|array',
            'methods.*' => 'exists:payment_methods,id'
        ]);

        $user = auth()->user();
        $user->paymentMethods()->sync($request->methods);

        return response()->json([
            'message' => 'Métodos actualizados correctamente',
            'selected' => $user->paymentMethods()->get()
        ]);
    }

    public function destroy($methodId)
    {
        $user = Auth::user();

        // Verificar que el método existe y está asignado al usuario
        $exists = DB::table('user_payment_methods')
                  ->where('user_id', $user->id)
                  ->where('payment_method_id', $methodId)
                  ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'Método de pago no encontrado o no asignado al usuario'
            ], 404);
        }

        // Eliminar la relación usando Query Builder para asegurar funcionamiento
        DB::table('user_payment_methods')
          ->where('user_id', $user->id)
          ->where('payment_method_id', $methodId)
          ->delete();

        return response()->json([
            'message' => 'Método de pago eliminado correctamente',
            'selected' => $user->paymentMethods()->get()
        ]);
    }
}
