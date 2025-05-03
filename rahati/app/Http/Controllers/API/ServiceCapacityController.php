<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServiceCapacityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = \App\Models\ServiceCapacity::with('center');

        // Filter by center if provided
        if ($request->has('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        // Filter by service type if provided
        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Filter by date if provided
        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $serviceCapacities = $query->orderBy('date', 'asc')->get();

        return response()->json($serviceCapacities);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Only Admin can create service capacities
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'center_id' => 'required|exists:centers,id',
            'service_type' => 'required|string|max:50',
            'max_capacity' => 'required|integer|min:1',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after:start_time',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        // Check if a capacity rule already exists for this center, service type, date, and time range
        $existingCapacity = \App\Models\ServiceCapacity::where('center_id', $request->center_id)
            ->where('service_type', $request->service_type)
            ->where('date', $request->date);

        if ($request->has('start_time') && $request->has('end_time')) {
            $existingCapacity->where('start_time', $request->start_time)
                ->where('end_time', $request->end_time);
        } else {
            $existingCapacity->whereNull('start_time')
                ->whereNull('end_time');
        }

        if ($existingCapacity->exists()) {
            return response()->json(['error' => 'A capacity rule already exists for this center, service type, date, and time range'], 400);
        }

        $serviceCapacity = \App\Models\ServiceCapacity::create($request->all());

        // Load the center relationship
        $serviceCapacity->load('center');

        return response()->json($serviceCapacity, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $serviceCapacity = \App\Models\ServiceCapacity::with('center')->findOrFail($id);
        return response()->json($serviceCapacity);
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
        // Only Admin can update service capacities
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $serviceCapacity = \App\Models\ServiceCapacity::findOrFail($id);

        $request->validate([
            'max_capacity' => 'sometimes|integer|min:1',
            'date' => 'sometimes|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after:start_time',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        // If date or time range is being changed, check for conflicts
        if ($request->has('date') || $request->has('start_time') || $request->has('end_time')) {
            $date = $request->date ?? $serviceCapacity->date;
            $startTime = $request->start_time ?? $serviceCapacity->start_time;
            $endTime = $request->end_time ?? $serviceCapacity->end_time;

            // Check if a capacity rule already exists for this center, service type, date, and time range
            $existingCapacity = \App\Models\ServiceCapacity::where('center_id', $serviceCapacity->center_id)
                ->where('service_type', $serviceCapacity->service_type)
                ->where('date', $date)
                ->where('id', '!=', $id);

            if ($startTime && $endTime) {
                $existingCapacity->where('start_time', $startTime)
                    ->where('end_time', $endTime);
            } else {
                $existingCapacity->whereNull('start_time')
                    ->whereNull('end_time');
            }

            if ($existingCapacity->exists()) {
                return response()->json(['error' => 'A capacity rule already exists for this center, service type, date, and time range'], 400);
            }
        }

        $serviceCapacity->update($request->all());

        // Load the center relationship
        $serviceCapacity->load('center');

        return response()->json($serviceCapacity);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        // Only Admin can delete service capacities
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $serviceCapacity = \App\Models\ServiceCapacity::findOrFail($id);
        $serviceCapacity->delete();

        return response()->json(['message' => 'Service capacity deleted successfully']);
    }
}
