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
    public function index()
    {
        $data = GoodReceive::with(['model', 'baseUom', 'convertUom'])
            ->latest()
            ->paginate(10);
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'model_id' => 'required|exists:tr_model,id',
            'description' => 'required|string',
            'qty_base' => 'required|numeric|min:0',
            'qty_convert' => 'required|numeric|min:0',
            'uom_base' => 'required|exists:mst_uom,id',
            'uom_convert' => 'required|exists:mst_uom,id',
            'recipent' => 'required|string|max:255'
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $goodReceive = GoodReceive::create($validated);
        return response()->json($goodReceive, 201);
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
            'qty_base' => 'required|numeric|min:0',
            'qty_convert' => 'required|numeric|min:0',
            'uom_base' => 'required|exists:mst_uom,id',
            'uom_convert' => 'required|exists:mst_uom,id',
            'recipent' => 'required|string|max:255'
        ]);

        $validated['updated_by'] = Auth::id();

        $goodReceive->update($validated);
        return response()->json($goodReceive);
    }

    public function destroy(GoodReceive $goodReceive)
    {
        $goodReceive->deleted_by = Auth::id();
        $goodReceive->save();
        $goodReceive->delete();

        return response()->json(null, 204);
    }
}