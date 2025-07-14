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
            'name' => 'required|max:255|unique:mst_product,name',
        ]);
        $validated['category_id'] = 0;
        $newId = $this->generateNumber(100);
        $validated['id'] = $newId;
       
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $Bahan = Bahan::create($validated);
        return response()->json($Bahan, 201);
    }

    private function generateNumber(int $categoryId): string
    {
        // Panjang digit category_id (misalnya: 2 untuk '12')
        $categoryIdStr = (string) $categoryId;
        $prefixLength = strlen($categoryIdStr);

        // Ambil ID terakhir dari produk dalam kategori itu
        $lastBahanId = DB::table('mst_product')
            ->where('category_id', $categoryId)
            ->where('id', 'like', $categoryIdStr . '%')
            ->orderByDesc('id')
            ->value('id');

        // Ambil 4 digit terakhir (setelah prefix category_id)
        $lastNumber = 0;
        if ($lastBahanId) {
            $lastNumber = (int) substr($lastBahanId, $prefixLength);
        }

        $newNumber = $lastNumber + 1;

        // Gabungkan: category_id + new number (formatted)
        return $categoryIdStr . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
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