<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FeedbackController extends Controller
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
        $query = \App\Models\Feedback::with(['user', 'appointment.provider', 'appointment.center']);

        // Filter feedback based on user role
        if ($user->role === 'Patient') {
            // Patients can only see their own feedback or public feedback
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('is_public', true);
            });
        } elseif ($user->role === 'Provider') {
            // Providers can see feedback for their appointments or public feedback
            $query->where(function ($q) use ($user) {
                $q->whereHas('appointment', function ($q2) use ($user) {
                    $q2->where('provider_id', $user->id);
                })->orWhere('is_public', true);
            });
        }
        // Admins can see all feedback

        // Filter by appointment if provided
        if ($request->has('appointment_id')) {
            $query->where('appointment_id', $request->appointment_id);
        }

        // Filter by rating if provided
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by public status if provided
        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        $feedback = $query->orderBy('created_at', 'desc')->get();

        return response()->json($feedback);
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
            'appointment_id' => 'required|exists:appointments,id',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string',
            'is_anonymous' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
        ]);

        // Check if the appointment exists and if the user is authorized
        $appointment = \App\Models\Appointment::findOrFail($request->appointment_id);

        if ($appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to provide feedback for this appointment'], 403);
        }

        // Check if the appointment is completed
        if ($appointment->status !== 'completed') {
            return response()->json(['error' => 'Cannot provide feedback for an appointment that is not completed'], 400);
        }

        // Check if feedback already exists for this appointment
        $existingFeedback = \App\Models\Feedback::where('appointment_id', $request->appointment_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingFeedback) {
            return response()->json(['error' => 'Feedback already exists for this appointment'], 400);
        }

        // Create the feedback
        $feedback = \App\Models\Feedback::create([
            'user_id' => $user->id,
            'appointment_id' => $request->appointment_id,
            'rating' => $request->rating,
            'comments' => $request->comments,
            'is_anonymous' => $request->is_anonymous ?? false,
            'is_public' => $request->is_public ?? true,
        ]);

        // Load relationships
        $feedback->load(['user', 'appointment.provider', 'appointment.center']);

        return response()->json($feedback, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $feedback = \App\Models\Feedback::with(['user', 'appointment.provider', 'appointment.center'])->findOrFail($id);

        $user = auth()->user();

        // Check if the user has permission to view this feedback
        if ($user->role === 'Patient') {
            // Patients can only see their own feedback or public feedback
            if ($feedback->user_id !== $user->id && !$feedback->is_public) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } elseif ($user->role === 'Provider') {
            // Providers can only see feedback for their appointments or public feedback
            if ($feedback->appointment->provider_id !== $user->id && !$feedback->is_public) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }
        // Admins can see all feedback

        return response()->json($feedback);
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
        $feedback = \App\Models\Feedback::findOrFail($id);
        $user = auth()->user();

        // Check if the user has permission to update this feedback
        if ($user->role === 'Patient' && $feedback->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only the user who created the feedback or an admin can update it
        if ($user->role !== 'Admin' && $feedback->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comments' => 'nullable|string',
            'is_anonymous' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
        ]);

        $feedback->update($request->all());

        // Load relationships
        $feedback->load(['user', 'appointment.provider', 'appointment.center']);

        return response()->json($feedback);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $feedback = \App\Models\Feedback::findOrFail($id);
        $user = auth()->user();

        // Only the user who created the feedback or an admin can delete it
        if ($user->role !== 'Admin' && $feedback->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $feedback->delete();

        return response()->json(['message' => 'Feedback deleted successfully']);
    }
}
