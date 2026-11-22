<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount(['conversations', 'tickets'])
            ->orderBy('display_order', 'asc')
            ->get();
        return response()->json($departments);
    }

    public function show(string $id)
    {
        $department = Department::with(['conversations.user', 'tickets'])->findOrFail($id);
        return response()->json($department);
    }

    public function getBySlug(string $slug)
    {
        $department = Department::with(['conversations.user', 'tickets'])->where('slug', $slug)->firstOrFail();
        return response()->json($department);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:departments,slug',
            'description' => 'nullable|string',
            'email' => 'nullable|email',
            'color' => 'sometimes|string',
            'display_order' => 'sometimes|integer',
        ]);

        $department = Department::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'description' => $request->description,
            'email' => $request->email,
            'color' => $request->color ?? '#3b82f6',
            'display_order' => $request->display_order ?? 0,
        ]);

        return response()->json($department, 201);
    }

    public function update(Request $request, string $id)
    {
        $department = Department::findOrFail($id);
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:departments,slug,' . $id,
            'description' => 'nullable|string',
            'email' => 'nullable|email',
            'color' => 'sometimes|string',
            'display_order' => 'sometimes|integer',
        ]);
        $department->update($request->only(['name', 'slug', 'description', 'email', 'color', 'display_order']));
        return response()->json($department);
    }

    public function destroy(string $id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
        return response()->json(['message' => 'Department deleted']);
    }
}
