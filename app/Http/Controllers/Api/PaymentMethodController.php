<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 50);
        $payment = $query->paginate($perPage);

        return response()->json($payment);

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:10|unique:mst_payment_method',
        ]);
        $validated['id'] = $request->name;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $payment = PaymentMethod::create($validated);
        return response()->json($payment, 201);
    }


    public function show(PaymentMethod $payment)
    {
        return response()->json($payment);
    }

    public function update(Request $request, PaymentMethod $payment)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:10', Rule::unique('mst_payment_method')->ignore($payment->id)],
        ]);
        $validated['updated_by'] = Auth::id();
        $payment->update($validated);
        return response()->json($payment);
    }

    public function destroy(PaymentMethod $payment)
    {
        $payment->deleted_by = Auth::id();
        $payment->save();
        $payment->delete();
        return response()->json(null, 204);
    }

}