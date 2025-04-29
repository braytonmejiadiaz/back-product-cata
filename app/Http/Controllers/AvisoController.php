<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\User;
use Illuminate\Http\Request;

class AvisoController extends Controller
{
    public function index()
    {
        return response()->json(Aviso::with('user')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'contenido' => 'required',
            'estilos' => 'nullable|array'
        ]);

        $aviso = Aviso::create([
            'contenido' => $request->contenido,
            'estilos' => $request->estilos,
            'user_id' => auth()->id()
        ]);

        return response()->json($aviso, 201);
    }

    public function show(Aviso $aviso)
    {
        return response()->json($aviso);
    }

    public function update(Request $request, Aviso $aviso)
    {
        $request->validate([
            'contenido' => 'required',
            'estilos' => 'nullable|array'
        ]);

        $aviso->update($request->all());

        return response()->json($aviso);
    }

    public function destroy(Aviso $aviso)
    {
        $aviso->delete();
        return response()->json(null, 204);
    }

    public function obtenerAvisoPublico($slug)
    {
        // Buscar el usuario por slug
        $user = User::where('slug', $slug)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Obtener el último aviso del usuario
        $aviso = Aviso::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

        return response()->json($aviso);
    }

    public function obtenerAvisoUsuario(Request $request)
{
    // Obtiene el ID del usuario autenticado
    $userId = auth()->id();

    // Busca el último aviso del usuario
    $aviso = Aviso::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();

    // Si no encuentra aviso, devuelve null (no 404)
    return response()->json($aviso);
}
}
