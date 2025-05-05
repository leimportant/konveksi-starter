<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModelRef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Support\Facades\Log;

class ModelRefController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'start_date' => 'required|string',
            'remark' => 'nullable|string',
            'estimation_price_pcs' => 'required|numeric|min:0',
            'estimation_qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $model = ModelRef::create([
                'description' => $request->description,
                'remark' => $request->remark,
                'start_date' => $request->start_date,
                'estimation_price_pcs' => $request->estimation_price_pcs,
                'estimation_qty' => $request->estimation_qty,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Model berhasil dibuat',
                'data' => $model
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat model',
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
            $model = ModelRef::findOrFail($id);
            
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $model = ModelRef::findOrFail($id);
            
            $model->update([
                'description' => $request->description,
                'remark' => $request->remark,
                'estimation_price_pcs' => $request->estimation_price_pcs,
                'estimation_qty' => $request->estimation_qty,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Model berhasil diperbarui',
                'data' => $model
            ]);

        } catch (\Exception $e) {
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