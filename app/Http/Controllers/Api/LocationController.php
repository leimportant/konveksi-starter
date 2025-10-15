<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    /**
     * Display a listing of the locations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Location::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%')
            ->orWhere('id', 'like', '%' . $request->name . '%');
        }

        $perPage = $request->input('perPage', 10);
        $locations = $query->paginate($perPage);

        
        return response($locations, 200);
    }


    /**
     * Store a newly created location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:mst_location,name',
        ]);

        $location = new Location();
        $location->name = $request->name;
        $location->created_by = Auth::id();
        $location->save();

        return response($location, 201);
    }

    /**
     * Display the specified location.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function show(Location $location)
    {
        return response($location);
    }

    /**
     * Update the specified location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:mst_location,name,' . $location->id,
        ]);

        $location->name = $request->name;
        $location->updated_by = Auth::id();
        $location->save();

        return response($location);
    }

    /**
     * Remove the specified location from storage.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function destroy(Location $location)
    {
        $location->deleted_by = Auth::id();
        $location->save();
        $location->delete();

        return response()->noContent();
    }

    
    public function getLocations()
    {
        $user = Auth::user();
        $locationId = $user ? $user->location_id : null;

        $query = $locationId ? Location::find($locationId) : null;

        return response()->json($query);
    }
}