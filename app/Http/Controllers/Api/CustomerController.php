<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
     public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 10);
        $categories = $query->paginate($perPage);

        return response()->json($categories);
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'address' => 'required|string|max:200',
                'phone_number' => 'required|string|max:20',
                'saldo_kredit' => 'numeric|min:0',
                'is_active' => 'in:Y,N'
            ]);

           
            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            $customer = Customer::create($validated);

               // automatically insert into table users
            $isExists = DB::table('users')->where('username', $customer->id)->exists();
            if (!$isExists) {
                DB::table('users')->insert([
                    'username' => $customer->id,
                    'password' => bcrypt($request->phone_number), // Set a default password
                    'role' => 'customer', // Assuming a role for the user
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

           return response()->json($customer, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'address' => 'required|string|max:200',
                'phone_number' => 'required|string|max:20',
                'saldo_kredit' => 'numeric|min:0',
                'is_active' => 'in:Y,N'
            ]);

            $customer->update($validated);

            DB::commit();

            return response()->json($customer);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer: ' . $e->getMessage()
            ], 500);
        }
    }
}