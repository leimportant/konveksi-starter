<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
}