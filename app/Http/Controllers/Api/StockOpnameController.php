<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class StockOpnameController extends Controller
{
    public function index()
    {
        $opname = StockOpname::with(['product', 'location', 'items'])->latest()->paginate(50);
        return response()->json($opname);
    }

    public function store(Request $request)
    {
        $uuid = Str::uuid()->toString();
    
        $validated = $request->validate([
            'product_id' => 'required|exists:mst_product,id',
            'location_id' => 'required|exists:mst_location,id',
            'sloc_id' => 'required|exists:mst_sloc,id',
            'uom_id' => 'required|exists:mst_uom,id',
            'stock_opname_items' => 'required|array|min:1',
            'stock_opname_items.*.size_id' => 'required|exists:mst_size,id',
            'stock_opname_items.*.variant' => 'required',
            'stock_opname_items.*.qty_system' => 'required|integer',
            'stock_opname_items.*.qty_physical' => 'required|integer',
            'stock_opname_items.*.difference' => 'required|integer',
            'stock_opname_items.*.note' => 'nullable|string',
        ]);
    
        // Create Stock Opname (Eloquent model)
        $stockOpname = StockOpname::create([
            'id' => $uuid,
            'product_id' => $validated['product_id'],
            'location_id' => $validated['location_id'],
            'sloc_id' => $validated['sloc_id'],
            'uom_id' => $validated['uom_id'],
            'remark' => $request->input('remark'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    
        // Pastikan $stockOpname adalah model Eloquent, lalu buat items
        $inventoryService = new InventoryService();
    
        foreach ($validated['stock_opname_items'] as $item) {
            $stockOpname->items()->create([
                'opname_id' => $uuid,
                'size_id' => $item['size_id'],
                'variant' => $item['variant'],
                'qty_system' => $item['qty_system'],
                'qty_physical' => $item['qty_physical'],
                'difference' => $item['difference'],
                'note' => $item['note'],
            ]);
    
            // Use InventoryService to update or create inventory
            $inventoryService->updateOrCreateInventory($validated, $item);
        }
    
        // Load relation items sebelum return
        $stockOpname->load('items');
    
        return response()->json([
            'message' => 'Opname berhasil disimpan',
            'data' => $stockOpname,
        ], 201);
    }
    

    public function show(StockOpname $stockOpname)
    {
        return response()->json($stockOpname->load('items'));
    }

    public function destroy(StockOpname $stockOpname)
    {
        $stockOpname->delete();
        return response()->noContent();
    }
}