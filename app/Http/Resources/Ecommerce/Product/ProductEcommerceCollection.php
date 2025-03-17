<?php

namespace App\Http\Resources\Ecommerce\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductEcommerceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "data" => ProductEcommerceResource::collection($this->collection),
        ];
    }
}
