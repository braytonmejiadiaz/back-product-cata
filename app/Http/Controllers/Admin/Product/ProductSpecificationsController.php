<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductSpecification;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Auth;

class ProductSpecificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $product_id = $request->product_id;

        // Verificar que el producto pertenezca al usuario autenticado
        $product = Product::where('id', $product_id)
                         ->where('user_id', $user->id)
                         ->first();

        if (!$product) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes permiso para acceder a este producto"
            ]);
        }

        $specifications = ProductSpecification::with(['attribute', 'propertie'])
            ->where("product_id", $product_id)
            ->orderBy("id", "desc")
            ->get();

        return response()->json([
            "specifications" => $specifications->map(function($specification) {
                return [
                    "id" => $specification->id,
                    "product_id" => $specification->product_id,
                    "attribute_id" => $specification->attribute_id,
                    "attribute" => $specification->attribute ? [
                        "name" => $specification->attribute->name,
                        "type_attribute" => $specification->attribute->type_attribute,
                    ] : null,
                    "propertie_id" => $specification->propertie_id,
                    "propertie" => $specification->propertie ? [
                        "name" => $specification->propertie->name,
                        "code" => $specification->propertie->code,
                    ] : null,
                    "value_add" => $specification->value_add,
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Verificar que el producto pertenezca al usuario
        $product = Product::where('id', $request->product_id)
                         ->where('user_id', $user->id)
                         ->first();

        if (!$product) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes permiso para modificar este producto"
            ]);
        }

        // Validar si la especificación ya existe
        $is_valid_variation = null;
        if ($request->propertie_id) {
            $is_valid_variation = ProductSpecification::where("product_id", $request->product_id)
                ->where("attribute_id", $request->attribute_id)
                ->where("propertie_id", $request->propertie_id)
                ->first();
        } else {
            $is_valid_variation = ProductSpecification::where("product_id", $request->product_id)
                ->where("attribute_id", $request->attribute_id)
                ->where("value_add", $request->value_add)
                ->first();
        }

        if ($is_valid_variation) {
            return response()->json([
                "message" => 403,
                "message_text" => "LA ESPECIFICACIÓN YA EXISTE, INTENTE OTRA COMBINACIÓN"
            ]);
        }

        $product_specification = ProductSpecification::create($request->all());

        return response()->json([
            "message" => 200,
            "specification" => [
                "id" => $product_specification->id,
                "product_id" => $product_specification->product_id,
                "attribute_id" => $product_specification->attribute_id,
                "attribute" => $product_specification->attribute ? [
                    "name" => $product_specification->attribute->name,
                    "type_attribute" => $product_specification->attribute->type_attribute,
                ] : null,
                "propertie_id" => $product_specification->propertie_id,
                "propertie" => $product_specification->propertie ? [
                    "name" => $product_specification->propertie->name,
                    "code" => $product_specification->propertie->code,
                ] : null,
                "value_add" => $product_specification->value_add,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Este método puede mantenerse igual o implementar la verificación de usuario
        return response()->json([
            "message" => 501,
            "message_text" => "Método no implementado"
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        // Obtener la especificación verificando que pertenezca al usuario
        $product_specification = ProductSpecification::with(['product'])
            ->where('id', $id)
            ->first();

        if (!$product_specification || $product_specification->product->user_id != $user->id) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes permiso para modificar esta especificación"
            ]);
        }

        // Validar si la especificación ya existe
        $is_valid_variation = null;
        if ($request->propertie_id) {
            $is_valid_variation = ProductSpecification::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)
                ->where("propertie_id", $request->propertie_id)
                ->first();
        } else {
            $is_valid_variation = ProductSpecification::where("product_id", $request->product_id)
                ->where("id", "<>", $id)
                ->where("attribute_id", $request->attribute_id)
                ->where("value_add", $request->value_add)
                ->first();
        }

        if ($is_valid_variation) {
            return response()->json([
                "message" => 403,
                "message_text" => "LA ESPECIFICACIÓN YA EXISTE, INTENTE OTRA COMBINACIÓN"
            ]);
        }

        $product_specification->update($request->all());

        return response()->json([
            "message" => 200,
            "specification" => [
                "id" => $product_specification->id,
                "product_id" => $product_specification->product_id,
                "attribute_id" => $product_specification->attribute_id,
                "attribute" => $product_specification->attribute ? [
                    "name" => $product_specification->attribute->name,
                    "type_attribute" => $product_specification->attribute->type_attribute,
                ] : null,
                "propertie_id" => $product_specification->propertie_id,
                "propertie" => $product_specification->propertie ? [
                    "name" => $product_specification->propertie->name,
                    "code" => $product_specification->propertie->code,
                ] : null,
                "value_add" => $product_specification->value_add,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $product_specification = ProductSpecification::with(['product'])
            ->where('id', $id)
            ->first();

        if (!$product_specification || $product_specification->product->user_id != $user->id) {
            return response()->json([
                "message" => 403,
                "message_text" => "No tienes permiso para eliminar esta especificación"
            ]);
        }

        $product_specification->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
