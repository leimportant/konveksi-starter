<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashBalanceController extends Controller
{
    public function index(Request $request)
    {

        $query = CashBalance::query();

        if ($request->has('name')) {
            $query->where('shift_date', 'like', '%' . $request->name . '%')
                ->orWhere('status', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 10);
        $cashBalances = $query->paginate($perPage);

        return response()->json($cashBalances);

    }

    public function openShift(Request $request)
    {
        try {
            $validated = $request->validate([
                'shift_number' => 'required|integer',
                'opening_balance' => 'required|numeric|min:0'
            ]);

            $validated['cashier_id'] = Auth::id();
            $validated['shift_date'] = Carbon::now();
            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();
            $validated['status'] = 'open';
 
            $cashBalance = CashBalance::create($validated);

           return response()->json($cashBalance, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to open shift: ' . $e->getMessage()
            ], 500);
        }
    }

    public function closeShift(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'cash_sales_total' => 'required|numeric|min:0',
                'cash_in' => 'required|numeric|min:0',
                'cash_out' => 'required|numeric|min:0',
                'closing_balance' => 'required|numeric|min:0',
                'discrepancy' => 'nullable|numeric',
                'notes' => 'nullable|string'
            ]);

            $cashBalance = CashBalance::findOrFail($id);

            if ($cashBalance->status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => 'Shift is already closed'
                ], 400);
            }

            $cashBalance->update([
                'cash_sales_total' => $validated['cash_sales_total'],
                'cash_in' => $validated['cash_in'],
                'cash_out' => $validated['cash_out'],
                'closing_balance' => $validated['closing_balance'],
                'discrepancy' => $validated['discrepancy'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'closed_at' => now(),
                'status' => 'closed'
            ]);

            return response()->json([
                'success' => true,
                'data' => $cashBalance,
                'message' => 'Shift closed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close shift: ' . $e->getMessage()
            ], 500);
        }
    }
}