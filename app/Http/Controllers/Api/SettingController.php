<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = Setting::all();
        return response()->json($settings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setting $setting)
    {
        $request->validate([
            'value' => 'required|string|max:200',
        ]);

        $setting->value = $request->value;
        $setting->save();

        return response()->json($setting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        //
    }

    public function getData(Request $request)
    {
        $settings = Setting::where('key', $request->key)->first();
        return response()->json(['message' => $settings->value]);
    }
}
