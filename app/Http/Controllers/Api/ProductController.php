<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function productsBySearch(Request $request)
    {
        $search = $request->input('search');
        $query = Product::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('descriptions', 'like', '%' . $search . '%')
            ;
        }

        $products = $query->with(['category', 'uom', 'galleryImages'])
            ->orderBy('name')
            ->paginate();

        // Attach size IDs to each product
        foreach ($products as $data) {
            $products->sizes = $this->getProductPriceSizeIds($data->id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function index(Request $request)
    {
        $query = Product::whereNotIn('category_id', [0])
            ->with(['category', 'uom', 'galleryImages']);

        $search = $request->input('search');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('descriptions', 'like', '%' . $search . '%')
                ->orWhereHas('category', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('uom', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        }

        $perPage = $request->input('perPage', 50);
        $data = $query->paginate($perPage);
        // Attach size IDs to each product
        foreach ($data as $product) {
            $product->sizes = $this->getProductPriceSizeIds($product->id);
        }

        return response()->json($data);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:mst_category,id',
            'uom_id' => 'exists:mst_uom,id',
            'descriptions' => 'nullable|string',
            'name' => 'required|max:255|unique:mst_product,name',
            'doc_id' => 'nullable|string|exists:tr_document_attachment,doc_id'
        ]);
        $newId = $this->generateNumber($validated['category_id']);
        $validated['id'] = $newId;

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    private function generateNumber(int $categoryId): string
    {
        // Panjang digit category_id (misalnya: 2 untuk '12')
        $categoryIdStr = (string) $categoryId;
        $prefixLength = strlen($categoryIdStr);

        // Ambil ID terakhir dari produk dalam kategori itu
        $lastProductId = DB::table('mst_product')
            ->where('category_id', $categoryId)
            ->where('id', 'like', $categoryIdStr . '%')
            ->orderByDesc('id')
            ->value('id');

        // Ambil 4 digit terakhir (setelah prefix category_id)
        $lastNumber = 0;
        if ($lastProductId) {
            $lastNumber = (int) substr($lastProductId, $prefixLength);
        }

        $newNumber = $lastNumber + 1;

        // Gabungkan: category_id + new number (formatted)
        return $categoryIdStr . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255', Rule::unique('mst_product')->ignore($product->id)],
            'category_id' => 'required|exists:mst_category,id',
            'descriptions' => 'nullable|string',
            'uom_id' => 'exists:mst_uom,id',
            'doc_id' => 'nullable|string|exists:tr_document_attachment,doc_id',
        ]);

        $product->update($validated);
        return response()->json($product);
    }

    public function show(Product $product)
    {
        $product->product_name = $product->name;
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->deleted_by = Auth::id();
        $product->save();
        $product->delete();
        return response()->json(null, 204);
    }

    public function getProductPriceSizeIds($productId)
    {
        return DB::table('mst_product_price AS a')
            ->join('mst_product_price_type AS b', 'a.id', '=', 'b.price_id')
            ->where('a.product_id', $productId)
            ->groupBy('b.size_id')
            ->pluck('b.size_id');
    }

    public function getProductWithSizeIds(Request $request)
    {
        $productId = $request->input('productId'); // Ambil productId dari query param
        $search = $request->input('search');

        $query = DB::table('mst_product AS a')
            ->select(
                'a.id',
                'a.id as product_id',
                'a.name as product_name',
                DB::raw("CONCAT(a.name, ' - Uk: ', c.id) as product_title"),
                'a.uom_id',
                'c.id as size_id',
                'c.name as size_name'
            )
            ->join('mst_product_price_type AS b', 'a.id', '=', 'b.product_id')
            ->join('mst_size AS c', 'b.size_id', '=', 'c.id');

        if ($productId) {
            $query->where('a.id', $productId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('a.name', 'like', '%' . $search . '%')
                    ->orWhere('a.descriptions', 'like', '%' . $search . '%');
            });
        }

        $query->groupBy('a.id', 'a.name', 'a.uom_id', 'c.id', 'c.name');

        $data = $query->paginate(10);

        return response()->json($data);
    }



}