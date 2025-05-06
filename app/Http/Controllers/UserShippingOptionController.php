<?php

namespace App\Http\Controllers;

use App\Models\UserShippingOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserShippingOptionController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        // Crear registro con valores por defecto si no existe
        if (!$user->shippingOption) {
            $user->shippingOption()->create([
                'is_free' => true,     // Por defecto envío gratis
                'shipping_rate' => 0   // Valor 0 cuando es gratis
            ]);
            $user->refresh();
        }

        return response()->json([
            'is_free' => $user->shippingOption->is_free,
            'shipping_rate' => $user->shippingOption->shipping_rate
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'is_free' => 'required|boolean',
            'shipping_rate' => 'nullable|numeric|min:0'
        ]);

        // Si es envío gratis, forzar shipping_rate a 0
        if ($validated['is_free']) {
            $validated['shipping_rate'] = 0;
        }

        // Actualizar o crear si no existe
        $user->shippingOption()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'is_free' => $user->shippingOption->is_free,
            'shipping_rate' => $user->shippingOption->shipping_rate
        ]);
    }
}
