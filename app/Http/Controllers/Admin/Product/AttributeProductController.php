<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Models\Product\Attribute;
use App\Models\Product\Propertie;
use App\Http\Controllers\Controller;
use App\Models\User;

class AttributeProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        // Filtra los atributos por el user_id del usuario autenticado
        $attributes = Attribute::where("user_id", auth()->id())
                                ->where("name", "like", "%".$search."%")
                                ->orderBy("id", "desc")
                                ->paginate(25);

        return response()->json([
            "total" => $attributes->total(),
            "attributes" => $attributes->map(function($attribute) {
                return [
                    "id" => $attribute->id,
                    "name" => $attribute->name,
                    "type_attribute" => $attribute->type_attribute,
                    "state" => $attribute->state,
                    "created_at" => $attribute->created_at->format("Y-m-d h:i:s"),
                    "properties" => $attribute->properties->map(function($propertie) {
                        return [
                            "id" => $propertie->id,
                            "name" => $propertie->name,
                            "code" => $propertie->code,
                        ];
                    })
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isValida = Attribute::where("name", $request->name)->first();
        if($isValida){
            return response()->json(["message" => 403]);
        }

        // Asigna el user_id del usuario autenticado
        $request->merge(['user_id' => auth()->id()]);

        $attribute = Attribute::create($request->all());

        return response()->json([
            "message" => 200,
            "attribute" => [
                "id" => $attribute->id,
                "name" => $attribute->name,
                "type_attribute" => $attribute->type_attribute,
                "state" => $attribute->state,
                "created_at" => $attribute->created_at->format("Y-m-d h:i:s"),
                "properties" => $attribute->properties->map(function($propertie) {
                    return [
                        "id" => $propertie->id,
                        "name" => $propertie->name,
                        "code" => $propertie->code,
                    ];
                })
            ],
        ]);
    }

    public function store_propertie(Request $request) {
        $isValida = Propertie::where("name", $request->name)
                            ->where("attribute_id", $request->attribute_id)
                            ->first();

        if($isValida){
            return response()->json(["message" => 403]);
        }
        $propertie = Propertie::create($request->all());

        return response()->json([
            "message" => 200,
            "propertie" => [
                "id" => $propertie->id,
                "name" => $propertie->name,
                "code" => $propertie->code,
                "created_at" => $propertie->created_at->format("Y-m-d h:i:s"),
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
        $isValida = Attribute::where("id", "<>", $id)->where("name", $request->name)->first();
        if($isValida){
            return response()->json(["message" => 403]);
        }

        $attribute = Attribute::findOrFail($id);

        // Verifica que el atributo pertenezca al usuario autenticado
        if ($attribute->user_id !== auth()->id()) {
            return response()->json(["message" => 403, "message_text" => "No tienes permiso para actualizar este atributo"]);
        }

        $attribute->update($request->all());

        return response()->json([
            "message" => 200,
            "attribute" => [
                "id" => $attribute->id,
                "name" => $attribute->name,
                "type_attribute" => $attribute->type_attribute,
                "state" => $attribute->state,
                "created_at" => $attribute->created_at->format("Y-m-d h:i:s"),
                "properties" => $attribute->properties->map(function($propertie) {
                    return [
                        "id" => $propertie->id,
                        "name" => $propertie->name,
                        "code" => $propertie->code,
                    ];
                })
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $attribute = Attribute::findOrFail($id);

        // Verifica que el atributo pertenezca al usuario autenticado
        if ($attribute->user_id !== auth()->id()) {
            return response()->json(["message" => 403, "message_text" => "No tienes permiso para eliminar este atributo"]);
        }

        if($attribute->specifications->count() > 0 ||
        $attribute->variations->count() > 0){
            return response()->json(["message" => 403, "message_text" => "EL ATRIBUTO YA ESTA RELACIONADO CON ALGUNOS O UN PRODUCTO"]);
        }

        $attribute->delete();

        return response()->json([
            "message" => 200,
        ]);
    }

    public function destroy_propertie($id) {
        $propertie = Propertie::findOrFail($id);

        if($propertie->specifications->count() > 0 ||
        $propertie->variations->count() > 0){
            return response()->json(["message" => 403, "message_text" => "LA PROPIEDAD YA ESTA RELACIONADA CON ALGUNOS O UN PRODUCTO"]);
        }

        $propertie->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
