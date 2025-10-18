<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoodReceive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class GoodReceiveController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('name');

        $query = GoodReceive::with(['items', 'model'])
                ->whereNull('deleted_at')
                ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('recipent', 'like', "%{$search}%")
                    ->orWhereHas('model', function ($q2) use ($search) {
                        $q2->where('description', 'like', "%{$search}%");
                    });
            });
        }

        $data = $query->paginate(10);

        return response()->json($data);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'model_id' => 'required|exists:tr_model,id',
            'recipent' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.model_material_id' => 'required|exists:mst_product,id',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.qty_convert' => 'required|numeric|min:0',
            'items.*.uom_base' => 'required|exists:mst_uom,id',
            'items.*.uom_convert' => 'required|exists:mst_uom,id',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $goodReceive = GoodReceive::create($validated);

        // Save items
        foreach ($request->items as $item) {
            $goodReceive->items()->create([
                'model_material_id' => $item['model_material_id'],
                'model_material_item' => $item['model_material_item'],
                'qty' => $item['qty'],
                'qty_convert' => $item['qty_convert'],
                'uom_base' => $item['uom_base'],
                'uom_convert' => $item['uom_convert'],
            ]);
        }

        return response()->json($goodReceive->load('items'), 201);
    }

    public function show($id)
    {
        try {
            $model = GoodReceive::with(['model', 'baseUom', 'convertUom'])->findOrFail($id);

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

    public function update(Request $request, GoodReceive $goodReceive)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'model_id' => 'required|exists:tr_model,id',
            'description' => 'required|string',
            'recipent' => 'required|string|max:255',
            'good_receive_items' => 'required|array',
            'good_receive_items.*.model_material_id' => 'required|exists:mst_product,id',
            'good_receive_items.*.qty' => 'required|numeric|min:0',
            'good_receive_items.*.qty_convert' => 'required|numeric|min:0',
            'good_receive_items.*.uom_base' => 'required|exists:mst_uom,id',
            'good_receive_items.*.uom_convert' => 'required|exists:mst_uom,id',
        ]);

        $validated['updated_by'] = Auth::id();

        $goodReceive->update($validated);

        // Delete existing items
        $goodReceive->items()->delete();

        // Create new items
        foreach ($request->good_receive_items as $item) {
            $goodReceive->items()->create([
                'model_material_id' => $item['model_material_id'],
                'model_material_item' => $item['model_material_item'],
                'qty' => $item['qty'],
                'qty_convert' => $item['qty_convert'],
                'uom_base' => $item['uom_base'],
                'uom_convert' => $item['uom_convert'],
            ]);
        }

        return response()->json($goodReceive->load('items'));
    }

    public function destroy(GoodReceive $goodReceive)
    {
        // Simpan siapa yang menghapus sebelum data dihapus
        $goodReceive->deleted_by = Auth::id();
        $goodReceive->save();

        // Lalu hapus secara permanen
        $goodReceive->forceDelete();

        return response()->json(null, 204);
    }

}