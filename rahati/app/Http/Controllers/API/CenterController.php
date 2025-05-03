<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\User;
use Illuminate\Http\Request;

class CenterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = auth()->user();

        // If user is a regular admin, only show their assigned center
        if ($user->isAdmin() && !$user->isSuperuser() && $user->center_id) {
            $centers = Center::where('id', $user->center_id)->get();
        } else {
            // Superusers and other roles can see all centers
            $centers = Center::all();
        }

        return response()->json($centers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Only Admin or Superuser can create centers
        if (!$user->isAdminOrSuperuser()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'nullable|boolean',
        ]);

        $center = Center::create($request->all());

        return response()->json($center, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $user = auth()->user();
        $center = Center::with(['rooms'])->findOrFail($id);

        // If user is a regular admin, check if they're assigned to this center
        if ($user->isAdmin() && !$user->isSuperuser() && $user->center_id != $center->id) {
            return response()->json(['error' => 'Unauthorized to view this center'], 403);
        }

        return response()->json($center);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();

        // Only Admin or Superuser can update centers
        if (!$user->isAdminOrSuperuser()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $center = Center::findOrFail($id);

        // If user is a regular admin, check if they're assigned to this center
        if ($user->isAdmin() && !$user->isSuperuser() && $user->center_id != $center->id) {
            return response()->json(['error' => 'Unauthorized to update this center'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'nullable|boolean',
        ]);

        $center->update($request->all());

        return response()->json($center);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $user = auth()->user();

        // Only Admin or Superuser can delete centers
        if (!$user->isAdminOrSuperuser()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $center = Center::findOrFail($id);

        // If user is a regular admin, check if they're assigned to this center
        if ($user->isAdmin() && !$user->isSuperuser() && $user->center_id != $center->id) {
            return response()->json(['error' => 'Unauthorized to delete this center'], 403);
        }

        // Check if center has related records
        if ($center->appointments()->count() > 0 || $center->rooms()->count() > 0) {
            // Soft delete by setting is_active to false
            $center->update(['is_active' => false]);
            return response()->json(['message' => 'Center deactivated successfully']);
        }

        $center->delete();

        return response()->json(['message' => 'Center deleted successfully']);
    }
}
