<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::whereNotIn('id', [0]); // ✅ perbaikan

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 10);
        $categories = $query->paginate($perPage);

        return response()->json($categories);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:mst_category',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $category = Category::create($validated);
        return response()->json($category, 201);
    }


    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('mst_category')->ignore($category->id)],
        ]);
        $validated['updated_by'] = Auth::id();
        $category->update($validated);
        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(null, 204);
    }
}