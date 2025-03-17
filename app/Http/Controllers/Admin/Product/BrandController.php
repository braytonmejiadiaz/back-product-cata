<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Models\Product\Brand;
use App\Http\Controllers\Controller;
use App\Models\User;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        // Filtra las marcas por el user_id del usuario autenticado
        $brands = Brand::where("user_id", auth()->id())
                        ->where("name", "like", "%".$search."%")
                        ->orderBy("id", "desc")
                        ->paginate(25);

        return response()->json([
            "total" => $brands->total(),
            "brands" => $brands->map(function($brand) {
                return [
                    "id" => $brand->id,
                    "name" => $brand->name,
                    "state" => $brand->state,
                    "created_at" => $brand->created_at->format("Y-m-d h:i:s"),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isValida = Brand::where("name", $request->name)->first();
        if ($isValida) {
            return response()->json(["message" => 403]);
        }

        // Asigna el user_id del usuario autenticado
        $request->merge(['user_id' => auth()->id()]);

        $brand = Brand::create($request->all());

        return response()->json([
            "message" => 200,
            "brand" => [
                "id" => $brand->id,
                "name" => $brand->name,
                "state" => $brand->state,
                "created_at" => $brand->created_at->format("Y-m-d h:i:s"),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $isValida = Brand::where("id", "<>", $id)->where("name", $request->name)->first();
        if ($isValida) {
            return response()->json(["message" => 403]);
        }

        $brand = Brand::findOrFail($id);

        // Verifica que la marca pertenezca al usuario autenticado
        if ($brand->user_id !== auth()->id()) {
            return response()->json(["message" => 403, "message_text" => "No tienes permiso para actualizar esta marca"]);
        }

        $brand->update($request->all());

        return response()->json([
            "message" => 200,
            "brand" => [
                "id" => $brand->id,
                "name" => $brand->name,
                "state" => $brand->state,
                "created_at" => $brand->created_at->format("Y-m-d h:i:s"),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $brand = Brand::findOrFail($id);

        // Verifica que la marca pertenezca al usuario autenticado
        if ($brand->user_id !== auth()->id()) {
            return response()->json(["message" => 403, "message_text" => "No tienes permiso para eliminar esta marca"]);
        }

        if($brand->products->count() > 0){
            return response()->json(["message" => 403, "message_text" => "LA MARCA YA ESTA RELACIONADA CON ALGUNOS O UN PRODUCTO"]);
        }

        $brand->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
