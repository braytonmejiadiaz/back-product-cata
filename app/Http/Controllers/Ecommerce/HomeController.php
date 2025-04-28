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
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceResource;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceCollection;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Obtiene la URL completa para una imagen
     *
     * @param string|null $path
     * @return string|null
     */
    private function getFullImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Si ya es una URL completa, no hacer nada
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Si es una cadena base64 (empieza con data:image), devolverla tal cual
        if (strpos($path, 'data:image') === 0) {
            return $path;
        }

        // Si comienza con storage/, usar Storage::url
        if (strpos($path, 'storage/') === 0) {
            return Storage::url($path);
        }

        // Si es una ruta relativa, construir la URL completa
        return config('app.url') . '/storage/' . ltrim($path, '/');
    }

    /**
     * Obtiene el usuario actual basado en el dominio o slug
     */
    private function getCurrentUser($slug = null)
    {
        // Si es un dominio personalizado, obtener el usuario del request
        if (request()->is_custom_domain ?? false) {
            $user = request()->current_store_owner;

            // Verificar que el slug coincida (si se proporciona)
            if ($slug && $user->slug !== $slug) {
                abort(404, 'Tienda no encontrada');
            }

            return $user;
        }

        // Buscar usuario por slug (acceso normal)
        $user = User::where('slug', $slug)->first();

        if (!$user) {
            abort(404, 'Tienda no encontrada');
        }

        return $user;
    }

    public function getUserDataBySlug(string $slug)
    {
        $user = $this->getCurrentUser($slug);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'phone' => $user->phone,
            'uniqd' => $user->uniqd,
            'avatar' => $this->getFullImageUrl($user->avatar),
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
            'popup' => $this->getFullImageUrl($user->popup),
            'menu_color' => $user->menu_color,
            'button_color' => $user->button_color,
            'mision' => $user->mision,
            'vision' => $user->vision,
            'button_radio' => $user->button_radio,
            'selected_font' => $user->selected_font,
            'bg_color' => $user->bg_color,
            'is_custom_domain' => request()->is_custom_domain ?? false
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
        $user = $this->getCurrentUser($slug);

        $productos = Product::where('user_id', $user->id)->where('state', 2)->get();
        $sliders = $this->getSlidersByUserId($user->id);
        $categories = $this->getCategoriesByUserId($user->id);

        return response()->json([
            'user' => [
                'name' => $user->name,
                'store_name' => $user->store_name,
                'avatar' => $this->getFullImageUrl($user->avatar),
                'bio' => $user->bio,
                'mision' => $user->mision,
                'vision' => $user->vision,
                'slug' => $user->slug,
                'selected_font' => $user->selected_font,
                'is_custom_domain' => request()->is_custom_domain ?? false
            ],
            'productos' => ProductEcommerceCollection::make($productos),
            'sliders' => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $this->getFullImageUrl($slider->imagen),
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
                    "imagen" => $this->getFullImageUrl($categorie->imagen),
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
                'avatar' => $this->getFullImageUrl($user->avatar),
                'bio' => $user->bio,
                'is_custom_domain' => request()->is_custom_domain ?? false
            ],
            'productos' => ProductEcommerceCollection::make($productos),
            'sliders' => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $this->getFullImageUrl($slider->imagen),
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
                    "imagen" => $this->getFullImageUrl($categorie->imagen),
                ];
            }),
        ]);
    }

    public function getProductsByUserSlug($slug)
    {
        $user = $this->getCurrentUser($slug);

        $productos = Product::where('user_id', $user->id)->where('state', 2)->get();
        $sliders = $this->getSlidersByUserId($user->id);
        $categories = $this->getCategoriesByUserId($user->id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'store_name' => $user->store_name,
                'avatar' => $this->getFullImageUrl($user->avatar),
                'bio' => $user->bio,
                'is_custom_domain' => request()->is_custom_domain ?? false
            ],
            'productos' => ProductEcommerceCollection::make($productos),
            'sliders' => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $this->getFullImageUrl($slider->imagen),
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
                    "imagen" => $this->getFullImageUrl($categorie->imagen),
                ];
            }),
        ]);
    }

    public function getCategoriesByUserSlug($slug)
    {
        $user = $this->getCurrentUser($slug);

        $categories = $this->getCategoriesByUserId($user->id);

        return response()->json($categories->map(function ($categorie) {
            return [
                "id" => $categorie->id,
                "name" => $categorie->name,
                "products_count" => $categorie->product_categorie_firsts_count,
                "imagen" => $this->getFullImageUrl($categorie->imagen),
            ];
        }));
    }

    public function getSlidersByUserSlug($slug)
    {
        $user = $this->getCurrentUser($slug);

        $sliders = Slider::where("state", 1)
            ->where("user_id", $user->id)
            ->orderBy("id", "desc")
            ->get()
            ->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "imagen" => $this->getFullImageUrl($slider->imagen),
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

        // Verificar si es dominio personalizado y el producto pertenece al usuario
        if (request()->is_custom_domain ?? false) {
            $user = request()->current_store_owner;
            if ($product->user_id !== $user->id) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }
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
                'imagen' => $this->getFullImageUrl($product->imagen),
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
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'imagen' => $this->getFullImageUrl($image->imagen),
                    ];
                }),
                'variations' => $variations,
            ],
            'is_custom_domain' => request()->is_custom_domain ?? false
        ]);
    }

    public function getUserPaymentMethods($slug)
    {
        try {
            $user = User::where('slug', $slug)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener SOLO los métodos asignados al usuario
            $userMethods = $user->paymentMethods()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['payment_methods.id', 'name', 'description']);

            // Obtener el método por defecto del usuario
            $defaultMethod = $user->paymentMethods()
                ->wherePivot('is_default', true)
                ->value('payment_methods.id');

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => $userMethods, // Solo métodos asignados al usuario
                    'selected' => $defaultMethod ?: ($userMethods->first() ? $userMethods->first()->id : null)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Error en getUserPaymentMethods: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener métodos de pago',
                'details' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}
