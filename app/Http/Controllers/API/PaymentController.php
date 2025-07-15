<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
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
        $query = \App\Models\Payment::with(['appointment.patient', 'appointment.provider', 'appointment.center']);

        // Filter payments based on user role
        if ($user->role === 'Patient') {
            // Patients can only see their own payments
            $query->whereHas('appointment', function ($q) use ($user) {
                $q->where('patient_id', $user->id);
            });
        } elseif ($user->role === 'Provider') {
            // Providers can only see payments for their appointments
            $query->whereHas('appointment', function ($q) use ($user) {
                $q->where('provider_id', $user->id);
            });
        }
        // Admins can see all payments

        // Filter by appointment if provided
        if ($request->has('appointment_id')) {
            $query->where('appointment_id', $request->appointment_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method if provided
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return response()->json($payments);
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
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,completed,failed,refunded',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Check if the appointment exists
        $appointment = \App\Models\Appointment::findOrFail($request->appointment_id);

        // Check if the user is authorized to make a payment for this appointment
        if ($user->role === 'Patient' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to make payment for this appointment'], 403);
        }

        // Check if a payment already exists for this appointment
        $existingPayment = \App\Models\Payment::where('appointment_id', $request->appointment_id)->first();
        if ($existingPayment) {
            return response()->json(['error' => 'A payment already exists for this appointment'], 400);
        }

        // Create the payment
        $payment = \App\Models\Payment::create([
            'appointment_id' => $request->appointment_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'status' => $request->status ?? 'pending',
            'payment_date' => $request->payment_date ?? now(),
            'notes' => $request->notes,
        ]);

        // Load relationships
        $payment->load(['appointment.patient', 'appointment.provider', 'appointment.center']);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $payment = \App\Models\Payment::with(['appointment.patient', 'appointment.provider', 'appointment.center'])->findOrFail($id);

        $user = auth()->user();

        // Check if the user has permission to view this payment
        if ($user->role === 'Patient' && $payment->appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->role === 'Provider' && $payment->appointment->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($payment);
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
        $payment = \App\Models\Payment::with('appointment')->findOrFail($id);
        $user = auth()->user();

        // Only Admins can update payments
        if ($user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|string',
            'transaction_id' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,completed,failed,refunded',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Update the payment
        $payment->update($request->all());

        // Load relationships
        $payment->load(['appointment.patient', 'appointment.provider', 'appointment.center']);

        return response()->json($payment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $payment = \App\Models\Payment::findOrFail($id);
        $user = auth()->user();

        // Only Admins can delete payments
        if ($user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
