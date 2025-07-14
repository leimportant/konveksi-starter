<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class BahanController extends Controller
{
    public function bahansBySearch(Request $request)
    {
        $search = $request->input('search');
        $query = Bahan::where('category_id', 0)->query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('descriptions', 'like', '%' . $search . '%')
;
        }

        $data = $query->with(['uom'])
            ->orderBy('name')
            ->paginate();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

   public function index(Request $request)
    {
        $query = Bahan::where('category_id', 0)
                         ->with(['uom']); 

        $search = $request->input('search');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('descriptions', 'like', '%' . $search . '%')
                ->orWhereHas('uom', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        }

        $perPage = $request->input('perPage', 10);
        $data = $query->paginate($perPage);

        return response()->json($data);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'uom_id' => 'exists:mst_uom,id',
            'name' => [
                'required',
                'max:255',
                Rule::unique('mst_product', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $validated['category_id'] = 0;
        $newId = $this->generateNumber(100, $validated['category_id']);
        $validated['id'] = $newId;

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $Bahan = Bahan::create($validated);
        return response()->json($Bahan, 201);
    }


    private function generateNumber(int $prefixCategoryId, int $realCategoryId): string
    {
        $prefixStr = (string) $prefixCategoryId;
        $prefixLength = strlen($prefixStr);

        $lastId = DB::table('mst_product')
            ->where('id', 'like', $prefixStr . '%')
            ->orderByDesc('id')
            ->value('id');

        $lastNumber = 0;
        if ($lastId && strlen($lastId) > $prefixLength) {
            $lastNumber = (int) substr($lastId, $prefixLength);
        }

        $newNumber = $lastNumber + 1;
        return $prefixStr . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }


    public function update(Request $request, Bahan $data)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255', Rule::unique('mst_product')->ignore($data->id)],
            'uom_id' => 'exists:mst_uom,id',
        ]);

        $validated['category_id'] = 0;

        $data->update($validated);
        return response()->json($data);
    }

    public function show(Bahan $Bahan)
    {
        return response()->json($Bahan);
    }

    public function destroy(Bahan $bahan)
    {
        $bahan->deleted_by = Auth::id();
        $bahan->save();
        $bahan->delete();
        return response()->json(null, 204);
    }
}