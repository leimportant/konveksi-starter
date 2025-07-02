<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 10);
        $categories = $query->paginate($perPage);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:mst_bank_account',
            'account_number' => 'required|string|max:100|unique:mst_bank_account',
        ]);
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $account = BankAccount::create($validated);
        return response()->json($account, 201);
    }

    public function show(BankAccount $account)
    {
        return response()->json($account);
    }

    public function update(Request $request, BankAccount $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:mst_bank_account',
            'account_number' => 'required|string|max:100|unique:mst_bank_account',
        ]);

        $account->update($validated);
        return response()->json($account);
    }

    public function destroy(BankAccount $account)
    {
        $account->deleted_by = Auth::id();
        $account->save();
        $account->delete();
        return response()->json(null, 204);
    }
}