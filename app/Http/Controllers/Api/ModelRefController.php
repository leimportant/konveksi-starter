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

class ModelRefController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'remark' => 'nullable|string',
            'estimation_price_pcs' => 'required|numeric|min:0',
            'estimation_qty' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'sizes' => 'required|array',
            'sizes.*.size_id' => 'required|exists:mst_size,id',
            'sizes.*.variant' => 'required|string|max:100',
            'sizes.*.qty' => 'required|integer|min:1',
            'activity' => 'required|array',
            'activity.*.activity_role_id' => 'required|exists:mst_activity_role,id',
            'activity.*.price' => 'required|numeric|min:0',
            'modelMaterials' => 'required|array',
            'modelMaterials.*.product_id' => 'required|exists:mst_product,id',
            'modelMaterials.*.qty' => 'required|numeric|min:0',
            'modelMaterials.*.price' => 'required|numeric|min:0',
            'modelMaterials.*.uom_id' => 'required|exists:mst_uom,id',
            'modelMaterials.*.remark' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Create the model
            $model = ModelRef::create([
                'description' => $validated['description'],
                'remark' => $validated['remark'],
                'estimation_price_pcs' => $validated['estimation_price_pcs'] ?? null,
                'estimation_qty' => $validated['estimation_qty'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Store sizes
            foreach ($validated['sizes'] as $size) {
                $model->sizes()->create([
                    'size_id' => $size['size_id'],
                    'variant' => $size['variant'],
                    'qty' => $size['qty'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Store activities
            foreach ($validated['activity'] as $activity) {
                $model->activities()->create([
                    'activity_role_id' => $activity['activity_role_id'],
                    'price' => $activity['price'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            if (!empty($validated['modelMaterials'])) {
                foreach ($validated['modelMaterials'] as $index => $modelMaterial) {
                    $productId = is_array($modelMaterial['product_id'])
                        ? $modelMaterial['product_id']['id'] ?? null
                        : $modelMaterial['product_id'];

                    $model->modelMaterial()->create([
                        'product_id' => $productId,
                        'item' => $index + 1,
                        'remark' => $modelMaterial['remark'] ?? null,
                        'qty' => $modelMaterial['qty'],
                        'price' => $modelMaterial['price'],
                        'uom_id' => $modelMaterial['uom_id'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
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

            $models = ModelRef::with(['sizes', 'activities', 'modelMaterial'])->latest()->paginate(10);

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
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'remark' => 'nullable|string',
            'estimation_price_pcs' => 'required|numeric|min:0',
            'estimation_qty' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'sizes' => 'required|array',
            'sizes.*.size_id' => 'required|exists:mst_size,id',
            'sizes.*.qty' => 'required|integer|min:1',
            'activity' => 'required|array',
            'activity.*.activity_role_id' => 'required|exists:mst_activity_role,id',
            'activity.*.price' => 'required|numeric|min:0',
            'modelMaterials' => 'required|array',
            'modelMaterials.*.product_id' => 'required|exists:mst_product,id',
            'modelMaterials.*.qty' => 'required|numeric|min:0',
            'modelMaterials.*.price' => 'required|numeric|min:0',
            'modelMaterials.*.uom_id' => 'required|exists:mst_uom,id',
            'modelMaterials.*.remark' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $model = ModelRef::findOrFail($id);

            // Update main model data
            $model->update([
                'description' => $request->description,
                'remark' => $request->remark,
                'estimation_price_pcs' => $request->estimation_price_pcs,
                'estimation_qty' => $request->estimation_qty,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'updated_by' => Auth::id()
            ]);

            // Update sizes - gunakan forceDelete untuk memastikan record lama terhapus
            $model->sizes()->forceDelete();
            foreach ($request->sizes as $size) {
                $model->sizes()->create([
                    'size_id' => $size['size_id'],
                    'variant' => $size['variant'],
                    'qty' => $size['qty'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Update activities - gunakan forceDelete untuk memastikan record lama terhapus
            $model->activities()->forceDelete();
            foreach ($request->activity as $activity) {
                $model->activities()->create([
                    'activity_role_id' => $activity['activity_role_id'],
                    'price' => $activity['price'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            if (!empty($request->modelMaterials)) {
                $model->modelMaterial()->forceDelete(); // Perubahan di sini: modelMaterials -> modelMaterial
                foreach ($request->modelMaterials as $index => $modelMaterial) {
                    $productId = is_array($modelMaterial['product_id'])
                        ? $modelMaterial['product_id']['id'] ?? null
                        : $modelMaterial['product_id'];


                    $model->modelMaterial()->create([
                        'product_id' => $productId,
                        'item' => $index + 1, // Increment the number for each model material
                        'remark' => $modelMaterial['remark'],
                        'qty' => $modelMaterial['qty'],
                        'price' => $modelMaterial['price'],
                        'uom_id' => $modelMaterial['uom_id'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                }

            }


            DB::commit();

            return response()->json([
                'message' => 'Model berhasil diperbarui',
                'data' => $model->load(['sizes', 'activities'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $model = ModelRef::findOrFail($id);

            $model->update(['deleted_by' => Auth::id()]);
            $model->delete();

            return response()->json([
                'message' => 'Model berhasil dihapus'
            ]);

        } catch (\Exception $e) {
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

            if ($request->filled( 'is_close') != '-') {
                $query->where('is_close',  $request->is_close);
            }

            $sortField = $request->get('sort_field', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortField, $sortOrder);

            $perPage = $request->get('per_page', 10);
            $models = $query->with('sizes')->orderBy('created_at', 'desc')->paginate($perPage);

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