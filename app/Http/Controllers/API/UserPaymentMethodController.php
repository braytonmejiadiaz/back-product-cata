<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPaymentMethodController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return response()->json([
            'available' => PaymentMethod::where('is_active', true)->get(),
            'selected' => $user->paymentMethods()->pluck('id') // Solo IDs de métodos seleccionados
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
            'selected' => $user->paymentMethods()->pluck('id')
        ]);
    }
}
