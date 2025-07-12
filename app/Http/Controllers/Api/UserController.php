<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    /**
     * Display a listing of users
     */

    public function index(Request $request)
    {
        $query = User::with('roles', 'location');

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%')
            ->orWhere('email', 'like', '%' . $request->name . '%')
            ->orWhere('phone_number', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 10);
        $users = $query->orderBy('created_at')->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a new user
     */
   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255|unique:users',
        'phone_number' => 'required|string|min:8',
        'location_id' => 'required|exists:mst_location,id',
        'role' => 'required|exists:roles,id',
        'active' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    // Tentukan status karyawan berdasarkan role ID
    $employeeStatus = 'staff';
    if (in_array($request->role, [7, 2])) {
        $employeeStatus = 'customer';
    }

    // Buat user
    $user = User::create([
        'employee_status' => $employeeStatus,
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->phone_number), // default password
        'phone_number' => $request->phone_number,
        'location_id' => $request->location_id,
        'active' => $request->active ?? true,
    ]);

    // Attach role dengan metadata
    $user->roles()->attach([
        $request->role => [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'User created successfully',
        'data' => $user->load('roles')
    ], 201);
}

    /**
     * Display the specified user
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone_number' => 'required|string|min:8',
            'location_id' =>'required|exists:mst_location,id',
            'role' => 'required|exists:roles,id',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->location_id = $request->location_id;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->active = $request->has('active') ? filter_var($request->active, FILTER_VALIDATE_BOOLEAN) : false;
;
        $employeeStatus = 'staff';
        if ($request->role == 7) { // Assuming 7 is the ID for 'customer' role
            $employeeStatus = 'customer';
        } elseif ($request->role == 2) { // Assuming 2 is the ID for 'owner' role
            $employeeStatus = 'customer';
        }
        $user->employee_status = $employeeStatus;
        $user->save();

        // Sync roles (remove existing and add new)
        $user->roles()->sync([$request->role]);

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user->load('roles')
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Detach all roles before deleting
        $user->roles()->detach();
        $user->update([
            'active' => false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}