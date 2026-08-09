<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Emergency::with(['user', 'responder']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $emergencies = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($emergencies);
    }

    public function myEmergencies(Request $request)
    {
        $emergencies = Emergency::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($emergencies);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:medical,security,lost_item,other',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $data = [
            'user_id' => $request->user()->id,
            'category' => $request->category,
            'description' => $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'pending',
            'tracking_number' => Emergency::generateTrackingNumber(),
        ];

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('emergencies', 'public');
        }

        $emergency = Emergency::create($data);

        // Notify all staff
        $staff = \App\Models\User::whereIn('role', ['admin', 'staff'])->where('is_active', true)->get();
        foreach ($staff as $s) {
            \App\Models\NotificationModel::create([
                'user_id' => $s->id,
                'type' => 'emergency',
                'title' => 'Emergency Alert',
                'message' => "New {$emergency->category} emergency reported by {$request->user()->name}",
                'data' => ['emergency_id' => $emergency->id, 'tracking_number' => $emergency->tracking_number],
            ]);
        }

        broadcast(new \App\Events\EmergencyCreated($emergency));

        return response()->json([
            'emergency' => $emergency,
            'message' => 'Emergency reported successfully. Help is on the way.',
        ], 201);
    }

    public function show(Emergency $emergency)
    {
        $emergency->load(['user', 'responder']);
        return response()->json($emergency);
    }

    public function update(Request $request, Emergency $emergency)
    {
        $request->validate([
            'description' => 'sometimes|string|max:1000',
        ]);

        $emergency->update($request->only('description'));

        return response()->json(['emergency' => $emergency->fresh(), 'message' => 'Emergency updated.']);
    }

    public function updateStatus(Request $request, Emergency $emergency)
    {
        $request->validate([
            'status' => 'required|in:acknowledged,responding,resolved',
            'response_notes' => 'nullable|string',
        ]);

        $oldStatus = $emergency->status;

        $data = [
            'status' => $request->status,
            'responder_id' => $request->user()->id,
            'response_notes' => $request->response_notes,
        ];

        if ($request->status === 'resolved') {
            $data['resolved_at'] = now();
        }

        $emergency->update($data);

        // Notify the guest
        \App\Models\NotificationModel::create([
            'user_id' => $emergency->user_id,
            'type' => 'emergency_update',
            'title' => 'Emergency Update',
            'message' => "Your emergency ({$emergency->tracking_number}) status has been updated to: {$emergency->status}",
            'data' => ['emergency_id' => $emergency->id, 'status' => $emergency->status],
        ]);

        broadcast(new \App\Events\EmergencyStatusUpdated($emergency));

        AuditLog::log('emergency_status_changed', $emergency, ['status' => $oldStatus], ['status' => $emergency->status]);

        return response()->json([
            'emergency' => $emergency->fresh()->load(['user', 'responder']),
            'message' => 'Emergency status updated.',
        ]);
    }
}
