<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceType;
use Illuminate\Http\Request;

class PriceTypeController extends Controller
{
    public function index()
    {
        $priceType = PriceType::latest()->paginate(10);
        return response()->json($priceType);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        return PriceType::create($validated);
    }

    public function show(PriceType $priceType)
    {
        return response()->json($priceType);
    }

    public function update(Request $request, PriceType $priceType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $priceType->update($validated);
        return $priceType;
    }

    public function destroy(PriceType $priceType)
    {
        $priceType->delete();
        return response()->noContent();
    }
}