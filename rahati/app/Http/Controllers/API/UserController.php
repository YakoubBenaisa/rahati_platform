<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Center;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = auth()->user();

        // Only Admin or Superuser can see users
        if (!$user->isAdminOrSuperuser()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Regular admins can only see users from their center
        if ($user->isAdmin() && !$user->isSuperuser() && $user->center_id) {
            // Get all patients and providers
            $generalUsers = User::where(function($query) {
                $query->where('role', 'Patient')
                      ->orWhere('role', 'Provider');
            })->get();

            // Get admins from their center only
            $centerAdmins = User::where('role', 'Admin')
                ->where('center_id', $user->center_id)
                ->get();

            // Combine the collections
            $users = $generalUsers->merge($centerAdmins);
        } else {
            // Superusers can see all users
            $users = User::all();
        }

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();

        // Only Admin or Superuser can create users (other than patients)
        if (!$currentUser->isAdminOrSuperuser() && $request->input('role', 'Patient') !== 'Patient') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Regular admins cannot create superusers
        if ($currentUser->isAdmin() && !$currentUser->isSuperuser() && $request->input('role') === 'Superuser') {
            return response()->json(['error' => 'Unauthorized to create superuser accounts'], 403);
        }

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

        // For Admin users, center_id is required
        if ($request->input('role') === 'Admin' && !$request->has('center_id')) {
            return response()->json(['error' => 'Center ID is required for Admin users'], 422);
        }

        // Regular admins can only assign users to their own center
        if ($currentUser->isAdmin() && !$currentUser->isSuperuser() &&
            $request->has('center_id') && $request->center_id != $currentUser->center_id) {
            return response()->json(['error' => 'Unauthorized to assign users to other centers'], 403);
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
        if ($request->input('role') === 'Admin' && $request->has('center_id')) {
            $userData['center_id'] = $request->center_id;
        }

        $user = User::create($userData);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        $currentUser = auth()->user();

        // Users can see their own profile
        if (auth()->id() == $id) {
            return response()->json($user);
        }

        // Admins and superusers can see any user
        if ($currentUser->isAdminOrSuperuser()) {
            return response()->json($user);
        }

        // Providers can see their patients
        if ($currentUser->isProvider() && $user->isPatient()) {
            // Check if this patient is assigned to the provider
            $isPatientOfProvider = Appointment::where('provider_id', $currentUser->id)
                ->where('patient_id', $user->id)
                ->exists();

            if ($isPatientOfProvider) {
                return response()->json($user);
            }
        }

        // Otherwise, unauthorized
        return response()->json(['error' => 'Unauthorized'], 403);
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
        $user = User::findOrFail($id);
        $currentUser = auth()->user();

        // Users can update their own profile
        if (auth()->id() == $id) {
            // Continue with update
        }
        // Admins and superusers can update any user
        else if ($currentUser->isAdminOrSuperuser()) {
            // Continue with update
        }
        // Providers can update their patients' information
        else if ($currentUser->isProvider() && $user->isPatient()) {
            // Check if this patient is assigned to the provider
            $isPatientOfProvider = Appointment::where('provider_id', $currentUser->id)
                ->where('patient_id', $user->id)
                ->exists();

            if (!$isPatientOfProvider) {
                return response()->json(['error' => 'Unauthorized. This patient is not assigned to you.'], 403);
            }

            // Providers can only update specific fields for patients
            $allowedFields = ['name', 'phone', 'address', 'caregiver_name', 'caregiver_phone'];
            $providedFields = array_keys($request->all());

            foreach ($providedFields as $field) {
                if (!in_array($field, $allowedFields)) {
                    return response()->json(['error' => "Providers cannot update the '{$field}' field for patients"], 403);
                }
            }
        }
        // Otherwise, unauthorized
        else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only Admin or Superuser can change roles
        if ($request->has('role') && !$currentUser->isAdminOrSuperuser()) {
            return response()->json(['error' => 'Unauthorized to change role'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:Patient,Provider,Admin',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'caregiver_name' => 'sometimes|string|max:255',
            'caregiver_phone' => 'sometimes|string|max:20',
        ]);

        // Handle password separately
        if ($request->has('password')) {
            $request->merge([
                'password' => bcrypt($request->password)
            ]);
        }

        $user->update($request->all());

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        // Only Admin can delete users
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Get patients assigned to the authenticated provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myPatients(Request $request)
    {
        $user = auth()->user();

        // Only providers can access this endpoint
        if ($user->role !== 'Provider') {
            return response()->json(['error' => 'Unauthorized. Only providers can access this endpoint'], 403);
        }

        // Get unique patient IDs from appointments where this provider is assigned
        $patientIds = Appointment::where('provider_id', $user->id)
            ->select('patient_id')
            ->distinct()
            ->pluck('patient_id');

        // Get the patient users
        $patients = User::whereIn('id', $patientIds)->get();

        return response()->json($patients);
    }

    /**
     * Get detailed information about patients assigned to the authenticated provider,
     * including their appointments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myPatientsDetailed(Request $request)
    {
        $user = auth()->user();

        // Only providers can access this endpoint
        if ($user->role !== 'Provider') {
            return response()->json(['error' => 'Unauthorized. Only providers can access this endpoint'], 403);
        }

        // Get all appointments for this provider with patient information
        $appointments = Appointment::with(['patient', 'center', 'consultation'])
            ->where('provider_id', $user->id)
            ->get();

        // Group appointments by patient
        $patientData = [];
        foreach ($appointments as $appointment) {
            $patientId = $appointment->patient_id;

            if (!isset($patientData[$patientId])) {
                $patientData[$patientId] = [
                    'patient' => $appointment->patient,
                    'appointments' => []
                ];
            }

            $patientData[$patientId]['appointments'][] = [
                'id' => $appointment->id,
                'datetime' => $appointment->appointment_datetime,
                'duration' => $appointment->appointment_duration,
                'status' => $appointment->status,
                'center' => $appointment->center ? [
                    'id' => $appointment->center->id,
                    'name' => $appointment->center->name
                ] : null,
                'has_consultation' => $appointment->consultation ? true : false
            ];
        }

        return response()->json(array_values($patientData));
    }

    /**
     * Get detailed information about a specific patient assigned to the authenticated provider,
     * including their appointments.
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function patientDetails(string $id, Request $request)
    {
        $currentUser = auth()->user();
        $patient = User::findOrFail($id);

        // Only providers can access this endpoint
        if (!$currentUser->isProvider()) {
            return response()->json(['error' => 'Unauthorized. Only providers can access this endpoint'], 403);
        }

        // Check if the patient is actually a patient
        if (!$patient->isPatient()) {
            return response()->json(['error' => 'The specified user is not a patient'], 400);
        }

        // Check if this patient is assigned to the provider
        $isPatientOfProvider = Appointment::where('provider_id', $currentUser->id)
            ->where('patient_id', $patient->id)
            ->exists();

        if (!$isPatientOfProvider) {
            return response()->json(['error' => 'Unauthorized. This patient is not assigned to you.'], 403);
        }

        // Get all appointments for this patient with this provider
        $appointments = Appointment::with(['center', 'consultation'])
            ->where('provider_id', $currentUser->id)
            ->where('patient_id', $patient->id)
            ->orderBy('appointment_datetime', 'desc')
            ->get();

        $appointmentData = $appointments->map(function($appointment) {
            return [
                'id' => $appointment->id,
                'datetime' => $appointment->appointment_datetime,
                'duration' => $appointment->appointment_duration,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'center' => $appointment->center ? [
                    'id' => $appointment->center->id,
                    'name' => $appointment->center->name,
                    'address' => $appointment->center->address
                ] : null,
                'consultation' => $appointment->consultation ? [
                    'id' => $appointment->consultation->id,
                    'diagnosis' => $appointment->consultation->diagnosis,
                    'treatment_plan' => $appointment->consultation->treatment_plan,
                    'notes' => $appointment->consultation->notes,
                    'created_at' => $appointment->consultation->created_at
                ] : null
            ];
        });

        $result = [
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'address' => $patient->address,
                'caregiver_name' => $patient->caregiver_name,
                'caregiver_phone' => $patient->caregiver_phone
            ],
            'appointments' => $appointmentData
        ];

        return response()->json($result);
    }
}
