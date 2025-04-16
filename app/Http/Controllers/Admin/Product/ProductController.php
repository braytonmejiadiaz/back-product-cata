<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Product\Brand;
use App\Models\Product\Product;
use App\Models\Product\Categorie;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductImage;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductCollection;
use App\Models\Plan;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        $search = $request->search;
        $categorie_first_id = $request->categorie_first_id;
        $categorie_second_id = $request->categorie_second_id;
        $categorie_third_id = $request->categorie_third_id;
        $brand_id = $request->brand_id;

        $products = Product::where('user_id', $userId)
            ->filterAdvanceProduct($search, $categorie_first_id, $categorie_second_id, $categorie_third_id, $brand_id)
            ->orderBy("id", "desc")
            ->paginate(25);

        return response()->json([
            "total" => $products->total(),
            "products" => ProductCollection::make($products),
            "product_limit" => $user->plan->product_limit,
            "current_products" => $user->products()->count()
        ]);
    }

    /**
     * Obtiene la configuración necesaria para el formulario de productos.
     */
    public function config()
    {
        $userId = auth()->id();

        $categories_first = Categorie::where("state", 1)
            ->where("categorie_second_id", NULL)
            ->where("categorie_third_id", NULL)
            ->where("user_id", $userId)
            ->get();

        $categories_seconds = Categorie::where("state", 1)
            ->where("categorie_second_id", "<>", NULL)
            ->where("categorie_third_id", NULL)
            ->where("user_id", $userId)
            ->get();

        $categories_thirds = Categorie::where("state", 1)
            ->where("categorie_second_id", "<>", NULL)
            ->where("categorie_third_id", "<>", NULL)
            ->where("user_id", $userId)
            ->get();

        $brands = Brand::where("state", 1)
            ->where("user_id", $userId)
            ->get();

        return response()->json([
            "categories_first" => $categories_first,
            "categories_seconds" => $categories_seconds,
            "categories_thirds" => $categories_thirds,
            "brands" => $brands,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $plan = $user->plan;

        // Verificar límite de productos
        if ($plan->product_limit !== null &&
            $user->products()->count() >= $plan->product_limit) {
            return response()->json([
                "message" => 403,
                "message_text" => "Has alcanzado el límite de productos para tu plan. Máximo permitido: " . $plan->product_limit
            ]);
        }

        // Verificar título duplicado
        $isValid = Product::where("user_id", $user->id)
                          ->where("title", $request->title)
                          ->first();

        if ($isValid) {
            return response()->json([
                "message" => 403,
                "message_text" => "Ya existe un producto con este título."
            ]);
        }

        // Procesar imagen portada
        if ($request->hasFile("portada")) {
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }

        // Crear producto
        $request->request->add([
            "slug" => Str::slug($request->title),
            "tags" => $request->multiselect,
            "user_id" => $user->id
        ]);

        $product = Product::create($request->all());

        return response()->json([
            "message" => 200,
            "product" => ProductResource::make($product)
        ]);
    }

    /**
     * Agrega una imagen a un producto.
     */
    public function imagens(Request $request)
    {
        $product_id = $request->product_id;

        // Verificar propiedad del producto
        $product = Product::where('id', $product_id)
                          ->where('user_id', auth()->id())
                          ->firstOrFail();

        // Procesar imagen
        if ($request->hasFile("imagen_add")) {
            $path = Storage::putFile("products", $request->file("imagen_add"));
        } else {
            return response()->json([
                "message" => 400,
                "message_text" => "No se ha subido ninguna imagen"
            ], 400);
        }

        $product_imagen = ProductImage::create([
            "imagen" => $path,
            "product_id" => $product_id,
        ]);

        return response()->json([
            "message" => 200,
            "imagen" => [
                "id" => $product_imagen->id,
                "imagen" => env("APP_URL") . "storage/" . $product_imagen->imagen,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            "message" => 200,
            "product" => ProductResource::make($product)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::where('id', $id)
                          ->where('user_id', auth()->id())
                          ->firstOrFail();

        // Verificar título duplicado
        $isValid = Product::where("user_id", auth()->id())
                          ->where("id", "<>", $id)
                          ->where("title", $request->title)
                          ->first();

        if ($isValid) {
            return response()->json([
                "message" => 403,
                "message_text" => "Ya existe un producto con este título."
            ]);
        }

        // Procesar imagen portada
        if ($request->hasFile("portada")) {
            if ($product->imagen) {
                Storage::delete($product->imagen);
            }
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }

        // Actualizar producto
        $request->request->add([
            "slug" => Str::slug($request->title),
            "tags" => $request->multiselect
        ]);

        $product->update($request->all());

        return response()->json([
            "message" => 200,
            "product" => ProductResource::make($product)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Eliminar imagen principal
        if ($product->imagen) {
            Storage::delete($product->imagen);
        }

        // Eliminar imágenes secundarias
        foreach ($product->images as $image) {
            Storage::delete($image->imagen);
            $image->delete();
        }

        $product->delete();

        return response()->json([
            "message" => 200
        ]);
    }

    /**
     * Elimina una imagen de un producto.
     */
    public function delete_imagen(string $id)
    {
        $productImage = ProductImage::findOrFail($id);

        // Verificar propiedad del producto
        $product = Product::where('id', $productImage->product_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Eliminar imagen física
        if ($productImage->imagen) {
            Storage::delete($productImage->imagen);
        }

        $productImage->delete();

        return response()->json([
            "message" => 200
        ]);
    }


// En App\Http\Controllers\Admin\Product\ProductController.php
public function limits(Request $request)
{
    $user = auth()->user();

    // Verificar si el usuario está autenticado
    if (!$user) {
        return response()->json([
            'message' => 'Usuario no autenticado',
            'product_limit' => 0,
            'current_products' => 0
        ], 401);
    }

    // Verificar si el usuario tiene plan asignado
    if (!$user->plan) {
        return response()->json([
            'message' => 'El usuario no tiene un plan asignado',
            'product_limit' => 0,
            'current_products' => $user->products()->count()
        ], 200);
    }

    return response()->json([
        "product_limit" => $user->plan->product_limit ?? 0,
        "current_products" => $user->products()->count()
    ]);
}

}
