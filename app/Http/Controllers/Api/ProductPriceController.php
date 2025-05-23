<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductPrice;
use App\Models\ProductPriceType;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function index()
    {
        $productPrices = ProductPrice::with(['product', 'priceType', 'uom', 'size'])
            ->latest()
            ->paginate(10);
        return response()->json($productPrices);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:mst_product,id',
            'effective_date' => 'required|date',
            'cost_price' => 'nullable|numeric|min:0',
            'price_types' => 'required|array|min:1',
            'price_types.*.price_type_id' => 'required|exists:mst_price_type,id',
            'price_types.*.price' => 'required|numeric|min:0',
            'price_types.*.uom_id' => 'nullable|exists:mst_uom,id',
            'price_types.*.size_id' => 'nullable|exists:mst_size,id',
        ]);
    
        $result = [];
    
        $validated = $request->validate([
            'product_id' => 'required|exists:mst_product,id',
            'cost_price' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
            'price_types' => 'required|array|min:1',
            'price_types.*.price_type_id' => 'required|exists:mst_price_type,id',
            'price_types.*.price' => 'required|numeric|min:0',
            'price_types.*.uom_id' => 'nullable|exists:mst_uom,id',
            'price_types.*.size_id' => 'nullable|exists:mst_size,id',
        ]);
    
        // 1. Simpan ProductPrice (master)
        $productPrice = ProductPrice::create([
            'product_id' => $validated['product_id'],
            'cost_price' => $validated['cost_price'],
            'effective_date' => $validated['effective_date'],
        ]);
    
        // 2. Simpan detail harga (ProductPriceType)
        foreach ($validated['price_types'] as $type) {
            ProductPriceType::create([
                'price_id' => $productPrice->id, // relasi foreign key
                'price_type_id' => $type['price_type_id'],
                'price' => $type['price'],
                'uom_id' => $type['uom_id'] ?? null,
                'size_id' => $type['size_id'] ?? null,
            ]);
        }
    
        return response()->json([
            'message' => 'Harga produk berhasil disimpan',
            'data' => $result
        ], 201);
    }
    

    public function show(ProductPrice $productPrice)
    {
        return response()->json($productPrice->load(['product', 'priceType', 'uom', 'size']));
    }

    public function update(Request $request, ProductPrice $productPrice)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:mst_product,id',
            'price_type_id' => 'sometimes|exists:mst_price_type,id',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'effective_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
            'qty' => 'nullable|numeric|min:0',
            'uom_id' => 'nullable|exists:mst_uom,id',
            'size_id' => 'nullable|exists:mst_size,id'
        ]);

        $productPrice->update($validated);
        return response()->json($productPrice->load(['product', 'priceType', 'uom', 'size']));
    }

    public function destroy(ProductPrice $productPrice)
    {
        $productPrice->delete();
        return response()->noContent();
    }
}