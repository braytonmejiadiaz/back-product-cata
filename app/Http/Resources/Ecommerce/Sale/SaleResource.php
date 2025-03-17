<?php

namespace App\Http\Resources\Ecommerce\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->resource->id,
            "user_id" => $this->resource->user_id,
            "user" => [
                "avatar" => $this->resource->user->avatar ? env("APP_URL"). "storage/". $this->resource->user->avatar : 'https://cdn-icons-png.flaticon.com/512/1476/1476614.png',
                "full_name" => $this->resource->user->name. ' '.$this->resource->user->surname,
            ],
            "method_payment" => $this->resource->method_payment,
            "currency_total" => $this->resource->currency_total,
            "currency_payment" => $this->resource->currency_payment,
            "discount" => $this->resource->discount,
            "subtotal" => $this->resource->subtotal,
            "total" => $this->resource->total,
            "price_dolar" => $this->resource->price_dolar,
            "description" => $this->resource->description,
            "n_transaccion" => $this->resource->n_transaccion,
            "sale_details" => $this->resource->sale_details->map(function($sale_detail) {
                return [
                    "id" => $sale_detail->id,
                    "product_id" => $sale_detail->product_id,
                    "product" => [
                        "id" => $sale_detail->product->id,
                        "title" => $sale_detail->product->title,
                        "slug"  => $sale_detail->product->slug,
                        "price_pen"  => $sale_detail->product->price_pen,
                        "price_usd"  => $sale_detail->product->price_usd,
                        "imagen"  => env("APP_URL")."storage/".$sale_detail->product->imagen,
                        "brand_id"  => $sale_detail->product->brand_id,
                        "brand" => $sale_detail->product->brand ? [
                            "id" => $sale_detail->product->brand->id,
                            "name" => $sale_detail->product->brand->name, 
                        ]: NULL,
                    ],
                    "type_discount" => $sale_detail->type_discount,
                    "discount" => $sale_detail->discount,
                    "type_campaing" => $sale_detail->type_campaing,
                    "code_cupon" => $sale_detail->code_cupon,
                    "code_discount" => $sale_detail->code_discount,
                    "product_variation_id" => $sale_detail->product_variation_id,
                    "product_variation" => $sale_detail->product_variation ? [
                        "id" => $sale_detail->product_variation->id,
                        "attribute_id" => $sale_detail->product_variation->attribute_id,
                        "attribute" => $sale_detail->product_variation->attribute ? [
                            "name" => $sale_detail->product_variation->attribute->name,
                            "type_attribute" => $sale_detail->product_variation->attribute->type_attribute,
                        ] : NULL,
                        "propertie_id" => $sale_detail->product_variation->propertie_id,
                        "propertie" => $sale_detail->product_variation->propertie ? [
                            "name" => $sale_detail->product_variation->propertie->name,
                            "code" => $sale_detail->product_variation->propertie->code,
                        ] : NULL,
                        "value_add" => $sale_detail->product_variation->value_add,
                        "variation_father" => $sale_detail->product_variation->variation_father ? 
                            [
                                "id" => $sale_detail->product_variation->variation_father->id,
                                "attribute_id" => $sale_detail->product_variation->variation_father->attribute_id,
                                "attribute" => $sale_detail->product_variation->variation_father->attribute ? [
                                    "name" => $sale_detail->product_variation->variation_father->attribute->name,
                                    "type_attribute" => $sale_detail->product_variation->variation_father->attribute->type_attribute,
                                ] : NULL,
                                "propertie_id" => $sale_detail->product_variation->variation_father->propertie_id,
                                "propertie" => $sale_detail->product_variation->variation_father->propertie ? [
                                    "name" => $sale_detail->product_variation->variation_father->propertie->name,
                                    "code" => $sale_detail->product_variation->variation_father->propertie->code,
                                ] : NULL,
                                "value_add" => $sale_detail->product_variation->variation_father->value_add,
                            ]
                        : NULL,
                    ] : NULL,
                    "quantity" => $sale_detail->quantity,
                    "price_unit" => $sale_detail->price_unit,
                    "subtotal" => $sale_detail->subtotal,
                    "total" => $sale_detail->total,
                    "currency" => $sale_detail->currency,
                    "created_at" => $sale_detail->created_at->format("Y-m-d h:i A"),
                    "review" => $sale_detail->review,
                ];
            }),
            "sale_address" => $this->resource->sale_addres,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i A"),
        ];
    }
}
