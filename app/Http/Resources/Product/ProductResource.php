<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            "title" => $this->resource->title,
            "slug"  => $this->resource->slug,
            "sku" => $this->resource->sku,
            "price_cop"  => $this->resource->price_cop,
            "resumen"  => $this->resource->resumen,
            "imagen"  => env("APP_URL")."storage/".$this->resource->imagen,
            "state"  => $this->resource->state,
            "description"  => $this->resource->description,
            "tags"  => $this->resource->tags ? json_decode($this->resource->tags) : [],
            "brand_id"  => $this->resource->brand_id,
            "brand" => $this->resource->brand ? [
                "id" => $this->resource->brand->id,
                "name" => $this->resource->brand->name,
            ]: NULL,
            "categorie_first_id"  => $this->resource->categorie_first_id,
            "categorie_first"  => $this->resource->categorie_first ? [
                "id" => $this->resource->categorie_first->id,
                "name" => $this->resource->categorie_first->name,
            ] : NULL,
            "categorie_second_id"  => $this->resource->categorie_second_id,
            "categorie_second"  => $this->resource->categorie_second ? [
                "id" => $this->resource->categorie_second->id,
                "name" => $this->resource->categorie_second->name,
            ] : NULL,
            "categorie_third_id"  => $this->resource->categorie_third_id,
            "categorie_third"  => $this->resource->categorie_third ? [
                "id" => $this->resource->categorie_third->id,
                "name" => $this->resource->categorie_third->name,
            ] : NULL,
            "stock" => $this->resource->stock,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i:s"),
            "images" => $this->resource->images->map(function($image) {
                return [
                    "id" => $image->id,
                    "imagen" => env("APP_URL")."storage/".$image->imagen,
                ];
            })
        ];
    }
}
