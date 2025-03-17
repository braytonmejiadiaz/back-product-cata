<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieResource extends JsonResource
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
            "name" => $this->resource->name,
            "icon" => $this->resource->icon,
            "imagen" => $this->resource->imagen ? env("APP_URL")."storage/".$this->resource->imagen : NULL,
            "categorie_second_id" => $this->resource->categorie_second_id,
            "categorie_second" => $this->resource->categorie_second ? [
                "name" => $this->resource->categorie_second->name,
            ] : NULL,
            "categorie_third_id" => $this->resource->categorie_third_id,
            "categorie_third" => $this->resource->categorie_third ? [
                "name" =>  $this->resource->categorie_third->name,
            ] : NULL,
            "position" => $this->resource->position,
            "type_categorie" => $this->resource->type_categorie,
            "state" => $this->resource->state,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i:s"),
        ];
    }
}
