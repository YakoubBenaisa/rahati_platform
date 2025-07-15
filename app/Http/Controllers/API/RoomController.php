<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = \App\Models\Room::with('center');

        // Filter by center if provided
        if ($request->has('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        // Filter by room type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by availability if provided
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Filter by accessibility if provided
        if ($request->has('is_accessible')) {
            $query->where('is_accessible', $request->boolean('is_accessible'));
        }

        // Filter by capacity if provided
        if ($request->has('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        // Filter by price range if provided
        if ($request->has('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        $rooms = $query->get();

        return response()->json($rooms);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Only Admin can create rooms
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'center_id' => 'required|exists:centers,id',
            'room_number' => 'required|string|max:20',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'price_per_night' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'is_accessible' => 'sometimes|boolean',
            'is_available' => 'sometimes|boolean',
        ]);

        // Check if the room number already exists in the center
        $existingRoom = \App\Models\Room::where('center_id', $request->center_id)
            ->where('room_number', $request->room_number)
            ->first();

        if ($existingRoom) {
            return response()->json(['error' => 'Room number already exists in this center'], 400);
        }

        $room = \App\Models\Room::create($request->all());

        // Load the center relationship
        $room->load('center');

        return response()->json($room, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $room = \App\Models\Room::with(['center', 'accommodations' => function ($query) {
            // Only include future accommodations
            $query->where('check_in_date', '>=', now()->format('Y-m-d'));
        }])->findOrFail($id);

        return response()->json($room);
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
        // Only Admin can update rooms
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $room = \App\Models\Room::findOrFail($id);

        $request->validate([
            'room_number' => 'sometimes|string|max:20',
            'type' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'price_per_night' => 'sometimes|numeric|min:0',
            'capacity' => 'sometimes|integer|min:1',
            'is_accessible' => 'sometimes|boolean',
            'is_available' => 'sometimes|boolean',
        ]);

        // If room number is being changed, check for uniqueness in the center
        if ($request->has('room_number') && $request->room_number !== $room->room_number) {
            $existingRoom = \App\Models\Room::where('center_id', $room->center_id)
                ->where('room_number', $request->room_number)
                ->first();

            if ($existingRoom) {
                return response()->json(['error' => 'Room number already exists in this center'], 400);
            }
        }

        $room->update($request->all());

        // Load the center relationship
        $room->load('center');

        return response()->json($room);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        // Only Admin can delete rooms
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $room = \App\Models\Room::findOrFail($id);

        // Check if the room has any accommodations
        if ($room->accommodations()->count() > 0) {
            // Instead of deleting, mark as unavailable
            $room->update(['is_available' => false]);
            return response()->json(['message' => 'Room marked as unavailable successfully']);
        }

        $room->delete();

        return response()->json(['message' => 'Room deleted successfully']);
    }
}
