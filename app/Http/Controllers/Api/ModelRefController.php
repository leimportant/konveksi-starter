<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'start_date' => 'required|date',
            'sizes' => 'required|array',
            'sizes.*.size_id' => 'required|exists:mst_size,id',
            'sizes.*.qty' => 'required|integer|min:1',
            'activity' => 'required|array',
            'activity.*.role_id' => 'required|exists:mst_activity_role,id',
            'activity.*.price' => 'required|numeric|min:0'
            // 'documents' => 'nullable|array',
            // 'documents.*.id' => 'required|string|max:50',
            // 'documents.*.url' => 'required|string|url',
            // 'documents.*.filename' => 'required|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Create the model
            $model = ModelRef::create([
                'description' => $validated['description'],
                'remark' => $validated['remark'],
                'estimation_price_pcs' => $validated['estimation_price_pcs'],
                'estimation_qty' => $validated['estimation_qty'],
                'start_date' => $validated['start_date'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Store sizes
            foreach ($validated['sizes'] as $size) {
                $model->sizes()->create([
                    'size_id' => $size['size_id'],
                    'qty' => $size['qty'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Store activities
            foreach ($validated['activity'] as $activity) {
                $model->activities()->create([
                    'role_id' => $activity['role_id'],
                    'price' => $activity['price'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Store documents
            // if (!empty($validated['documents'])) {
            //     foreach ($validated['documents'] as $document) {
            //         $model->documents()->create([
            //             'id' => $document['id'],
            //             'url' => $document['url'],
            //             'filename' => $document['filename'],
            //             'created_by' => Auth::id(),
            //             'updated_by' => Auth::id()
            //         ]);
            //     }
            // }

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

            
            $models = ModelRef::latest()->paginate(10);
            
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
            $model = ModelRef::with(['sizes', 'activities'])->findOrFail($id);
            
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
            'activity.*.role_id' => 'required|exists:mst_activity_role,id',
            'activity.*.price' => 'required|numeric|min:0'
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
                'updated_by' => Auth::id()
            ]);

            // Update sizes - gunakan forceDelete untuk memastikan record lama terhapus
            $model->sizes()->forceDelete();
            foreach ($request->sizes as $size) {
                $model->sizes()->create([
                    'size_id' => $size['size_id'],
                    'qty' => $size['qty'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Update activities - gunakan forceDelete untuk memastikan record lama terhapus
            $model->activities()->forceDelete();
            foreach ($request->activity as $activity) {
                $model->activities()->create([
                    'role_id' => $activity['role_id'],
                    'price' => $activity['price'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
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
    
            $query = ModelRef::query(); // Changed from get() to query()
    
            if ($request->filled('search')) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }
    
            if ($request->filled('start_date')) {
                $query->where('start_date', '>=', $request->start_date);
            }
    
            if ($request->filled('end_date')) {
                $query->where('start_date', '<=', $request->end_date);
            }
    
            $sortField = $request->get('sort_field', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortField, $sortOrder);
    
            $perPage = $request->get('per_page', 10);
            $models = $query->paginate($perPage);
    
            return response()->json([
                'message' => 'Data model berhasil diambil',
                'data' => $models
            ]);
    
        } catch (\Exception $e) {

            Log::info($e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data model',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}