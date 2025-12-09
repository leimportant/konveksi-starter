<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Reference;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;


class ComboController extends Controller
{
    public function getComboData(Request $request)
    {
        $key = $request->key;
        switch ($key) {
            case 'reference':
                return $this->getReference($key);
            case 'location':
                return $this->getLocationUser($key, $request);
            case 'product-inventory':
                return $this->getProductInventory($key, $request);
            default:
                return response()->json(['error' => 'Invalid key'], 400);
        }
    }
    public function getReference($key)
    {
        $data = Reference::query()
            ->select('id', 'name')
            ->where('ref_type_id', $key)  
            ->get();

        return response()->json($data);
    }

    public function getProductInventory($key, Request $request) {
        $locationId = $request->input('location_id');
        $data = Inventory::query()
            ->select('product_id', 'mst_product.name as product_name', 'tr_inventory.sloc_id','tr_inventory.uom_id')
            ->join('mst_product', 'tr_inventory.product_id', '=', 'mst_product.id')
            ->where('location_id', $locationId)
            ->get()->groupBy('product_id');

        // Attach inventory sizes for each product
        $result = [];
        foreach ($data as $productId => $items) {
            $sizes = $this->getProductInventorySize($locationId, $productId)->getData();
            $result[] = [
                'product_id' => $productId,
                'product_name' => $items[0]->product_name ?? '',
                'sloc_id' => $items[0]->sloc_id ?? '',
                'uom_id' => $items[0]->uom_id ?? '',
                'sizes' => $sizes->data ?? []
            ];
        }
        return response()->json($result);
    }

    public function getProductInventorySize($locationId, $productId) {
        $query = Inventory::with(['product', 'location', 'sloc', 'uom']);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }
        $inventory = $query->get();

        // Debug: Log the count and data
        Log::info('Inventory count: ' . $inventory->count());
        Log::info('Inventory data: ', $inventory->toArray());

        // Ensure the response structure is correct
        $sizes = $inventory->map(function($item) {
            return [
                'size_id' => $item->size_id,
                'variant' =>$item->variant,
                'qty' => $item->qty
            ];
        });

        return response()->json(['data' => $sizes]);
    }
    public function getLocationUser($key, Request $request) {
        $data = Location::query()
            ->select('id', 'name')
            ->where('id', Auth::user()->location_id)  
            ->get();

        return response()->json($data);
    }
}