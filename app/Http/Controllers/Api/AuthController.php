<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // We need this just for Sanctum createToken function

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)
                    ->orWhere('username', $request->email)
                    ->first();
                    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role
            ]
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'username' => 'required|string|max:100|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $hashedPassword = Hash::make($request->password);
        $role = $request->role ?? 'Usuario';

        DB::insert('INSERT INTO users (name, email, username, password, role) VALUES (?, ?, ?, ?, ?)', [
            $request->name,
            $request->email,
            $request->username,
            $hashedPassword,
            $role
        ]);

        return response()->json(['status' => 'success', 'message' => 'Usuario registrado exitosamente'], 201);
    }

    public function logout(Request $request)
    {
        // Delete all tokens for this user
        $request->user()->tokens()->delete();
        return response()->json(['status' => 'success', 'message' => 'Sesión cerrada exitosamente']);
    }

    public function listUsers(Request $request)
    {
        $users = DB::select('SELECT id, name, email, username, role, created_at FROM users ORDER BY id DESC');
        return response()->json($users);
    }

    public function me(Request $request)
    {
        return response()->json(['status' => 'success', 'user' => $request->user()]);
    }
}
