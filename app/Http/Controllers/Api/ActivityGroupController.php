<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityRoleRef;
use App\Models\ActivityGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityGroupController extends Controller
{
    public function index()
    {
        $activityGroups = ActivityGroup::all();
        return response()->json(['data' => $activityGroups]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:40',
            'sorting' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $activityGroup = ActivityGroup::create($request->all());
        return response()->json(['data' => $activityGroup], 201);
    }

    public function show(ActivityGroup $activityGroup)
    {
        return response()->json(['data' => $activityGroup]);
    }

    public function update(Request $request, ActivityGroup $activityGroup)
    {
        $validator = Validator::make($request->all(), [
            'sorting' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $activityGroup->update($request->all());
        return response()->json(['data' => $activityGroup]);
    }

    public function destroy(ActivityGroup $activityGroup)
    {
        $activityGroup->delete();
        return response()->json(null, 204);
    }


    public function getActivityGroupByUser(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $userRoleIds = $user->roles->pluck('id');
        $roleRef = ActivityRoleRef::whereIn('role_id', $userRoleIds)->get();
        $datas = ActivityGroup::whereIn('id', $roleRef->pluck('activity_group_id'))->get();

        return response()->json(['data' => $datas], 200);
    }

    public function getActivityEmployee($id)
    {
        $user = Auth::user();
        $userId = $user->id;
        $userRoleIds = $user->roles->pluck('id');
      
        // Cek apakah role user saat ini memiliki akses ke semua employee
        $allEmployee = DB::table('roles')
            ->whereIn('id', $userRoleIds)
            ->where('all_employee', 'Y')
            ->exists();

        // Ambil semua role yang terkait dengan group tersebut
        $roleIds = ActivityRoleRef::where('activity_group_id', $id)
            ->pluck('role_id');

        Log::info( $roleIds);


        // Query dasar user
        $query = User::join('user_role', 'users.id', '=', 'user_role.user_id')
            ->whereIn('user_role.role_id', $roleIds)
            ->select('users.id', 'users.name', 'users.email')
            ->distinct();

        // Jika user tidak memiliki hak melihat semua employee


        if (!$allEmployee) {
            $query->where('users.id', $userId);
        }

        $employees = $query->get();
        
        Log::info( $employees);

        return response()->json([
            'data' => $employees
        ], 200);
    }


}