<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentAttachment;
use App\Models\ModelRef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;
use App\Models\ActivityRole;
use App\Models\ActivityGroup;
use App\Models\User;
use App\Models\Product;
use App\Models\Sloc;

class ModelRefController extends Controller
{

     private function generateNumber(int $categoryId): string
    {
        return DB::transaction(function () use ($categoryId) {
            $categoryIdStr = (string) $categoryId;
            $prefixLength = strlen($categoryIdStr);

            // Lock the rows for this category
            $lastProductId = DB::table('mst_product')
                ->where('category_id', $categoryId)
                ->where('id', 'like', $categoryIdStr . '%')
                ->lockForUpdate()   
                ->orderByDesc('id')
                ->value('id');

            $lastNumber = 0;
            if ($lastProductId) {
                $lastNumber = (int) substr($lastProductId, $prefixLength);
            }

            $newNumber = $lastNumber + 1;

            return $categoryIdStr . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    private function generateProductId(): string
    {
        return DB::transaction(function () {
            $lastProductId = DB::table('mst_product')
                ->orderByDesc('id')
                ->value('id');

            $lastNumber = 0;
            if ($lastProductId) {
                $lastNumber = (int) substr($lastProductId, 0, 4); // Assuming category_id is 4 digits
            }

            $newNumber = $lastNumber + 1;

            return str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
        });
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
           'description' => 'required|string|max:255',
            'category_id' => 'required|exists:mst_category,id',
            'remark' => 'nullable|string',
            'estimation_price_pcs' => 'numeric|min:0',
            'estimation_qty' => 'integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',

            'sizes' => 'nullable|array',
            'sizes.*.size_id' => 'required|exists:mst_size,id',
            'sizes.*.variant' => 'required|string|max:100',
            'sizes.*.qty' => 'required|integer|min:1',
            'sizes.*.price_store' => 'required|numeric|min:1',
            'sizes.*.price_grosir' => 'required|numeric|min:1',

            'activity' => 'nullable|array',
            'activity.*.activity_role_id' => 'nullable|exists:mst_activity_role,id',
            'activity.*.price' => 'numeric|min:0',

            'modelMaterials' => 'nullable|array',
            'modelMaterials.*.product_id' => 'nullable|exists:mst_product,id',
            'modelMaterials.*.qty' => 'nullable|numeric|min:0',
            'modelMaterials.*.price' => 'nullable|numeric|min:0',
            'modelMaterials.*.uom_id' => 'nullable|exists:mst_uom,id',
            'modelMaterials.*.remark' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

           

            $newModelRefId = $this->generateNumber($validated['category_id']);

            // Create the model ref and link to the product
            $model = ModelRef::create([
                'id' => $newModelRefId,// Link ModelRef to Product
                'description' => $validated['description'] ?? "",
                'category_id' => $validated['category_id'] ?? null,
                'remark' => $validated['remark'] ?? "",
                'estimation_price_pcs' => $validated['estimation_price_pcs'] ?? null,
                'estimation_qty' => $validated['estimation_qty'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_close' => 'N',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

             // Create Product master record first
             $product = Product::create([
                'id' => $newModelRefId,
                'category_id'  => $validated['category_id'],
                'name' => $validated['description'],
                'uom_id' => 'PCS',
                'descriptions' => $validated['description'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Store sizes
            if ($request->has('sizes')) {
                if (!empty($validated['sizes'])) {
                    foreach ($validated['sizes'] as $size) {
                        $model->sizes()->create([
                            'size_id' => $size['size_id'] ?? "",
                            'variant' => $size['variant'] ?? "",
                            'qty' => $size['qty'] ?? 1,
                            'price_store' => $size['price_store'] ?? 0,
                            'price_grosir' => $size['price_grosir'] ?? 0,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id()
                        ]);
                    }
                }
            }

            // Store activities
            if ($request->has('activity')) {
                if (!empty($validated['activity'])) {
                    foreach ($validated['activity'] as $activity) {
                        $model->activities()->create([
                            'activity_role_id' => $activity['activity_role_id'],
                            'price' => $activity['price'] ?? 0,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id()
                        ]);
                    }
                }
            }

            if ($request->has('modelMaterials')) {
                if (!empty($validated['modelMaterials'])) {
                    foreach ($validated['modelMaterials'] as $index => $modelMaterial) {
                        $productId = is_array($modelMaterial['product_id'])
                            ? $modelMaterial['product_id']['id'] ?? null
                            : $modelMaterial['product_id'];

                        $model->modelMaterial()->create([
                            'product_id' => $productId,
                            'item' => $index + 1,
                            'remark' => $modelMaterial['remark'] ?? null,
                            'qty' => $modelMaterial['qty'] ?? 1,
                            'price' => $modelMaterial['price'] ?? 0,
                            'uom_id' => $modelMaterial['uom_id'] ?? null,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id()
                        ]);
                    }
                }
            }

            if ($request->has('sizes')) {
                if (!empty($validated['sizes'])) {
                    ProductService::createProduct(
                        $product, // Pass the Product object
                        $validated['sizes'],
                        $request
                    );
                }
            }

            // Store documents
            $uniqId = $request->input('uniqId', null);
            $docs = DocumentAttachment::where('reference_id', $uniqId)
                ->where('reference_type', 'Model')
                ->get();

            if ($docs->isNotEmpty()) {
                // update doc_id dan reference_id pada dokumen yang sudah ada
                foreach ($docs as $doc) {
                    $doc->update([
                        'doc_id' => $model->id,
                        'updated_by' => Auth::id()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Model created successfully',
                'data' => $model->load(['sizes', 'activities'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {

            // Ambil semua model + sizes
            $models = ModelRef::with(['sizes', 'activities', 'modelMaterial'])
                ->latest()
                ->paginate(10);

            // Ambil semua model_id dalam pagination
            $modelIds = $models->pluck('id');

            // Ambil cutting untuk semua model dalam satu query
            $cuttingData = DB::table('tr_production as p')
                ->join('tr_production_item as pi', 'pi.production_id', '=', 'p.id')
                ->whereIn('p.model_id', $modelIds)
                ->where('p.activity_role_id', 1) // CUTTING
                ->select(
                    'p.model_id',
                    'pi.size_id',
                    'pi.variant',
                    DB::raw('SUM(pi.qty) as total_cutting')
                )
                ->groupBy('p.model_id', 'pi.size_id', 'pi.variant')
                ->get();

            // Mapping cepat
            $cuttingMap = [];

            foreach ($cuttingData as $item) {
                $variantKey = $item->variant ?: 'all';
                $cuttingMap[$item->model_id][$item->size_id][$variantKey] = $item->total_cutting;
            }

            // Gabungkan cutting ke tiap model -> sizes
            foreach ($models as $model) {
                foreach ($model->sizes as $size) {
                    $oriqtySize = $size->qty ?? 1;
                    $variantKey = $size->variant ?: 'all';

                    $cuttingQty =
                        $cuttingMap[$model->id][$size->size_id][$variantKey]
                        ?? $cuttingMap[$model->id][$size->size_id]['all']
                        ?? $oriqtySize;

                    $size->qty = $cuttingQty ?? $oriqtySize;
                }
            }

            return response()->json([
                'data' => $models
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data model',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $model = ModelRef::with(['sizes', 'activities', 'modelMaterial'])->findOrFail($id);

            // --- Ambil data cutting dari tr_production ---
            $cuttingData = DB::table('tr_production as p')
                ->join('tr_production_item as pi', 'pi.production_id', '=', 'p.id')
                ->where('p.model_id', $id)
                ->where('p.activity_role_id', 1) // CUTTING
                ->select('pi.size_id', 'pi.variant', DB::raw('SUM(pi.qty) as total_cutting'))
                ->groupBy('pi.size_id', 'pi.variant')
                ->get();

            // Buat map untuk pencocokan cepat
            $cuttingMap = [];

            foreach ($cuttingData as $item) {
                $variantKey = $item->variant ?: 'all'; // fallback
                $cuttingMap[$item->size_id][$variantKey] = $item->total_cutting;
            }

            // --- Gabungkan cutting qty ke struktur model.sizes ---
            foreach ($model->sizes as $size) {
                $oriqtySize = $size->qty ?? 1;
                $variantKey = $size->variant ?: 'all';

                $cuttingQty =
                    $cuttingMap[$size->size_id][$variantKey]
                    ?? $cuttingMap[$size->size_id]['all']
                    ?? $oriqtySize;

                $size->qty = $cuttingQty ?? $oriqtySize;
            }

            return response()->json([
                'data' => $model
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Model tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    public function update(Request $request, $id)
    {
        $model = ModelRef::findOrFail($id);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category_id' => 'required|exists:mst_category,id',
            'remark' => 'nullable|string',
            'estimation_price_pcs' => 'numeric|min:0',
            'estimation_qty' => 'integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',

            'sizes' => 'nullable|array',
            'sizes.*.size_id' => 'required|exists:mst_size,id',
            'sizes.*.variant' => 'required|string|max:100',
            'sizes.*.qty' => 'required|integer|min:1',
            'sizes.*.price_store' => 'required|numeric|min:1',
            'sizes.*.price_grosir' => 'required|numeric|min:1',

            'activity' => 'nullable|array',
            'activity.*.activity_role_id' => 'nullable|exists:mst_activity_role,id',
            'activity.*.price' => 'numeric|min:0',

            'modelMaterials' => 'nullable|array',
            'modelMaterials.*.product_id' => 'nullable|exists:mst_product,id',
            'modelMaterials.*.qty' => 'nullable|numeric|min:0',
            'modelMaterials.*.price' => 'nullable|numeric|min:0',
            'modelMaterials.*.uom_id' => 'nullable|exists:mst_uom,id',
            'modelMaterials.*.remark' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update ModelRef
            $model->update([
                'description' => $validated['description'] ?? "",
                'category_id' => $validated['category_id'] ?? null,
                'remark' => $validated['remark'] ?? "",
                'estimation_price_pcs' => $validated['estimation_price_pcs'] ?? null,
                'estimation_qty' => $validated['estimation_qty'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Update Product master record
            $product = Product::find($id);

            $data = [
                'name'         => $validated['description'],
                'category_id'  => $validated['category_id'],
                'uom_id'       => $validated['uom_id'] ?? 'PCS',   // gunakan default PCS
                'descriptions' => $validated['description'] ?? null,
                'updated_by'   => Auth::id(),
            ];
            
            if ($product) {
                // Update existing product
                $product->update($data);
            
            } else {
                // Create new product
                $product = Product::create(array_merge($data, [
                    'id'         => $id,              // only if id is not auto-increment
                    'created_by' => Auth::id(),
                ]));
            }
        

            if ($request->has('sizes')) {
                if (!empty($validated['sizes'])) {
                    ProductService::createProduct(
                        $product, // Pass the Product object
                        $validated['sizes'],
                        $request
                    );
                }
            }

            // Update model material
            $model->modelMaterial()->delete(); // Clear existing materials
            foreach ($validated['modelMaterials'] as $material) {
                $model->modelMaterial()->create([
                    'product_id' => $material['product_id'],
                    'qty' => $material['qty'],
                    'price' => $material['price'] ?? null,
                    'uom_id' => $material['uom_id'] ?? null,
                    'remark' => $material['remark'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            // Update activity roles
            if ($request->has('activity')) {
                $model->activities()->delete(); // Clear existing activities
                foreach ($validated['activity'] as $activity) {
                    $model->activities()->create([
                        'activity_role_id' => $activity['activity_role_id'],
                        'price' => $activity['price'] ?? 0,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Model updated successfully',
                'data' => $model->load(['product', 'sizes', 'activities', 'modelMaterial'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating model: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update model.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $model = ModelRef::with('product')->findOrFail($id);

            DB::beginTransaction();

            // Soft delete the associated Product
            if ($model->product) {
                $model->product->update(['deleted_by' => Auth::id()]);
                $model->product->delete();
            }

            // Soft delete the ModelRef
            $model->update(['deleted_by' => Auth::id()]);
            $model->delete();

            DB::commit();

            return response()->json([
                'message' => 'Model berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $request->validate([
                'search' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'sort_field' => 'nullable|string|in:created_at,start_date,description',
                'sort_order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = ModelRef::query();

            if ($request->filled('search')) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }
            if ($request->filled('start_date')) {
                $query->whereDate('start_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('start_date', '<=', $request->end_date);
            }

            if ($request->has('is_close') && $request->is_close !== '-' && $request->is_close !== '') {
                $query->where('is_close', $request->is_close);
            }


            $perPage = $request->get('per_page', 50);
            $models = $query->with(['product', 'sizes'])->orderBy('created_at', 'desc')->paginate($perPage);

            $modelIds = $models->pluck('id');

            // Ambil cutting untuk semua model dalam satu query
            $cuttingData = DB::table('tr_production as p')
                ->join('tr_production_item as pi', 'pi.production_id', '=', 'p.id')
                ->whereIn('p.model_id', $modelIds)
                ->where('p.activity_role_id', 1) // CUTTING
                ->select(
                    'p.model_id',
                    'pi.size_id',
                    'pi.variant',
                    DB::raw('SUM(pi.qty) as total_cutting')
                )
                ->groupBy('p.model_id', 'pi.size_id', 'pi.variant')
                ->get();

            // Mapping cepat
            $cuttingMap = [];

            foreach ($cuttingData as $item) {
                $variantKey = $item->variant ?: 'all';
                $cuttingMap[$item->model_id][$item->size_id][$variantKey] = $item->total_cutting;
            }

            // Gabungkan cutting ke tiap model -> sizes
            foreach ($models as $model) {
                foreach ($model->sizes as $size) {
                    $oriqtySize = $size->qty ?? 1;
                    $variantKey = $size->variant ?: 'all';

                    $cuttingQty =
                        $cuttingMap[$model->id][$size->size_id][$variantKey]
                        ?? $cuttingMap[$model->id][$size->size_id]['all']
                        ?? $oriqtySize;

                    $size->qty = $cuttingQty ?? $oriqtySize;
                }
            }


            return response()->json([
                'message' => 'Data model berhasil diambil',
                'data' => $models
            ]);
        } catch (\Exception $e) {
            Log::error('Model list error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateClose($id)
    {
        try {
            $model = ModelRef::findOrFail($id);

            // Tentukan status baru
            $newStatus = $model->is_close === 'Y' ? 'N' : 'Y';

            // Update status dan siapa yang ubah
            $model->update([
                'is_close' => $newStatus,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $newStatus === 'Y'
                    ? 'Model berhasil ditutup.'
                    : 'Model berhasil dibuka kembali.',
                'data' => [
                    'id' => $model->id,
                    'is_close' => $model->is_close,
                    'updated_by' => $model->updated_by,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Model tidak ditemukan.',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}