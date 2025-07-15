<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Login user and create token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'userId' => $user->id,
            'role' => $user->role,
        ]);
    }

    /**
     * Register a new user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string|in:Patient,Provider,Admin,Superuser',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'caregiver_name' => 'sometimes|string|max:255',
            'caregiver_phone' => 'sometimes|string|max:20',
            'center_id' => 'sometimes|exists:centers,id',
        ]);

        // Only allow Admin or Superuser roles if the request is from an authenticated admin/superuser
        if (in_array($request->role, ['Admin', 'Superuser']) && (!auth()->check() || !auth()->user()->isAdminOrSuperuser())) {
            return response()->json(['error' => 'Unauthorized to create admin or superuser accounts'], 403);
        }

        // For Admin users, center_id is required
        if ($request->role === 'Admin' && !$request->has('center_id')) {
            return response()->json(['error' => 'Center ID is required for Admin users'], 422);
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'Patient',
            'phone' => $request->phone,
            'address' => $request->address,
            'caregiver_name' => $request->caregiver_name,
            'caregiver_phone' => $request->caregiver_phone,
        ];

        // Add center_id for Admin users
        if ($request->role === 'Admin' && $request->has('center_id')) {
            $userData['center_id'] = $request->center_id;
        }

        $user = User::create($userData);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'userId' => $user->id,
            'role' => $user->role,
        ], 201);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
