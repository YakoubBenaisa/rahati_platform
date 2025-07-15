<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
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
        $query = \App\Models\Notification::where('user_id', $user->id);

        // Filter by read status if provided
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Get notifications with newest first
        $notifications = $query->orderBy('created_at', 'desc')->get();

        return response()->json($notifications);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Only Admin can create notifications
        if (auth()->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
        ]);

        $notification = \App\Models\Notification::create([
            'user_id' => $request->user_id,
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'data' => $request->data,
            'is_read' => false,
        ]);

        return response()->json($notification, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $notification = \App\Models\Notification::findOrFail($id);
        $user = auth()->user();

        // Users can only see their own notifications
        if ($notification->user_id !== $user->id && $user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($notification);
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
        $notification = \App\Models\Notification::findOrFail($id);
        $user = auth()->user();

        // Users can only update their own notifications
        if ($notification->user_id !== $user->id && $user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Regular users can only mark as read
        if ($user->role !== 'Admin') {
            $request->validate([
                'is_read' => 'required|boolean',
            ]);

            if ($request->has('is_read') && $request->boolean('is_read')) {
                $notification->markAsRead();
            } else {
                $notification->update(['is_read' => false, 'read_at' => null]);
            }
        } else {
            // Admin validation
            $request->validate([
                'type' => 'sometimes|string|max:50',
                'title' => 'sometimes|string|max:255',
                'message' => 'sometimes|string',
                'data' => 'nullable|array',
                'is_read' => 'sometimes|boolean',
            ]);

            $notification->update($request->all());

            // Handle read_at timestamp
            if ($request->has('is_read')) {
                if ($request->boolean('is_read')) {
                    $notification->read_at = $notification->read_at ?? now();
                } else {
                    $notification->read_at = null;
                }
                $notification->save();
            }
        }

        return response()->json($notification);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $notification = \App\Models\Notification::findOrFail($id);
        $user = auth()->user();

        // Users can only delete their own notifications
        if ($notification->user_id !== $user->id && $user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    /**
     * Mark a notification as read.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(string $id)
    {
        $notification = \App\Models\Notification::findOrFail($id);
        $user = auth()->user();

        // Users can only mark their own notifications as read
        if ($notification->user_id !== $user->id && $user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json($notification);
    }
}
