<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealAllowances;
use Illuminate\Http\Request;

class MealAllowancesController extends Controller
{
     public function index(Request $request)
    {
        $query = MealAllowances::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%')
                    ->orWhere('amount', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 50);
        $mealAllowances = $query->paginate($perPage);

        return response()->json($mealAllowances);
    }

    public function store(Request $request)
    {
       $validated = $request->validate([
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
        ]);


        return MealAllowances::create($validated);
    }

    public function show(MealAllowances $mealAllowances)
    {
        return response()->json($mealAllowances);
    }

    public function update(Request $request, MealAllowances $mealAllowances)
    {
       $validated = $request->validate([
                'name' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0',
            ]);


        $mealAllowances->update($validated);
        return $mealAllowances;
    }

    public function destroy($id)
    {
        $mealAllowances = MealAllowances::findOrFail($id);
        $mealAllowances->delete();

        return response()->noContent();
    }

}