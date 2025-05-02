<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class SizeController extends Controller
{
    public function index()
    {
        $sizes = Size::latest()->paginate(10);
        return response()->json($sizes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:mst_size',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $size = Size::create($validated);
        return response()->json($size, 201);
    }

    public function show(Size $size)
    {
        return response()->json($size);
    }

    public function update(Request $request, Size $size)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('mst_size')->ignore($size->id)],
        ]);

        $size->update($validated);
        return response()->json($size);
    }

    public function destroy(Size $size)
    {
        $size->deleted_by = Auth::id();
        $size->save();
        $size->delete();
        return response()->json(null, 204);
    }
}