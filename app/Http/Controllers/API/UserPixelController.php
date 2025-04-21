<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pixel; // Asegúrate de importar el modelo

class UserPixelController extends Controller
{
    public function index(Request $request) // Añade Request como parámetro
    {
        return $request->user()->pixels;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:meta',
            'pixel_id' => 'required|string|max:50',
        ]);

        $pixel = $request->user()->pixels()->updateOrCreate(
            ['platform' => $validated['platform']],
            ['pixel_id' => $validated['pixel_id']]
        );

        return response()->json($pixel, 201);
    }

    public function destroy(string $id)
    {
        $pixel = $request->user()->pixels()->findOrFail($id);
        $pixel->delete();

        return response()->json(null, 204);
    }
}
