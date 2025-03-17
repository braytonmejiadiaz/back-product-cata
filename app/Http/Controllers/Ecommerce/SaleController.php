<?php

namespace App\Http\Controllers\Ecommerce;

use App\Mail\SaleMail;
use App\Models\Sale\Cart;
use App\Models\Sale\Sale;
use Illuminate\Http\Request;
use App\Models\Sale\SaleTemp;
use App\Models\Product\Product;
use App\Models\Sale\SaleAddres;
use App\Models\Sale\SaleDetail;
use MercadoPago\MercadoPagoConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\Product\ProductVariation;
use App\Http\Resources\Ecommerce\Sale\SaleResource;
use MercadoPago\Client\Preference\PreferenceClient;
use App\Http\Resources\Ecommerce\Sale\SaleCollection;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function orders(){
        $user = auth("api")->user();

        $sales = Sale::where("user_id",$user->id)->orderBy("id","desc")->get();

        return response()->json([
            "sales" => SaleCollection::make($sales),
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->request->add(["user_id" => auth("api")->user()->id]);
        $sale = Sale::create($request->all());

        $carts = Cart::where("user_id",auth("api")->user()->id)->get();

        foreach ($carts as $key => $cart) {
            $nCart = $cart;
            $new_detail = [];
            $new_detail = $nCart->toArray();
            $new_detail["sale_id"] = $sale->id;
            SaleDetail::create($new_detail);
            // DESCUENTO DE STOCK DEL PRODUCTO
            if($cart->product_variation_id){
               $variation = ProductVariation::find($cart->product_variation_id);
               if($variation->variation_father){
                    $variation->variation_father->update([
                        "stock" => $variation->variation_father->stock - $cart->quantity
                    ]);
                    $variation->update([
                        "stock" => $variation->stock - $cart->quantity
                    ]);
               }else{
                    $variation->update([
                        "stock" => $variation->stock - $cart->quantity
                    ]);
               }
            }else{
                $product = Product::find($cart->product_id);
                $product->update([
                    "stock" => $product->stock - $cart->quantity
                ]);
            }
            // LA ELIMINACIÓN DEL CARRITO
            $cart->delete();
        }
        $sale_addres = $request->sale_address;
        $sale_addres["sale_id"] = $sale->id;
        $sale_address = SaleAddres::create($sale_addres);
        // EL CORREO QUE LE DEBE LLEGAR AL CLIENTE CON LA COMPRA QUE ACABA DE REALIZAR
        $sale_new = Sale::findOrFail($sale->id);
        Mail::to(auth("api")->user()->email)->send(new SaleMail(auth("api")->user(),$sale_new));
        return response()->json([
            "message" => 200,
        ]);
    }


    public function checkout_mercadopago(Request $request) {
        //

        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "Bearer ".env("MERCADOPAGO_KEY")
        ])->get("https://api.mercadopago.com/v1/payments/".$request->n_transaccion);
        
        $format_response = json_decode($response->getBody()->getContents(),true);

        $sale_temp = SaleTemp::where("user_id",auth("api")->user()->id)->first();

        $request->request->add([
            "user_id" => auth("api")->user()->id,
            "total" => $format_response["transaction_amount"],
            "subtotal" => $format_response["transaction_amount"],
            "description" => $sale_temp->description ? $sale_temp->description : NULL,
        ]);
        $sale = Sale::create($request->all());

        $carts = Cart::where("user_id",auth("api")->user()->id)->get();

        foreach ($carts as $key => $cart) {
            $nCart = $cart;
            $new_detail = [];
            $new_detail = $nCart->toArray();
            $new_detail["sale_id"] = $sale->id;
            SaleDetail::create($new_detail);
            // DESCUENTO DE STOCK DEL PRODUCTO
            if($cart->product_variation_id){
                $variation = ProductVariation::find($cart->product_variation_id);
                if($variation->variation_father){
                     $variation->variation_father->update([
                         "stock" => $variation->variation_father->stock - $cart->quantity
                     ]);
                     $variation->update([
                         "stock" => $variation->stock - $cart->quantity
                     ]);
                }else{
                     $variation->update([
                         "stock" => $variation->stock - $cart->quantity
                     ]);
                }
             }else{
                 $product = Product::find($cart->product_id);
                 $product->update([
                     "stock" => $product->stock - $cart->quantity
                 ]);
             }
             // LA ELIMINACIÓN DEL CARRITO
             $cart->delete();
        }
        
        $sale_addres = json_decode($sale_temp->sale_address,true);
        $sale_addres["sale_id"] = $sale->id;
        $sale_address = SaleAddres::create($sale_addres);
        // EL CORREO QUE LE DEBE LLEGAR AL CLIENTE CON LA COMPRA QUE ACABA DE REALIZAR
        $sale_new = Sale::findOrFail($sale->id);
        Mail::to(auth("api")->user()->email)->send(new SaleMail(auth("api")->user(),$sale_new));
        return response()->json([
            "message" => 200,
        ]);
    }

    public function mercadopago(Request $request) {

        MercadoPagoConfig::setAccessToken(env("MERCADOPAGO_KEY"));
        $client = new PreferenceClient();
        $client->auto_return = "approved";

        $carts = Cart::where("user_id",auth("api")->user()->id)->get();

        $array_carts = [];

        foreach ($carts as $key => $cart) {
            array_push($array_carts,[
                "title" => $cart->product->title,
                "quantity" => $cart->quantity,
                "currency_id" => $cart->currency,
                "unit_price" => $cart->total,
            ]);
        }
        $datos = array(
            "items"=> $array_carts,
            "back_urls" =>array(
                "success" => env("URL_TIENDA")."mercado-pago-success",
                "failure" => env("URL_TIENDA")."mercado-pago-failure",
                "pending" => env("URL_TIENDA")."mercado-pago-pending"
            ),
            "redirect_urls" =>array(
                "success" => env("URL_TIENDA")."mercado-pago-success",
                "failure" => env("URL_TIENDA")."mercado-pago-failure",
                "pending" => env("URL_TIENDA")."mercado-pago-pending"
            ),
            "auto_return" => "approved",
            "external_reference" => uniqid(),
        );
        $preference = $client->create($datos);

        return response()->json([
            "preference" => $preference,
        ]);
    }

    public function checkout_temp(Request $request) {
        $sale_temp = SaleTemp::where("user_id",auth('api')->user()->id)->first();
        if($sale_temp){
            $sale_temp->update([
                "description" => $request->description,
                "sale_address" => json_encode($request->sale_address),
            ]);
        }else{
            SaleTemp::create([
                "user_id" => auth('api')->user()->id,
                "description" => $request->description,
                "sale_address" => json_encode($request->sale_address),
            ]);
        }

        return response()->json(true);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $sale = Sale::where("n_transaccion",$id)->first();

        return response()->json([
            "sale" => SaleResource::make($sale),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
