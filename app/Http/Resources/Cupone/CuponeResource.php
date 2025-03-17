<?php

namespace App\Http\Resources\Cupone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuponeResource extends JsonResource
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
            "code" => $this->resource->code,
            "type_discount" => $this->resource->type_discount,
            "discount" => $this->resource->discount,
            "type_count" => $this->resource->type_count,
            "num_use" => $this->resource->num_use,
            "type_cupone" => $this->resource->type_cupone,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i A"),//"6 AM 6PM"
            "state" => $this->resource->state,
            "products" => $this->resource->products->map(function($product_aux) {
                return [
                    "id" => $product_aux->product->id,
                    "title" => $product_aux->product->title,
                    "imagen" => env("APP_URL")."storage/".$product_aux->product->imagen,
                    "id_aux" => $product_aux->id,
                ];
            }),
            "categories" => $this->resource->categories->map(function($categorie_aux) {
                return [
                    "id" => $categorie_aux->categorie->id,
                    "name" => $categorie_aux->categorie->name,
                    "imagen" => env("APP_URL")."storage/".$categorie_aux->categorie->imagen,
                    "id_aux" => $categorie_aux->id,
                ];
            }),
            "brands" => $this->resource->brands->map(function($brand_aux) {
                return [
                    "id" => $brand_aux->brand->id,
                    "name" => $brand_aux->brand->name,
                    "id_aux" => $brand_aux->id,
                ];
            }),
        ];
    }
}
