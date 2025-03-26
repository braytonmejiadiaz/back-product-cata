<?php

namespace App\Http\Controllers\Ecommerce;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Slider;
use App\Models\Sale\Review;
use Illuminate\Http\Request;
use App\Models\Product\Brand;
use App\Models\Product\Product;
use App\Models\Discount\Discount;
use App\Models\Product\Categorie;
use App\Models\Product\Propertie;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceResource;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceCollection;

class HomeController extends Controller
{
    public function getUserDataBySlug(string $slug)
    {
        $user = User::where('slug', $slug)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'phone' => $user->phone,
            'uniqd' => $user->uniqd,
            'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
            'fb' => $user->fb,
            'ins' => $user->ins,
            'tikTok' => $user->tikTok,
            'youtube' => $user->youtube,
            'address' => $user->address,
            'description' => $user->description,
            'sexo' => $user->sexo,
            'email' => $user->email,
            'type_user' => $user->type_user,
            'code_verified' => $user->code_verified,
            'email_verified_at' => $user->email_verified_at,
            'remember_token' => $user->remember_token,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'deleted_at' => $user->deleted_at,
            'store_name' => $user->store_name,
            'slug' => $user->slug,
            'popup' => $user->popup,
            'menu_color' => $user->menu_color,
            'button_color' => $user->button_color,
            'mision' => $user->mision,
            'vision' => $user->vision,
        ]);
    }

    private function getSlidersByUserId($user_id)
    {
        return Slider::where("state", 1)
            ->where("user_id", $user_id)
            ->orderBy("id", "desc")
            ->get();
    }

    private function getCategoriesByUserId($user_id)
    {
        return Categorie::withCount(["product_categorie_firsts"])
            ->where("user_id", $user_id)
            ->where("categorie_second_id", NULL)
            ->where("categorie_third_id", NULL)
            ->inRandomOrder()
            ->limit(5)
            ->get();
    }

    public function mostrarTiendaUsuario($slug)
    {
        $user = User::where('slug', $slug)->first();

        if (!$user) {
            return response()->json(['error' => 'Tienda no encontrada'], 404);
        }

        $productos = Product::where('user_id', $user->id)->where('state', 2)->get();
        $sliders = $this->getSlidersByUserId($user->id);
        $categories = $this->getCategoriesByUserId($user->id);

        return response()->json([
            'user' => [
                'name' => $user->name,
                'store_name' => $user->store_name,
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                'bio' => $user->bio,
                'mision' => $user->mision,
                'vision' => $user->vision,
            ],
            'productos' => ProductEcommerceCollection::make($productos),
            'sliders' => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? Storage::url($slider->imagen) : null,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
            'categories' => $categories->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "products_count" => $categorie->product_categorie_firsts_count,
                    "imagen" => $categorie->imagen ? Storage::url($categorie->imagen) : null,
                ];
            }),
        ]);
    }

    public function getProductsByUserId($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $productos = Product::where('user_id', $user->id)->where('state', 2)->get();
        $sliders = $this->getSlidersByUserId($user->id);
        $categories = $this->getCategoriesByUserId($user->id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'store_name' => $user->store_name,
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                'bio' => $user->bio,
            ],
            'productos' => ProductEcommerceCollection::make($productos),
            'sliders' => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? Storage::url($slider->imagen) : null,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
            'categories' => $categories->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "products_count" => $categorie->product_categorie_firsts_count,
                    "imagen" => $categorie->imagen ? Storage::url($categorie->imagen) : null,
                ];
            }),
        ]);
    }

    public function getProductsByUserSlug($slug)
    {
        $user = User::where('slug', $slug)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $productos = Product::where('user_id', $user->id)->where('state', 2)->get();
        $sliders = $this->getSlidersByUserId($user->id);
        $categories = $this->getCategoriesByUserId($user->id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'store_name' => $user->store_name,
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                'bio' => $user->bio,
            ],
            'productos' => ProductEcommerceCollection::make($productos),
            'sliders' => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? Storage::url($slider->imagen) : null,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
            'categories' => $categories->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "products_count" => $categorie->product_categorie_firsts_count,
                    "imagen" => $categorie->imagen ? Storage::url($categorie->imagen) : null,
                ];
            }),
        ]);
    }

    public function getCategoriesByUserSlug($slug)
    {
        $user = User::where('slug', $slug)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $categories = $this->getCategoriesByUserId($user->id);

        return response()->json($categories->map(function ($categorie) {
            return [
                "id" => $categorie->id,
                "name" => $categorie->name,
                "products_count" => $categorie->product_categorie_firsts_count,
                "imagen" => $categorie->imagen ? Storage::url($categorie->imagen) : null,
            ];
        }));
    }

    public function getSlidersByUserSlug($slug)
    {
        $user = User::where('slug', $slug)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $sliders = Slider::where("state", 1)
            ->where("user_id", $user->id)
            ->orderBy("id", "desc")
            ->get()
            ->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "imagen" => $slider->imagen ? Storage::url($slider->imagen) : null,
                ];
            });

        return response()->json($sliders);
    }

    public function getProductById($productId)
    {
        $product = Product::with([
            'brand',
            'categorie_first',
            'categorie_second',
            'categorie_third',
            'images',
            'product_variations',
            'product_variations.propertie',
            'product_variations.attribute'
        ])->find($productId);

        if (!$product) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $variations = [];
        if ($product->product_variations->isNotEmpty()) {
            $variations = $product->product_variations->map(function ($variation) {
                return [
                    'id' => $variation->id,
                    'attribute' => $variation->attribute ? [
                        'id' => $variation->attribute->id,
                        'name' => $variation->attribute->name,
                    ] : null,
                    'propertie' => $variation->propertie ? [
                        'id' => $variation->propertie->id,
                        'name' => $variation->propertie->name,
                        'code' => $variation->propertie->code,
                    ] : null,
                    'value_add' => $variation->value_add,
                    'add_price' => $variation->add_price,
                    'stock' => $variation->stock,
                ];
            });
        }

        return response()->json([
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'description' => $product->description,
                'price_cop' => $product->price_cop,
                'tags' => $product->tags,
                'imagen' => $product->imagen ? Storage::url($product->imagen) : null,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'categorie_first' => $product->categorie_first ? [
                    'id' => $product->categorie_first->id,
                    'name' => $product->categorie_first->name,
                ] : null,
                'categorie_second' => $product->categorie_second ? [
                    'id' => $product->categorie_second->id,
                    'name' => $product->categorie_second->name,
                ] : null,
                'categorie_third' => $product->categorie_third ? [
                    'id' => $product->categorie_third->id,
                    'name' => $product->categorie_third->name,
                ] : null,
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'imagen' => $image->imagen ? Storage::url($image->imagen) : null,
                    ];
                }),
                'variations' => $variations,
            ],
        ]);
    }
}
