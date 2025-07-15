<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccommodationController extends Controller
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
        $query = \App\Models\Accommodation::with(['appointment.patient', 'appointment.provider', 'room.center', 'mealOption']);

        // Filter accommodations based on user role
        if ($user->role === 'Patient') {
            // Patients can only see their own accommodations
            $query->whereHas('appointment', function ($q) use ($user) {
                $q->where('patient_id', $user->id);
            });
        }
        // Admins can see all accommodations

        // Filter by appointment if provided
        if ($request->has('appointment_id')) {
            $query->where('appointment_id', $request->appointment_id);
        }

        // Filter by room if provided
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('check_in_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('check_out_date', [$request->start_date, $request->end_date]);
            });
        }

        $accommodations = $query->orderBy('check_in_date', 'asc')->get();

        return response()->json($accommodations);
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
            'room_id' => 'required|exists:rooms,id',
            'meal_option_id' => 'nullable|exists:meal_options,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'special_requests' => 'nullable|string',
        ]);

        // Check if the appointment exists and if the user is authorized
        $appointment = \App\Models\Appointment::findOrFail($request->appointment_id);

        if ($user->role === 'Patient' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized to book accommodation for this appointment'], 403);
        }

        // Check if an accommodation already exists for this appointment
        $existingAccommodation = \App\Models\Accommodation::where('appointment_id', $request->appointment_id)->first();
        if ($existingAccommodation) {
            return response()->json(['error' => 'An accommodation already exists for this appointment'], 400);
        }

        // Check if the room is available for the requested dates
        $room = \App\Models\Room::findOrFail($request->room_id);

        if (!$room->is_available) {
            return response()->json(['error' => 'The selected room is not available'], 400);
        }

        // Check if the room is already booked for the requested dates
        $conflictingBookings = \App\Models\Accommodation::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('check_in_date', [$request->check_in_date, $request->check_out_date])
                    ->orWhereBetween('check_out_date', [$request->check_in_date, $request->check_out_date])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('check_in_date', '<=', $request->check_in_date)
                          ->where('check_out_date', '>=', $request->check_out_date);
                    });
            })
            ->where('status', '!=', 'cancelled')
            ->count();

        if ($conflictingBookings > 0) {
            return response()->json(['error' => 'The room is not available for the selected dates'], 400);
        }

        // Check if the number of guests exceeds the room capacity
        if ($request->number_of_guests > $room->capacity) {
            return response()->json(['error' => 'The number of guests exceeds the room capacity'], 400);
        }

        // Calculate the total price
        $checkInDate = new \Carbon\Carbon($request->check_in_date);
        $checkOutDate = new \Carbon\Carbon($request->check_out_date);
        $numberOfNights = $checkOutDate->diffInDays($checkInDate);

        $totalPrice = $room->price_per_night * $numberOfNights;

        // Add meal option price if selected
        if ($request->has('meal_option_id') && $request->meal_option_id) {
            $mealOption = \App\Models\MealOption::findOrFail($request->meal_option_id);
            $totalPrice += $mealOption->price * $numberOfNights * $request->number_of_guests;
        }

        // Create the accommodation
        $accommodation = \App\Models\Accommodation::create([
            'appointment_id' => $request->appointment_id,
            'room_id' => $request->room_id,
            'meal_option_id' => $request->meal_option_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'number_of_guests' => $request->number_of_guests,
            'total_price' => $totalPrice,
            'status' => 'reserved',
            'special_requests' => $request->special_requests,
        ]);

        // Load relationships
        $accommodation->load(['appointment.patient', 'appointment.provider', 'room.center', 'mealOption']);

        return response()->json($accommodation, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $accommodation = \App\Models\Accommodation::with(['appointment.patient', 'appointment.provider', 'room.center', 'mealOption'])->findOrFail($id);

        $user = auth()->user();

        // Check if the user has permission to view this accommodation
        if ($user->role === 'Patient' && $accommodation->appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($accommodation);
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
        $accommodation = \App\Models\Accommodation::with(['appointment', 'room'])->findOrFail($id);
        $user = auth()->user();

        // Check if the user has permission to update this accommodation
        if ($user->role === 'Patient' && $accommodation->appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'meal_option_id' => 'nullable|exists:meal_options,id',
            'check_in_date' => 'sometimes|date|after_or_equal:today',
            'check_out_date' => 'sometimes|date|after:check_in_date',
            'number_of_guests' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string|in:reserved,checked-in,checked-out,cancelled',
            'special_requests' => 'nullable|string',
        ]);

        // If dates or room are being changed, check for conflicts
        if ($request->has('check_in_date') || $request->has('check_out_date')) {
            $checkInDate = $request->check_in_date ?? $accommodation->check_in_date;
            $checkOutDate = $request->check_out_date ?? $accommodation->check_out_date;

            // Check if the room is already booked for the requested dates
            $conflictingBookings = \App\Models\Accommodation::where('room_id', $accommodation->room_id)
                ->where('id', '!=', $id)
                ->where(function ($query) use ($checkInDate, $checkOutDate) {
                    $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                        ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                        ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                            $q->where('check_in_date', '<=', $checkInDate)
                              ->where('check_out_date', '>=', $checkOutDate);
                        });
                })
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($conflictingBookings > 0) {
                return response()->json(['error' => 'The room is not available for the selected dates'], 400);
            }
        }

        // If number of guests is being changed, check room capacity
        if ($request->has('number_of_guests') && $request->number_of_guests > $accommodation->room->capacity) {
            return response()->json(['error' => 'The number of guests exceeds the room capacity'], 400);
        }

        // Recalculate total price if necessary
        if ($request->has('check_in_date') || $request->has('check_out_date') || $request->has('meal_option_id') || $request->has('number_of_guests')) {
            $checkInDate = new \Carbon\Carbon($request->check_in_date ?? $accommodation->check_in_date);
            $checkOutDate = new \Carbon\Carbon($request->check_out_date ?? $accommodation->check_out_date);
            $numberOfNights = $checkOutDate->diffInDays($checkInDate);
            $numberOfGuests = $request->number_of_guests ?? $accommodation->number_of_guests;

            $totalPrice = $accommodation->room->price_per_night * $numberOfNights;

            // Add meal option price if selected
            $mealOptionId = $request->meal_option_id ?? $accommodation->meal_option_id;
            if ($mealOptionId) {
                $mealOption = \App\Models\MealOption::findOrFail($mealOptionId);
                $totalPrice += $mealOption->price * $numberOfNights * $numberOfGuests;
            }

            $request->merge(['total_price' => $totalPrice]);
        }

        $accommodation->update($request->all());

        // Load relationships
        $accommodation->load(['appointment.patient', 'appointment.provider', 'room.center', 'mealOption']);

        return response()->json($accommodation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $accommodation = \App\Models\Accommodation::findOrFail($id);
        $user = auth()->user();

        // Check if the user has permission to delete this accommodation
        if ($user->role === 'Patient' && $accommodation->appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // If the accommodation is already checked-in, don't allow deletion
        if ($accommodation->status === 'checked-in') {
            return response()->json(['error' => 'Cannot cancel an accommodation that is already checked-in'], 400);
        }

        // Instead of deleting, mark as cancelled
        $accommodation->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Accommodation cancelled successfully']);
    }
}
