<?php

namespace App\Http\Resources\Ecommerce\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartEcommerceResource extends JsonResource
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
            "product_id" => $this->resource->product_id,
            "product" => [
                "id" => $this->resource->product->id,
                "title" => $this->resource->product->title,
                "slug"  => $this->resource->product->slug,
                "price_cop"  => $this->resource->product->price_cop,
                "imagen"  => env("APP_URL")."storage/".$this->resource->product->imagen,
                "brand_id"  => $this->resource->product->brand_id,
                "brand" => $this->resource->product->brand ? [
                    "id" => $this->resource->product->brand->id,
                    "name" => $this->resource->product->brand->name,
                ]: NULL,
            ],
            "type_discount" => $this->resource->type_discount,
            "discount" => $this->resource->discount,
            "type_campaing" => $this->resource->type_campaing,
            "code_cupon" => $this->resource->code_cupon,
            "code_discount" => $this->resource->code_discount,
            "product_variation_id" => $this->resource->product_variation_id,
            "product_variation" => $this->resource->product_variation ? [
                "id" => $this->resource->product_variation->id,
                "attribute_id" => $this->resource->product_variation->attribute_id,
                "attribute" => $this->resource->product_variation->attribute ? [
                    "name" => $this->resource->product_variation->attribute->name,
                    "type_attribute" => $this->resource->product_variation->attribute->type_attribute,
                ] : NULL,
                "propertie_id" => $this->resource->product_variation->propertie_id,
                "propertie" => $this->resource->product_variation->propertie ? [
                    "name" => $this->resource->product_variation->propertie->name,
                    "code" => $this->resource->product_variation->propertie->code,
                ] : NULL,
                "value_add" => $this->resource->product_variation->value_add,
                "variation_father" => $this->resource->product_variation->variation_father ?
                    [
                        "id" => $this->resource->product_variation->variation_father->id,
                        "attribute_id" => $this->resource->product_variation->variation_father->attribute_id,
                        "attribute" => $this->resource->product_variation->variation_father->attribute ? [
                            "name" => $this->resource->product_variation->variation_father->attribute->name,
                            "type_attribute" => $this->resource->product_variation->variation_father->attribute->type_attribute,
                        ] : NULL,
                        "propertie_id" => $this->resource->product_variation->variation_father->propertie_id,
                        "propertie" => $this->resource->product_variation->variation_father->propertie ? [
                            "name" => $this->resource->product_variation->variation_father->propertie->name,
                            "code" => $this->resource->product_variation->variation_father->propertie->code,
                        ] : NULL,
                        "value_add" => $this->resource->product_variation->variation_father->value_add,
                    ]
                : NULL,
            ] : NULL,
            "quantity" => $this->resource->quantity,
            "price_unit" => $this->resource->price_unit,
            "subtotal" => $this->resource->subtotal,
            "total" => $this->resource->total,
            "currency" => $this->resource->currency,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i A"),
        ];
    }
}
