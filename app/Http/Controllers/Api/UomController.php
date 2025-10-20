<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UomController extends Controller
{
    public function index(Request $request)
    {
        $query = Uom::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 50);
        $categories = $query->paginate($perPage);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'max:100',
                Rule::unique('mst_uom', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $validated['id'] = $request->name;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $uom = Uom::create($validated);
        return response()->json($uom, 201);
    }


    public function show(Uom $uom)
    {
        return response()->json($uom);
    }

    public function update(Request $request, Uom $uom)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('mst_uom')->ignore($uom->id)],
        ]);
        $validated['updated_by'] = Auth::id();
        $uom->update($validated);
        return response()->json($uom);
    }

    public function destroy(Uom $uom)
    {
        $uom->delete();
        return response()->json(null, 204);
    }
}