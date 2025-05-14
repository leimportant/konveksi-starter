<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductPrice;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function index()
    {
        
        $product = ProductPrice::with(['product', 'priceType'])->latest()->paginate(10);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'price_type_id' => 'required|exists:mst_price_type,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
            'is_active' => 'boolean'
        ]);

        return ProductPrice::create($validated);
    }

    public function show(ProductPrice $productPrice)
    {
        return response()->json($productPrice->load(['product', 'priceType']));
    }

    public function update(Request $request, ProductPrice $productPrice)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'price_type_id' => 'sometimes|exists:mst_price_type,id',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'effective_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean'
        ]);

        $productPrice->update($validated);
        return $productPrice;
    }

    public function destroy(ProductPrice $productPrice)
    {
        $productPrice->delete();
        return response()->noContent();
    }
}