<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityRoleRef;
use App\Models\ActivityGroup;
use App\Models\User;
use App\Models\Role;
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
        if (in_array($id, ['QUALITY_CHECK', 'FINISHING', 'PACKING'])) {
            $roleIds = ActivityRoleRef::whereNotNull('activity_group_id')
                ->pluck('role_id');
        } else {
            $roleIds = ActivityRoleRef::where('activity_group_id', $id)
                ->pluck('role_id');
        }


        $roleIds = ActivityRoleRef::where('activity_group_id', $id)
            ->pluck('role_id');

        Log::info($roleIds);


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

        Log::info($employees);

        return response()->json([
            'data' => $employees
        ], 200);
    }


    public function updateActivityGroups(Request $request, $id)
    {
        $validated = $request->validate([
            'activity_groups' => 'array',
            'activity_groups.*' => 'string|exists:mst_activity_group,id',
        ]);

        DB::beginTransaction();

        try {
            // Hapus semua relasi lama untuk role ini
            DB::table('mst_activity_role_ref')
                ->where('role_id', $id)
                ->delete();

            // Masukkan relasi baru
            if (!empty($validated['activity_groups'])) {
                $insertData = collect($validated['activity_groups'])->map(function ($groupId) use ($id) {
                    return [
                        'role_id' => $id,
                        'activity_group_id' => $groupId,
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                DB::table('mst_activity_role_ref')->insert($insertData);
            }

            DB::commit();

            // Ambil ulang data role + activity group-nya
            $role = DB::table('roles')
                ->where('id', $id)
                ->first();

            $activityGroups = DB::table('mst_activity_group')
                ->join('mst_activity_role_ref', 'mst_activity_role_ref.activity_group_id', '=', 'mst_activity_group.id')
                ->where('mst_activity_role_ref.role_id', $id)
                ->select('mst_activity_group.id', 'mst_activity_group.name')
                ->get();

            return response()->json([
                'message' => 'Activity groups updated successfully',
                'role' => $role,
                'activity_groups' => $activityGroups,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update activity groups',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




}