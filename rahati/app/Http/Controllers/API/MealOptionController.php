<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MealOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = \App\Models\MealOption::query();

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by dietary preferences if provided
        if ($request->has('is_vegetarian')) {
            $query->where('is_vegetarian', $request->boolean('is_vegetarian'));
        }

        if ($request->has('is_vegan')) {
            $query->where('is_vegan', $request->boolean('is_vegan'));
        }

        if ($request->has('is_gluten_free')) {
            $query->where('is_gluten_free', $request->boolean('is_gluten_free'));
        }

        $mealOptions = $query->get();

        return response()->json($mealOptions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Only Admin can create meal options
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_vegetarian' => 'sometimes|boolean',
            'is_vegan' => 'sometimes|boolean',
            'is_gluten_free' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $mealOption = \App\Models\MealOption::create($request->all());

        return response()->json($mealOption, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $mealOption = \App\Models\MealOption::findOrFail($id);
        return response()->json($mealOption);
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
        // Only Admin can update meal options
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $mealOption = \App\Models\MealOption::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'is_vegetarian' => 'sometimes|boolean',
            'is_vegan' => 'sometimes|boolean',
            'is_gluten_free' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $mealOption->update($request->all());

        return response()->json($mealOption);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        // Only Admin can delete meal options
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $mealOption = \App\Models\MealOption::findOrFail($id);

        // Check if the meal option is used in any accommodations
        if ($mealOption->accommodations()->count() > 0) {
            // Instead of deleting, mark as inactive
            $mealOption->update(['is_active' => false]);
            return response()->json(['message' => 'Meal option deactivated successfully']);
        }

        $mealOption->delete();

        return response()->json(['message' => 'Meal option deleted successfully']);
    }
}
