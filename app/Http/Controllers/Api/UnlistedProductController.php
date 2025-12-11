<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnlistedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ProductService;

class UnlistedProductController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'category_id' => 'required|exists:mst_category,id',
            'size_id' => 'required|exists:mst_size,id',
            'variant' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'price_store' => 'required|numeric|min:1',
            'price_grosir' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Generate a unique ID for the unlisted product.
            // This could be based on a sequence or a UUID, depending on your system.
            // For now, let's use a simple approach, e.g., combining category_id and a timestamp/random string.

            $unlistedProduct = UnlistedProduct::create([
                'id' => UnlistedProduct::generateId(), // Use the new generateId method
                'category_id' => $validated['category_id'],
                'unlisted' => 'Y',
                'uom_id' => 'PCS', // Default UOM for unlisted products
                'name' => $validated['name'],
                'descriptions' => '', // No description for now
                'variant' => $validated['variant'],
                'size_id' => $validated['size_id'],
                'price_store' => $validated['price_store'],
                'price_grosir' => $validated['price_grosir'],
                'image_path' => null, // No image for now
            ]);

            DB::commit();
            return response()->json($unlistedProduct, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating unlisted product: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create unlisted product.'], 500);
        }
    }


    public function index(Request $request)
    {
        $query = UnlistedProduct::where('unlisted', 'Y');
    
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('variant', 'like', '%' . $search . '%');
            });
        }
    
        $perPage = $request->input('perPage', 50);
    
        return response()->json(
            $query->paginate($perPage)
        );
    }
    

    public function show(UnlistedProduct $unlistedProduct)
    {
        return response()->json($unlistedProduct);
    }

    public function update(Request $request, UnlistedProduct $unlistedProduct)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'category_id' => 'required|exists:mst_category,id',
            'size_id' => 'required|exists:mst_size,id',
            'variant' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'price_store' => 'required|numeric|min:1',
            'price_grosir' => 'required|numeric|min:1',
        ]);

        $validated['unlisted'] = 'Y';
        $unlistedProduct->update($validated);

        return response()->json($unlistedProduct);
    }

    public function destroy(UnlistedProduct $unlistedProduct)
    {
        $unlistedProduct->delete();
        return response()->json(null, 204);
    }
}
