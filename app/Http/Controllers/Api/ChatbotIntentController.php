<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotIntent;
use App\Models\ChatbotLog;
use Illuminate\Http\Request;

class ChatbotIntentController extends Controller
{
    public function index(Request $request)
    {
        $query = ChatbotIntent::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('search')) {
            $query->where('keyword', 'like', "%{$request->search}%");
        }

        return response()->json($query->orderBy('category')->paginate(20));
    }

    public function store(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'response' => 'required|string',
            'category' => 'required|in:rates,hours,policies,directions,facilities,booking_help,general',
            'is_active' => 'boolean',
        ]);

        $intent = ChatbotIntent::create($request->all());

        return response()->json(['intent' => $intent, 'message' => 'Intent created successfully.'], 201);
    }

    public function show(ChatbotIntent $chatbotIntent)
    {
        return response()->json($chatbotIntent);
    }

    public function update(Request $request, ChatbotIntent $chatbotIntent)
    {
        $request->validate([
            'keyword' => 'sometimes|string|max:255',
            'response' => 'sometimes|string',
            'category' => 'sometimes|in:rates,hours,policies,directions,facilities,booking_help,general',
            'is_active' => 'sometimes|boolean',
        ]);

        $chatbotIntent->update($request->all());

        return response()->json(['intent' => $chatbotIntent->fresh(), 'message' => 'Intent updated.']);
    }

    public function destroy(ChatbotIntent $chatbotIntent)
    {
        $chatbotIntent->delete();
        return response()->json(['message' => 'Intent deleted.']);
    }

    public function logs(Request $request)
    {
        $query = ChatbotLog::with('user');

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }
}
