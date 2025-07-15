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
        $data = $query->orderBy('created_at', 'DESC')->paginate($perPage);

        return response()->json($data);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        $customers = Customer::where('name', 'like', '%' . $query . '%')
            ->orWhere('phone_number', 'like', '%' . $query . '%')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'address' => 'nullable|string|max:200',
                'phone_number' => 'nullable|string|max:20',
                'saldo_kredit' => 'numeric|min:0',
                'is_active' => 'in:Y,N'
            ]);

           
            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();


            $customer = Customer::create($validated);

            // automatically insert into table users
            $isExists = DB::table('users')->where('id', $customer->user_id)->exists();
            if (!$isExists) {
                DB::table('users')->insert([
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => ($customer->phone_number ?? $customer->name) . '@example.com', // Use phone number or name as email
                    'password' => bcrypt($customer->phone_number ?? $customer->name), // Set a default password
                    'phone_number' => $customer->phone_number,
                    'active' => $customer->is_active === 'Y' ? true : false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_role')->insert([
                    'role_id' => 7, // for customer
                    'user_id' => $customer->id,
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

    public function get($userId)
    {
        $customer = Customer::where('user_id', $userId)->first();
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