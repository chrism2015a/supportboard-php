<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->get(['id', 'email', 'name', 'role', 'is_active', 'avatar_url', 'language', 'timezone', 'last_login']);
        return response()->json($users);
    }

    public function listAgents()
    {
        $agents = User::where('role', 'agent')->where('is_active', true)
            ->get(['id', 'email', 'name', 'role', 'is_active', 'avatar_url', 'language', 'timezone', 'last_login']);

        return response()->json($agents);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string',
            'language' => 'sometimes|in:en,es,fr,de,pt,ja,zh',
            'timezone' => 'sometimes|string',
        ]);

        $user->update($request->only(['name', 'phone', 'language', 'timezone']));
        return response()->json($user);
    }

    public function updatePassword(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['message' => 'Password updated']);
    }
}
