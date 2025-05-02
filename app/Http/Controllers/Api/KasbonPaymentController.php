<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KasbonPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KasbonPaymentController extends Controller
{
    public function index()
    {
        $payments = KasbonPayment::latest()->paginate(10);
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_number' => 'required|unique:kasbon_payments',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $payment = KasbonPayment::create($validated);
        return response()->json($payment, 201);
    }

    public function show(KasbonPayment $kasbonPayment)
    {
        return response()->json($kasbonPayment);
    }

    public function update(Request $request, KasbonPayment $kasbonPayment)
    {
        $validated = $request->validate([
            'payment_number' => ['required', Rule::unique('kasbon_payments')->ignore($kasbonPayment->id)],
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $kasbonPayment->update($validated);
        return response()->json($kasbonPayment);
    }

    public function destroy(KasbonPayment $kasbonPayment)
    {
        $kasbonPayment->delete();
        return response()->json(null, 204);
    }
}
