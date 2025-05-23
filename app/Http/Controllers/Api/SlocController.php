<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sloc;
use Illuminate\Http\Request;

class SlocController extends Controller
{
    public function index()
    {
        $slocs = Sloc::latest()->paginate(10);
        return response()->json($slocs);
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