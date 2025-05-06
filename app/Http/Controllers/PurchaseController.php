<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'metodo_pago' => 'nullable',
            'comentario' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $purchase = Purchase::create([
                'user_id' => $request->user_id,
                'total_price' => $request->total_price,
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'ciudad' => $request->ciudad,
                'telefono' => $request->telefono,
                'metodo_pago' => $request->metodo_pago,
                'comentario' => $request->comentario,
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $product->price_cop,
                    'total_price' => $product->price_cop * ($item['quantity'] ?? 1),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Compra registrada con éxito',
                'data' => $purchase->load('items.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $user = auth()->user();

        $purchases = Purchase::with(['items.product', 'user'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $purchases
        ]);
    }
}
