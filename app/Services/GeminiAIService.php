<?php

namespace App\Services;

use App\Models\ChatbotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiAIService
 *
 * Provides backward-compatible bridge to the unified ChatbotAIService.
 * Preserves the exact signature expected by existing controllers and feature tests.
 */
class GeminiAIService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->model  = config('services.gemini.model', 'gemini-3.6-flash');
    }

    /**
     * Send a chat message with a system prompt and optional history.
     * Routes through the unified multi-provider ChatbotAIService.
     *
     * @param  string  $systemPrompt  Role-specific instructions injected as context
     * @param  string  $userMessage   The raw user message
     * @param  array   $history       Previous turns: [['role'=>'user'|'bot','text'=>'...']]
     * @return string                 AI response text
     *
     * @throws \RuntimeException      If API call fails (caller falls back to keyword matching)
     */
    public function chat(string $systemPrompt, string $userMessage, array $history = []): string
    {
        try {
            /** @var ChatbotAIService $aiService */
            $aiService = app(ChatbotAIService::class);
            return $aiService->chat($systemPrompt, $userMessage, $history);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'cURL error') || str_contains($e->getMessage(), 'Gemini connection failed')) {
                throw $e;
            }
            return $this->chatDirectGemini($systemPrompt, $userMessage, $history);
        }
    }

    /**
     * Direct standalone call to Gemini REST API.
     */
    public function chatDirectGemini(string $systemPrompt, string $userMessage, array $history = []): string
    {
        $config = ChatbotConfig::current();
        $key = $config->api_key ?: $this->apiKey;
        $model = $config->model ?: $this->model;

        if (empty($key) || in_array($key, ['your_gemini_api_key_here', '••••••••••••••••'])) {
            throw new \RuntimeException('Gemini API key not configured.');
        }

        $contents = [];
        foreach (array_slice($history, -6) as $turn) {
            $contents[] = [
                'role'  => ($turn['role'] ?? '') === 'bot' ? 'model' : 'user',
                'parts' => [['text' => $turn['text'] ?? '']],
            ];
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $endpoint = "{$this->baseUrl}/{$model}:generateContent?key={$key}";

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => $config->temperature !== null ? (float) $config->temperature : 0.7,
                'maxOutputTokens' => ((int) $config->max_tokens) ?: 600,
                'topP'            => 0.9,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ],
        ];

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, $payload);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown Gemini error');
            Log::warning("Gemini API error [{$response->status()}]: {$error}");
            throw new \RuntimeException("Gemini API error: {$error}");
        }

        $parts = $response->json('candidates.0.content.parts', []);
        $text = '';
        foreach ($parts as $part) {
            if (!empty($part['text'])) {
                $text .= $part['text'];
            }
        }

        if (empty(trim($text))) {
            throw new \RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
    }

    /**
     * Check if an active AI provider key is configured and operational.
     */
    public function isConfigured(): bool
    {
        $config = ChatbotConfig::current();
        if (!$config->is_enabled) {
            return false;
        }

        if ($config->hasApiKey()) {
            return true;
        }

        return !empty($this->apiKey) && $this->apiKey !== 'your_gemini_api_key_here';
    }
}
