<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConsultationController extends Controller
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
        $query = \App\Models\Consultation::with(['appointment.patient', 'appointment.provider', 'appointment.center']);

        // Filter consultations based on user role
        if ($user->role === 'Patient') {
            // Patients can only see their own consultations
            $query->whereHas('appointment', function ($q) use ($user) {
                $q->where('patient_id', $user->id);
            });
        } elseif ($user->role === 'Provider') {
            // Providers can only see consultations assigned to them
            $query->whereHas('appointment', function ($q) use ($user) {
                $q->where('provider_id', $user->id);
            });
        }
        // Admins can see all consultations

        // Filter by appointment if provided
        if ($request->has('appointment_id')) {
            $query->where('appointment_id', $request->appointment_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $consultations = $query->orderBy('start_time', 'desc')->get();

        return response()->json($consultations);
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

        // Only Providers and Admins can create consultations
        if ($user->role === 'Patient') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'provider_notes' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'status' => 'sometimes|string|in:in-progress,completed,cancelled',
        ]);

        // Check if the appointment exists and if the user is authorized
        $appointment = \App\Models\Appointment::findOrFail($request->appointment_id);

        if ($user->role === 'Provider' && $appointment->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to create consultation for this appointment'], 403);
        }

        // Check if a consultation already exists for this appointment
        $existingConsultation = \App\Models\Consultation::where('appointment_id', $request->appointment_id)->first();
        if ($existingConsultation) {
            return response()->json(['error' => 'A consultation already exists for this appointment'], 400);
        }

        // Create the consultation
        $consultation = \App\Models\Consultation::create([
            'appointment_id' => $request->appointment_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'provider_notes' => $request->provider_notes,
            'diagnosis' => $request->diagnosis,
            'treatment_plan' => $request->treatment_plan,
            'status' => $request->status ?? 'in-progress',
        ]);

        // Update the appointment status if consultation is completed
        if ($request->status === 'completed') {
            $appointment->update(['status' => 'completed']);
        }

        // Load relationships
        $consultation->load(['appointment.patient', 'appointment.provider', 'appointment.center']);

        return response()->json($consultation, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $consultation = \App\Models\Consultation::with(['appointment.patient', 'appointment.provider', 'appointment.center'])->findOrFail($id);

        $user = auth()->user();

        // Check if the user has permission to view this consultation
        if ($user->role === 'Patient' && $consultation->appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->role === 'Provider' && $consultation->appointment->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($consultation);
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
        $consultation = \App\Models\Consultation::with('appointment')->findOrFail($id);
        $user = auth()->user();

        // Only Providers and Admins can update consultations
        if ($user->role === 'Patient') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Providers can only update their own consultations
        if ($user->role === 'Provider' && $consultation->appointment->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to update this consultation'], 403);
        }

        $request->validate([
            'end_time' => 'nullable|date|after:start_time',
            'provider_notes' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'status' => 'sometimes|string|in:in-progress,completed,cancelled',
        ]);

        // Update the consultation
        $consultation->update($request->all());

        // Update the appointment status if consultation is completed
        if ($request->status === 'completed') {
            $consultation->appointment->update(['status' => 'completed']);
        }

        // Load relationships
        $consultation->load(['appointment.patient', 'appointment.provider', 'appointment.center']);

        return response()->json($consultation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $consultation = \App\Models\Consultation::with('appointment')->findOrFail($id);
        $user = auth()->user();

        // Only Admins can delete consultations
        if ($user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update the appointment status if consultation is deleted
        $consultation->appointment->update(['status' => 'scheduled']);

        $consultation->delete();

        return response()->json(['message' => 'Consultation deleted successfully']);
    }
}
