<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotIntent;
use App\Models\ChatbotLog;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'session_id' => 'required|string',
        ]);

        $message = strtolower(trim($request->message));
        $matchedIntent = null;
        $response = "I'm not sure about that. Would you like to speak with our staff? They can help you better.";
        $suggestedChips = ['Rates', 'Hours', 'Directions', 'Book Now', 'Talk to Staff'];

        // Search for matching intent using keyword matching
        $intents = ChatbotIntent::active()->get();

        foreach ($intents as $intent) {
            $keywords = array_map('trim', explode(',', strtolower($intent->keyword)));
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword) || $this->fuzzyMatch($message, $keyword)) {
                    $matchedIntent = $intent;
                    $response = $intent->response;
                    break 2;
                }
            }
        }

        // Generate suggested follow-up chips based on category
        if ($matchedIntent) {
            $suggestedChips = $this->getFollowUpChips($matchedIntent->category);
        }

        // Log the conversation
        ChatbotLog::create([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session_id,
            'message' => $request->message,
            'response' => $response,
            'intent' => $matchedIntent?->keyword,
        ]);

        return response()->json([
            'response' => $response,
            'intent' => $matchedIntent?->category,
            'chips' => $suggestedChips,
            'escalate' => $matchedIntent === null,
        ]);
    }

    private function fuzzyMatch(string $input, string $keyword): bool
    {
        if (strlen($keyword) < 3) return false;
        similar_text($input, $keyword, $percent);
        return $percent > 70;
    }

    private function getFollowUpChips(string $category): array
    {
        $chips = [
            'rates' => ['Day Tour Rate', 'Overnight Rate', 'Group Package', 'Add-ons'],
            'hours' => ['Opening Hours', 'Check-in Time', 'Check-out Time', 'Best Time to Visit'],
            'policies' => ['Cancellation Policy', 'Refund Policy', 'Pet Policy', 'Age Policy'],
            'directions' => ['From Tacloban', 'From Ormoc', 'Parking Info', 'Map'],
            'facilities' => ['Cottages', 'Restrooms', 'Restaurant', 'Water Sports'],
            'booking_help' => ['How to Book', 'Payment Methods', 'Availability', 'Modify Booking'],
            'general' => ['Rates', 'Hours', 'Directions', 'Book Now'],
        ];

        return $chips[$category] ?? $chips['general'];
    }
}
