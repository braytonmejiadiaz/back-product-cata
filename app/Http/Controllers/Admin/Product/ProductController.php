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

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $search = $request->search;
        $categorie_first_id = $request->categorie_first_id;
        $categorie_second_id = $request->categorie_second_id;
        $categorie_third_id = $request->categorie_third_id;
        $brand_id = $request->brand_id;

        // Filtra los productos por el user_id del usuario autenticado
        $products = Product::where('user_id', $userId)
            ->filterAdvanceProduct($search, $categorie_first_id, $categorie_second_id, $categorie_third_id, $brand_id)
            ->orderBy("id", "desc")
            ->paginate(25);

        return response()->json([
            "total" => $products->total(),
            "products" => ProductCollection::make($products),
        ]);
    }

    /**
     * Obtiene la configuración necesaria para el formulario de productos.
     */
    public function config()
    {
        $userId = auth()->id(); // Obtén el ID del usuario autenticado

        // Filtra las categorías y marcas por el user_id del usuario autenticado
        $categories_first = Categorie::where("state", 1)
            ->where("categorie_second_id", NULL)
            ->where("categorie_third_id", NULL)
            ->where("user_id", $userId) // Filtra por user_id
            ->get();

        $categories_seconds = Categorie::where("state", 1)
            ->where("categorie_second_id", "<>", NULL)
            ->where("categorie_third_id", NULL)
            ->where("user_id", $userId) // Filtra por user_id
            ->get();

        $categories_thirds = Categorie::where("state", 1)
            ->where("categorie_second_id", "<>", NULL)
            ->where("categorie_third_id", "<>", NULL)
            ->where("user_id", $userId) // Filtra por user_id
            ->get();

        $brands = Brand::where("state", 1)
            ->where("user_id", $userId) // Filtra por user_id
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
        // Verifica si ya existe un producto con el mismo título para el usuario autenticado
        $isValid = Product::where("user_id", auth()->id())
                          ->where("title", $request->title)
                          ->first();

        if ($isValid) {
            return response()->json(["message" => 403, "message_text" => "Ya existe un producto con este título."]);
        }

        if ($request->hasFile("portada")) {
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);
        $request->request->add(["user_id" => auth()->id()]); // Asigna el user_id del usuario autenticado

        $product = Product::create($request->all());

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Agrega una imagen a un producto.
     */
    public function imagens(Request $request)
    {
        $product_id = $request->product_id;

        // Verifica que el producto al que se le está agregando la imagen sea del usuario autenticado
        $product = Product::where('id', $product_id)
                          ->where('user_id', auth()->id())
                          ->firstOrFail();

        if ($request->hasFile("imagen_add")) {
            $path = Storage::putFile("products", $request->file("imagen_add"));
        }

        $product_imagen = ProductImage::create([
            "imagen" => $path,
            "product_id" => $product_id,
        ]);

        return response()->json([
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

        return response()->json(["product" => ProductResource::make($product)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::where('id', $id)
                          ->where('user_id', auth()->id())
                          ->firstOrFail();

        // Verifica si ya existe un producto con el mismo título para el usuario autenticado, excluyendo el producto actual
        $isValid = Product::where("user_id", auth()->id())
                          ->where("id", "<>", $id)
                          ->where("title", $request->title)
                          ->first();

        if ($isValid) {
            return response()->json(["message" => 403, "message_text" => "Ya existe un producto con este título."]);
        }

        if ($request->hasFile("portada")) {
            if ($product->imagen) {
                Storage::delete($product->imagen);
            }
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);
        $product->update($request->all());

        return response()->json([
            "message" => 200,
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

        $product->delete();

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Elimina una imagen de un producto.
     */
    public function delete_imagen(string $id)
    {
        $productImage = ProductImage::findOrFail($id);

        // Verifica que el producto al que pertenece la imagen sea del usuario autenticado
        $product = Product::where('id', $productImage->product_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($productImage->imagen) {
            Storage::delete($productImage->imagen);
        }
        $productImage->delete();

        return response()->json([
            "message" => 200
        ]);
    }
}
