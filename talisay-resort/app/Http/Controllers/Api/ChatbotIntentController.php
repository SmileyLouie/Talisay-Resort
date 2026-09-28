<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ChatbotIntent;
use App\Models\ChatbotLog;
use Illuminate\Http\Request;

class ChatbotIntentController extends Controller
{
    public function index(Request $request)
    {
        $query = ChatbotIntent::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('keyword', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->orderBy('category')->orderBy('keyword')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'keyword'   => 'required|string|max:255',
            'response'  => 'required|string|max:2000',
            'category'  => 'required|in:' . implode(',', ChatbotIntent::CATEGORIES),
            'is_active' => 'sometimes|boolean',
        ]);

        $intent = ChatbotIntent::create($data + ['is_active' => $request->boolean('is_active', true)]);
        AuditLog::log('chatbot_intent_created', $intent, null, $intent->toArray());

        return response()->json(['intent' => $intent, 'message' => 'Intent created successfully.'], 201);
    }

    public function show(ChatbotIntent $chatbotIntent)
    {
        return response()->json($chatbotIntent);
    }

    public function update(Request $request, ChatbotIntent $chatbotIntent)
    {
        $data = $request->validate([
            'keyword'   => 'sometimes|string|max:255',
            'response'  => 'sometimes|string|max:2000',
            'category'  => 'sometimes|in:' . implode(',', ChatbotIntent::CATEGORIES),
            'is_active' => 'sometimes|boolean',
        ]);

        $old = $chatbotIntent->toArray();
        $chatbotIntent->update($data);
        AuditLog::log('chatbot_intent_updated', $chatbotIntent, $old, $chatbotIntent->toArray());

        return response()->json(['intent' => $chatbotIntent->fresh(), 'message' => 'Intent updated.']);
    }

    public function destroy(ChatbotIntent $chatbotIntent)
    {
        $old = $chatbotIntent->toArray();
        $chatbotIntent->delete();
        AuditLog::log('chatbot_intent_deleted', null, $old, null);

        return response()->json(['message' => 'Intent deleted.']);
    }

    public function logs(Request $request)
    {
        $query = ChatbotLog::with('user:id,name,role');

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }
}
