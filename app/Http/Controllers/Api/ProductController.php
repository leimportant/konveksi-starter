<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function productsBySearch(Request $request)
    {
        $search = $request->input('search');
        $query = Product::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $products = $query->with(['category', 'uom'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function index()
    {
        $data = Product::with(['category', 'uom'])->latest()->paginate(10);
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:mst_category,id',
            'uom_id' => 'exists:mst_uom,id',
            'name' => 'required|max:255|unique:mst_product,name'
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255', Rule::unique('mst_product')->ignore($product->id)],
            'category_id' => 'required|exists:mst_category,id',
            'uom_id' => 'exists:mst_uom,id',
        ]);

        $product->update($validated);
        return response()->json($product);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->deleted_by = Auth::id();
        $product->save();
        $product->delete();
        return response()->json(null, 204);
    }
}