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
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(10);
        
        return response()->json($users);
    }

    /**
     * Store a new user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active' => $request->active ?? true
        ]);

        // Attach role
        $user->roles()->attach([
            $request->role => [
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
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
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8',
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
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->active = $request->active ?? $user->active;
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
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}