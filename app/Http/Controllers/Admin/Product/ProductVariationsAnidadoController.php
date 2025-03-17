<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Models\Product\Attribute;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductVariation;

class ProductVariationsAnidadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $product_id = $request->product_id;
        $product_variation_id = $request->product_variation_id;

        $variations = ProductVariation::where("product_id",$product_id)->where("product_variation_id",$product_variation_id)->orderBy("id","desc")->get();

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
                    "stock" => $variation->stock,
                    "product_variation_id" => $variation->product_variation_id
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $product_variation_id = $request->product_variation_id;
        $variations_exits = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",$product_variation_id)->count();
        if($variations_exits > 0){
            $variations_attributes_exits = ProductVariation::where("product_id",$request->product_id)
                                        ->where("product_variation_id",$product_variation_id)
                                            ->where("attribute_id",$request->attribute_id)
                                            ->count();
            if($variations_attributes_exits == 0){
                return response()->json(["message" => 403,"message_text" => "NO SE PUEDE AGREGAR UN ATRIBUTO DIFERENTE DEL QUE YA HAY EN LA LISTA"]);
            }
        }
        $is_valid_variation = null;
        if($request->propertie_id){
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)
                                                    ->where("product_variation_id",$product_variation_id)
                                                    ->where("attribute_id",$request->attribute_id)
                                                    ->where("propertie_id",$request->propertie_id)
                                                    ->first();
        }else{
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)
                                                    ->where("product_variation_id",$product_variation_id)
                                                        ->where("attribute_id",$request->attribute_id)
                                                        ->where("value_add",$request->value_add)
                                                        ->first();                                          
        }
        if($is_valid_variation){
            return response()->json(["message" => 403,"message_text" => "LA VARIACION YA EXISTE, INTENTE OTRA COMBINACIÓN"]); 
        }

        $product_var = ProductVariation::find($product_variation_id);
        $TOTAL_STOCK_VARIATION_CENTRAL = $product_var ? $product_var->stock : 0;

        $SUM_TOTAL_STOCK_ANIDADOS = ProductVariation::where("product_id",$request->product_id)
                                                        ->where("product_variation_id",$product_variation_id)
                                                        ->sum("stock");
        $SUM_TOTAL_STOCK_ANIDADOS = $SUM_TOTAL_STOCK_ANIDADOS + $request->stock;

        if($SUM_TOTAL_STOCK_ANIDADOS > $TOTAL_STOCK_VARIATION_CENTRAL){
            return response()->json(["message" => 403,"message_text" => "NO SE PUEDE CREAR LA VARIACIÓN PORQUE EL STOCK SUPERA SU VARIACIÓN PADRE"]);
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
                "stock" => $product_variation->stock,
                "product_variation_id" => $product_variation->product_variation_id,
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
        $product_variation_id = $request->product_variation_id;
        $variations_exits = ProductVariation::where("product_id",$request->product_id)->where("product_variation_id",$product_variation_id)->count();
        if($variations_exits > 0){
            $variations_attributes_exits = ProductVariation::where("product_id",$request->product_id)
                                            ->where("product_variation_id",$product_variation_id)
                                            ->where("attribute_id",$request->attribute_id)
                                            ->count();
            if($variations_attributes_exits == 0){
                return response()->json(["message" => 403,"message_text" => "NO SE PUEDE AGREGAR UN ATRIBUTO DIFERENTE DEL QUE YA HAY EN LA LISTA"]);
            }
        }
        $is_valid_variation = null;
        if($request->propertie_id){
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)
                                                    ->where("id","<>",$id)
                                                    ->where("product_variation_id",$product_variation_id)
                                                    ->where("attribute_id",$request->attribute_id)
                                                    ->where("propertie_id",$request->propertie_id)
                                                    ->first();
        }else{
            $is_valid_variation = ProductVariation::where("product_id",$request->product_id)
                                                    ->where("id","<>",$id)
                                                    ->where("product_variation_id",$product_variation_id)
                                                        ->where("attribute_id",$request->attribute_id)
                                                        ->where("value_add",$request->value_add)
                                                        ->first();                                          
        }
        if($is_valid_variation){
            return response()->json(["message" => 403,"message_text" => "LA VARIACION YA EXISTE, INTENTE OTRA COMBINACIÓN"]); 
        }

        $product_var = ProductVariation::find($product_variation_id);
        $TOTAL_STOCK_VARIATION_CENTRAL = $product_var ? $product_var->stock : 0;

        $SUM_TOTAL_STOCK_ANIDADOS = ProductVariation::where("product_id",$request->product_id)
                                                        ->where("id","<>",$id)
                                                        ->where("product_variation_id",$product_variation_id)
                                                        ->sum("stock");
        $SUM_TOTAL_STOCK_ANIDADOS = $SUM_TOTAL_STOCK_ANIDADOS + $request->stock;

        if($SUM_TOTAL_STOCK_ANIDADOS > $TOTAL_STOCK_VARIATION_CENTRAL){
            return response()->json(["message" => 403,"message_text" => "NO SE PUEDE CREAR LA VARIACIÓN PORQUE EL STOCK SUPERA SU VARIACIÓN PADRE"]);
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
                "stock" => $product_variation->stock,
                "product_variation_id" => $product_variation->product_variation_id,
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
