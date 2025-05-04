<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Reference;
use Illuminate\Http\Request;

class ComboController extends Controller
{
    public function getReference($key)
    {
        $data = Reference::query()
            ->select('id', 'name')
            ->where('ref_type_id', $key)  
            ->get();

        return response()->json($data);
    }
}