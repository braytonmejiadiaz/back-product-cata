<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;



class FontController extends Controller
{
    public function getAvailableFonts()
    {
        return response()->json([
            'fonts' => config('fonts.available'),
            'current_font' => Auth::user()->selected_font
        ]);
    }

    public function updateFont(Request $request)
    {
        $request->validate([
            'font' => 'required|in:'.implode(',', array_keys(config('fonts.available')))
        ]);

        $user = Auth::user();
        $user->selected_font = $request->font;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Fuente actualizada correctamente',
            'font_family' => $user->font_family
        ]);
    }

    public function getUserFont()
    {
        $user = Auth::user();
        $fonts = config('fonts.available');

        return response()->json([
            'font_family' => $fonts[$user->selected_font]['family'] ?? config('fonts.default'),
            'font_name' => $user->selected_font
        ]);
    }


    public function getPublicUserFont($slug)
{
    // Buscar usuario por slug
    $user = User::where('slug', $slug)->first();

    if (!$user) {
        return response()->json([
            'error' => 'Usuario no encontrado'
        ], 404);
    }

    $fonts = config('fonts.available');
    $selectedFont = $user->selected_font ?? config('fonts.default');

    // Verificar que la fuente seleccionada exista en las disponibles
    if (!array_key_exists($selectedFont, $fonts)) {
        $selectedFont = config('fonts.default');
    }

    return response()->json([
        'selected_font' => $selectedFont,
        'font_family' => $fonts[$selectedFont]['family'],
        'font_url' => $fonts[$selectedFont]['url'],
        'available_fonts' => $fonts
    ]);
}
}
