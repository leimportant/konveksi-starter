<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductPrice;
use App\Models\ProductPriceType;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductPriceController extends Controller
{
    public function index(Request $request)
    {

         $query = ProductPrice::with('product','priceTypes');

        if ($request->has('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('product_id', 'like', '%' . $search . '%')
              ->orWhereHas('product', function ($q2) use ($search) {
                  $q2->where('name', 'like', '%' . $search . '%');
              });
        });
    }

        $perPage = $request->input('perPage', 10);
        $productPrices = $query->paginate($perPage);

        return response()->json($productPrices);

    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        // Validasi input
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
            'price_types.*.discount' => 'nullable|numeric|min:0',
            'price_types.*.uom_id' => 'nullable|exists:mst_uom,id',
            'price_types.*.size_id' => 'nullable|exists:mst_size,id',
        ]);
    
        $product_id = $validated['product_id'];
        // 1. Simpan ProductPrice (master)
        $productPrice = ProductPrice::create([
            'product_id' => $product_id,
            'cost_price' => $validated['cost_price'],
            'effective_date' => $validated['effective_date'],
            'end_date' => $validated['end_date'] ?? "9999-12-31", // default end date if not provided
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    
        // 2. Simpan detail harga (ProductPriceType)
        foreach ($validated['price_types'] as $type) {

            $price_sell = floatval($type['price'] - ($type['discount'] ?? 0)); // hitung harga jual setelah diskon
            ProductPriceType::create([
                'price_id' => $productPrice->id, // relasi foreign key
                'price_type_id' => $type['price_type_id'],
                'product_id' => $product_id,
                'price' => $type['price'],
                'discount' => $type['discount'] ?? 0, // default discount if not provided
                'price_sell' => $price_sell, // default qty if not provided
                'uom_id' => $type['uom_id'] ?? null,
                'size_id' => $type['size_id'] ?? null,
            ]);
        }
    
        DB::commit();
        // Load relasi yang diperlukan
        return response()->json([
            'message' => 'Harga produk berhasil disimpan',
            'data' => $result
        ], 201);
    }
    

    public function show(ProductPrice $productPrice)
    {
        return response()->json($productPrice->load(['product', 'priceTypes']));
    }

    public function update(Request $request, ProductPrice $productPrice)
    {
        DB::beginTransaction();
        // Validasi input   
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:mst_product,id',
            'price_type_id' => 'sometimes|exists:mst_price_type,id',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'effective_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
            'price_types' => 'required|array|min:1',
            'price_types.*.price_type_id' => 'required|exists:mst_price_type,id',
            'price_types.*.price' => 'required|numeric|min:0',
            'price_types.*.discount' => 'nullable|numeric|min:0',
            'price_types.*.uom_id' => 'nullable|exists:mst_uom,id',
            'price_types.*.size_id' => 'nullable|exists:mst_size,id',
        ]);

        $productPrice->updated_by = Auth::id();

        $productPrice->update($validated);

        // Hapus semua relasi lama
        ProductPriceType::where('price_id', $productPrice->id)->delete();
        // Simpan relasi baru
         foreach ($validated['price_types'] as $type) {
            ProductPriceType::create([
                'price_id' => $productPrice->id, // relasi foreign key
                'price_type_id' => $type['price_type_id'],
                'product_id' => $validated['product_id'],
                'price' => $type['price'],
                'discount' => $type['discount'] ?? 0, // default discount if not provided
                'price_sell' => floatval($type['price'] - ($type['discount'] ?? 0)), // hitung harga jual setelah diskon
                'uom_id' => $type['uom_id'] ?? null,
                'size_id' => $type['size_id'] ?? null,
            ]);
        }
        
        DB::commit();
        return response()->json($productPrice->load(['product', 'priceTypes']));
       
    }

    public function destroy(ProductPrice $productPrice)
    {
        $productPrice->delete();
        return response()->noContent();
    }
}