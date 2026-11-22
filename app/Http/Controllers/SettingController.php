<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $query = Setting::query();

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $settings = $query->get();
        return response()->json($settings);
    }

    public function getByKey(string $key)
    {
        $setting = Setting::where('key', $key)->first();
        return response()->json($setting);
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:settings,key',
            'value' => 'nullable|string',
            'category' => 'sometimes|string',
        ]);

        $setting = Setting::updateOrCreate(
            ['key' => $request->key],
            ['value' => $request->value, 'category' => $request->category ?? 'general']
        );

        return response()->json($setting, 201);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'category' => $request->category ?? 'general']
            );
        }

        return response()->json(['message' => 'Settings updated']);
    }
}
