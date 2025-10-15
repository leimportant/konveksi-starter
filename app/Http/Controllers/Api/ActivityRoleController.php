<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ActivityRoleController extends Controller
{
    public function index(Request $request)
    {
        $groupMenu = $request->query('group_menu');
        $query = ActivityRole::latest();
        if ($groupMenu) {
            $query = $query->where('group_menu', $groupMenu);
        }
        $roles = $query->paginate(10);
        return response()->json($roles);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:mst_activity_role',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $role = ActivityRole::create($validated);
        return response()->json($role, 201);
    }

    public function show(ActivityRole $activityRole)
    {
        return response()->json($activityRole);
    }

    public function update(Request $request, ActivityRole $activityRole)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('mst_activity_role')->ignore($activityRole->id)],
        ]);

        $validated['updated_by'] = Auth::id();
        
        $activityRole->update($validated);
        return response()->json($activityRole);
    }

    public function destroy(ActivityRole $activityRole)
    {
        $activityRole->deleted_by = Auth::id();
        $activityRole->save();
        $activityRole->delete();
        return response()->json(null, 204);
    }
}