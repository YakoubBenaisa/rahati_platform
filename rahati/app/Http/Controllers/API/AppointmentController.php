<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Center;
use App\Models\ServiceCapacity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
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
        $query = Appointment::with(['patient', 'provider', 'center']);

        // Filter appointments based on user role
        if ($user->role === 'Patient') {
            // Patients can only see their own appointments
            $query->where('patient_id', $user->id);
        } elseif ($user->role === 'Provider') {
            // Providers can only see appointments assigned to them
            $query->where('provider_id', $user->id);
        } elseif ($user->isAdmin() && !$user->isSuperuser() && $user->center_id) {
            // Regular admins can only see appointments for their center
            $query->where('center_id', $user->center_id);
        }
        // Superusers can see all appointments

        // Filter by center if provided
        if ($request->has('center_id')) {
            // For regular admins, ensure they can only filter within their assigned center
            if ($user->isAdmin() && !$user->isSuperuser() && $user->center_id && $request->center_id != $user->center_id) {
                return response()->json(['error' => 'Unauthorized to view appointments for this center'], 403);
            }
            $query->where('center_id', $request->center_id);
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('appointment_datetime', [$request->start_date, $request->end_date]);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('appointment_datetime', 'asc')->get();

        return response()->json($appointments);
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
            'patient_id' => 'required|exists:users,id',
            'center_id' => 'required|exists:centers,id',
            'provider_id' => 'nullable|exists:users,id',
            'appointment_datetime' => 'required|date|after:now',
            'appointment_duration' => 'required|integer|min:15',
            'notes' => 'nullable|string',
        ]);

        // Check if the patient_id is the authenticated user or if the user is an Admin/Superuser
        if (!$user->isAdminOrSuperuser() && $request->patient_id != $user->id) {
            return response()->json(['error' => 'Unauthorized to book for another patient'], 403);
        }

        // For regular admins, check if they're trying to book for their center
        if ($user->isAdmin() && !$user->isSuperuser() && $user->center_id && $request->center_id != $user->center_id) {
            return response()->json(['error' => 'Unauthorized to book for this center'], 403);
        }

        // Check if the provider exists and has the Provider role
        if ($request->has('provider_id')) {
            $provider = User::findOrFail($request->provider_id);
            if ($provider->role !== 'Provider') {
                return response()->json(['error' => 'Selected user is not a provider'], 400);
            }
        }

        // Check if the center is active
        $center = Center::findOrFail($request->center_id);
        if (!$center->is_active) {
            return response()->json(['error' => 'Selected center is not active'], 400);
        }

        // Check for service capacity
        $appointmentDate = new Carbon($request->appointment_datetime);
        $capacity = ServiceCapacity::where('center_id', $request->center_id)
            ->where('service_type', 'appointment')
            ->where('date', $appointmentDate->format('Y-m-d'))
            ->where('is_active', true)
            ->first();

        if ($capacity) {
            // Count existing appointments for the same date and time range
            $existingAppointments = Appointment::where('center_id', $request->center_id)
                ->whereDate('appointment_datetime', $appointmentDate->format('Y-m-d'))
                ->count();

            if ($existingAppointments >= $capacity->max_capacity) {
                return response()->json(['error' => 'No available slots for the selected date and time'], 400);
            }
        }

        // Create the appointment
        $appointment = Appointment::create([
            'patient_id' => $request->patient_id,
            'center_id' => $request->center_id,
            'provider_id' => $request->provider_id,
            'appointment_datetime' => $request->appointment_datetime,
            'appointment_duration' => $request->appointment_duration,
            'status' => 'scheduled',
            'notes' => $request->notes,
        ]);

        // Load relationships
        $appointment->load(['patient', 'provider', 'center']);

        return response()->json($appointment, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $appointment = Appointment::with(['patient', 'provider', 'center', 'consultation', 'payment', 'accommodation'])->findOrFail($id);

        $user = auth()->user();

        // Check if the user has permission to view this appointment
        if ($user->role === 'Patient' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->role === 'Provider' && $appointment->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->isAdmin() && !$user->isSuperuser() && $user->center_id != $appointment->center_id) {
            // Regular admins can only view appointments for their center
            return response()->json(['error' => 'Unauthorized to view this appointment'], 403);
        }

        return response()->json($appointment);
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
        $appointment = Appointment::findOrFail($id);
        $user = auth()->user();

        // Check if the user has permission to update this appointment
        if ($user->role === 'Patient' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->role === 'Provider' && $appointment->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->isAdmin() && !$user->isSuperuser() && $user->center_id != $appointment->center_id) {
            // Regular admins can only update appointments for their center
            return response()->json(['error' => 'Unauthorized to update this appointment'], 403);
        }

        // Validate the request
        $request->validate([
            'provider_id' => 'nullable|exists:users,id',
            'appointment_datetime' => 'sometimes|date|after:now',
            'appointment_duration' => 'sometimes|integer|min:15',
            'status' => 'sometimes|string|in:scheduled,completed,cancelled,no-show',
            'notes' => 'nullable|string',
        ]);

        // Only Admin, Superuser or Provider can change the provider
        if ($request->has('provider_id') && $user->role === 'Patient') {
            return response()->json(['error' => 'Unauthorized to change provider'], 403);
        }

        // Check if the provider exists and has the Provider role
        if ($request->has('provider_id')) {
            $provider = User::findOrFail($request->provider_id);
            if ($provider->role !== 'Provider') {
                return response()->json(['error' => 'Selected user is not a provider'], 400);
            }
        }

        // Update the appointment
        $appointment->update($request->all());

        // Load relationships
        $appointment->load(['patient', 'provider', 'center']);

        return response()->json($appointment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $appointment = Appointment::findOrFail($id);
        $user = auth()->user();

        // Check if the user has permission to delete this appointment
        if ($user->role === 'Patient' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->role === 'Provider' && $appointment->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->isAdmin() && !$user->isSuperuser() && $user->center_id != $appointment->center_id) {
            // Regular admins can only delete appointments for their center
            return response()->json(['error' => 'Unauthorized to delete this appointment'], 403);
        }

        // Check if the appointment has related records
        if ($appointment->consultation()->exists() || $appointment->payment()->exists() || $appointment->accommodation()->exists()) {
            // Instead of deleting, mark as cancelled
            $appointment->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Appointment cancelled successfully']);
        }

        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully']);
    }
}
