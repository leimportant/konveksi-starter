<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sloc;
use Illuminate\Http\Request;

class SlocController extends Controller
{
    public function index(Request $request)
    {
        $query = Sloc::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%')
            ->orWhere('id', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 10);
        $sloc = $query->paginate($perPage);

        return response()->json($sloc);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|max:10|unique:mst_sloc,id',
            'name' => 'required|string|max:255'
        ]);

        return Sloc::create($validated);
    }

    public function show(Sloc $sloc)
    {
        return $sloc;
    }

    public function update(Request $request, Sloc $sloc)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255'
        ]);

        $sloc->update($validated);
        return $sloc;
    }

    public function destroy(Sloc $sloc)
    {
        $sloc->delete();
        return response()->noContent();
    }
}