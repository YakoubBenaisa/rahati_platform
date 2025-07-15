<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransportationRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = \App\Models\TransportationRequest::with(['user', 'appointment']);

        // Filter transportation requests based on user role
        if ($user->role === 'Patient') {
            // Patients can only see their own transportation requests
            $query->where('user_id', $user->id);
        }
        // Admins can see all transportation requests

        // Filter by appointment if provided
        if ($request->has('appointment_id')) {
            $query->where('appointment_id', $request->appointment_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date if provided
        if ($request->has('date')) {
            $query->whereDate('pickup_time', $request->date);
        }

        $transportationRequests = $query->orderBy('pickup_time', 'asc')->get();

        return response()->json($transportationRequests);
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

        $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'pickup_location' => 'required|string',
            'dropoff_location' => 'required|string',
            'pickup_time' => 'required|date|after:now',
            'transportation_type' => 'sometimes|string',
            'number_of_passengers' => 'sometimes|integer|min:1',
            'special_instructions' => 'nullable|string',
        ]);

        // If appointment_id is provided, check if the user is authorized
        if ($request->has('appointment_id') && $request->appointment_id) {
            $appointment = \App\Models\Appointment::findOrFail($request->appointment_id);

            if ($user->role === 'Patient' && $appointment->patient_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized to request transportation for this appointment'], 403);
            }
        }

        // Create the transportation request
        $transportationRequest = \App\Models\TransportationRequest::create([
            'user_id' => $user->id,
            'appointment_id' => $request->appointment_id,
            'pickup_location' => $request->pickup_location,
            'dropoff_location' => $request->dropoff_location,
            'pickup_time' => $request->pickup_time,
            'transportation_type' => $request->transportation_type ?? 'standard',
            'number_of_passengers' => $request->number_of_passengers ?? 1,
            'status' => 'pending',
            'special_instructions' => $request->special_instructions,
        ]);

        // Load relationships
        $transportationRequest->load(['user', 'appointment']);

        return response()->json($transportationRequest, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $transportationRequest = \App\Models\TransportationRequest::with(['user', 'appointment'])->findOrFail($id);

        $user = auth()->user();

        // Check if the user has permission to view this transportation request
        if ($user->role === 'Patient' && $transportationRequest->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($transportationRequest);
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
        $transportationRequest = \App\Models\TransportationRequest::findOrFail($id);
        $user = auth()->user();

        // Check if the user has permission to update this transportation request
        if ($user->role === 'Patient' && $transportationRequest->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Patients can only update certain fields
        if ($user->role === 'Patient') {
            $request->validate([
                'pickup_location' => 'sometimes|string',
                'dropoff_location' => 'sometimes|string',
                'pickup_time' => 'sometimes|date|after:now',
                'transportation_type' => 'sometimes|string',
                'number_of_passengers' => 'sometimes|integer|min:1',
                'special_instructions' => 'nullable|string',
            ]);

            // Patients cannot change the status
            if ($request->has('status')) {
                return response()->json(['error' => 'Unauthorized to change status'], 403);
            }
        } else {
            // Admin validation
            $request->validate([
                'pickup_location' => 'sometimes|string',
                'dropoff_location' => 'sometimes|string',
                'pickup_time' => 'sometimes|date|after:now',
                'transportation_type' => 'sometimes|string',
                'number_of_passengers' => 'sometimes|integer|min:1',
                'status' => 'sometimes|string|in:pending,confirmed,completed,cancelled',
                'special_instructions' => 'nullable|string',
            ]);
        }

        $transportationRequest->update($request->all());

        // Load relationships
        $transportationRequest->load(['user', 'appointment']);

        return response()->json($transportationRequest);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $transportationRequest = \App\Models\TransportationRequest::findOrFail($id);
        $user = auth()->user();

        // Check if the user has permission to delete this transportation request
        if ($user->role === 'Patient' && $transportationRequest->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // If the transportation request is already confirmed or completed, don't allow deletion
        if (in_array($transportationRequest->status, ['confirmed', 'completed'])) {
            return response()->json(['error' => 'Cannot cancel a transportation request that is already confirmed or completed'], 400);
        }

        // Instead of deleting, mark as cancelled
        $transportationRequest->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Transportation request cancelled successfully']);
    }
}
