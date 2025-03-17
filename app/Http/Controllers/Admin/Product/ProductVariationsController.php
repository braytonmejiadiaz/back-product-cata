<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Models\Product\Attribute;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductVariation;

class ProductVariationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $product_id = $request->product_id;

        $variations = ProductVariation::where("product_id",$product_id)->where("product_variation_id",NULL)->orderBy("id","desc")->get();

        return response()->json([
            "variations" =>  $variations->map(function($variation) {
                return [
                    "id" => $variation->id,
                    "product_id" => $variation->product_id,
                    "attribute_id" => $variation->attribute_id,
                    "attribute" => $variation->attribute ? [
                        "name" => $variation->attribute->name,
                        "type_attribute" => $variation->attribute->type_attribute,
                    ] : NULL,
                    "propertie_id" => $variation->propertie_id,
                    "propertie" => $variation->propertie ? [
                        "name" => $variation->propertie->name,
                        "code" => $variation->propertie->code,
                    ] : NULL,
                    "value_add" => $variation->value_add,
                    "add_price" => $variation->add_price,
                    "stock" => $variation->stock
                ];
            })
        ]);
    }

    public function config(){

        $attributes_specifications = Attribute::where("state",1)->orderBy("id","desc")->get();

        $attributes_variations = Attribute::where("state",1)->whereIn("type_attribute",[1,3])
                                ->orderBy("id","desc")->get();

        return response()->json([
            "attributes_specifications" => $attributes_specifications->map(function($specification){
                return [
                    "id" => $specification->id,
                    "name" => $specification->name,
                    "type_attribute" => $specification->type_attribute,
                    "state" => $specification->state,
                    "created_at" => $specification->created_at->format("Y-m-d h:i:s"),
                    "properties" => $specification->properties->map(function($propertie) {
                        return [
                            "id" => $propertie->id,
                            "name" => $propertie->name,
                            "code" => $propertie->code,
                        ];
                    })
                ];
            }),
            "attributes_variations" => $attributes_variations->map(function($variation){
                return [
                    "id" => $variation->id,
                    "name" => $variation->name,
                    "type_attribute" => $variation->type_attribute,
                    "state" => $variation->state,
                    "created_at" => $variation->created_at->format("Y-m-d h:i:s"),
                    "properties" => $variation->properties->map(function($propertie) {
                        return [
                            "id" => $propertie->id,
                            "name" => $propertie->name,
                            "code" => $propertie->code,
                        ];
                    })
                ];
            })
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $variations_exits = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)->count();
        if($variations_exits > 0){
            $variations_attributes_exits = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)
                                            ->where("attribute_id",$request->attribute_id)
                                            ->count();
            if($variations_attributes_exits == 0){
                return response()->json(["message" => 403,"message_text" => "NO SE PUEDE AGREGAR UN ATRIBUTO DIFERENTE DEL QUE YA HAY EN LA LISTA"]);
            }
        }
        $is_valid_variation = null;
        if($request->propertie_id){
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)
                                                    ->where("attribute_id",$request->attribute_id)
                                                    ->where("propertie_id",$request->propertie_id)
                                                    ->first();
        }else{
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)
                                                        ->where("attribute_id",$request->attribute_id)
                                                        ->where("value_add",$request->value_add)
                                                        ->first();                                          
        }
        if($is_valid_variation){
            return response()->json(["message" => 403,"message_text" => "LA VARIACION YA EXISTE, INTENTE OTRA COMBINACIÓN"]); 
        }

        $product_variation = ProductVariation::create($request->all());

        return response()->json([
            "message" => 200,
            "variation" => [
                "id" => $product_variation->id,
                "product_id" => $product_variation->product_id,
                "attribute_id" => $product_variation->attribute_id,
                "attribute" => $product_variation->attribute ? [
                    "name" => $product_variation->attribute->name,
                    "type_attribute" => $product_variation->attribute->type_attribute,
                ] : NULL,
                "propertie_id" => $product_variation->propertie_id,
                "propertie" => $product_variation->propertie ? [
                    "name" => $product_variation->propertie->name,
                    "code" => $product_variation->propertie->code,
                ] : NULL,
                "value_add" => $product_variation->value_add,
                "add_price" => $product_variation->add_price,
                "stock" => $product_variation->stock
            ]
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
        $variations_exits = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)->count();
        if($variations_exits > 0){
            $variations_attributes_exits = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)
                                            ->where("attribute_id",$request->attribute_id)
                                            ->count();
            if($variations_attributes_exits == 0){
                return response()->json(["message" => 403,"message_text" => "NO SE PUEDE AGREGAR UN ATRIBUTO DIFERENTE DEL QUE YA HAY EN LA LISTA"]);
            }
        }
        $is_valid_variation = null;
        if($request->propertie_id){
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)
                                                    ->where("id","<>",$id)
                                                    ->where("attribute_id",$request->attribute_id)
                                                    ->where("propertie_id",$request->propertie_id)
                                                    ->first();
        }else{
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",NULL)
                                                    ->where("id","<>",$id)
                                                        ->where("attribute_id",$request->attribute_id)
                                                        ->where("value_add",$request->value_add)
                                                        ->first();                                          
        }
        if($is_valid_variation){
            return response()->json(["message" => 403,"message_text" => "LA VARIACION YA EXISTE, INTENTE OTRA COMBINACIÓN"]); 
        }

        $product_variation = ProductVariation::findOrFail($id);
        $product_variation->update($request->all());
        return response()->json([
            "message" => 200,
            "variation" => [
                "id" => $product_variation->id,
                "product_id" => $product_variation->product_id,
                "attribute_id" => $product_variation->attribute_id,
                "attribute" => $product_variation->attribute ? [
                    "name" => $product_variation->attribute->name,
                    "type_attribute" => $product_variation->attribute->type_attribute,
                ] : NULL,
                "propertie_id" => $product_variation->propertie_id,
                "propertie" => $product_variation->propertie ? [
                    "name" => $product_variation->propertie->name,
                    "code" => $product_variation->propertie->code,
                ] : NULL,
                "value_add" => $product_variation->value_add,
                "add_price" => $product_variation->add_price,
                "stock" => $product_variation->stock
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product_variation = ProductVariation::findOrFail($id);
        $product_variation->delete();
        // UNA VALIDACIÓN PARA QUE NO SE PUEDA ELIMINAR EN CASO EL PRODUCTO O LA VARACION ESTE EN EL CARRITO DE COMPRA O EN EL DETALLADO DE ALGUNA COMPRA
        return response()->json([
            "message" => 200,
        ]);
    }
}
