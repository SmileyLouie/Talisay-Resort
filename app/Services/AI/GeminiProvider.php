<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    private string $defaultBaseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function getId(): string
    {
        return 'gemini';
    }

    public function getName(): string
    {
        return 'Google Gemini';
    }

    public function getDefaultModel(): string
    {
        return 'gemini-1.5-flash';
    }

    public function getAvailableModels(): array
    {
        return [
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast, Recommended)',
            'gemini-1.5-pro'   => 'Gemini 1.5 Pro (High Reasoning)',
            'gemini-2.0-flash' => 'Gemini 2.0 Flash (Next-Gen Fast)',
            'gemini-3.6-flash' => 'Gemini 3.6 Flash (Preview)',
        ];
    }

    public function chat(string $systemPrompt, string $userMessage, array $history = [], array $options = []): string
    {
        $apiKey      = $options['apiKey'] ?? config('services.gemini.key', '');
        $model       = $options['model'] ?? $this->getDefaultModel();
        $temperature = (float) ($options['temperature'] ?? 0.7);
        $maxTokens   = (int) ($options['maxTokens'] ?? 600);
        $baseUrl     = !empty($options['endpoint']) ? rtrim($options['endpoint'], '/') : $this->defaultBaseUrl;

        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            throw new \RuntimeException('Google Gemini API key is not configured.');
        }

        // Build conversation turns
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

        $endpoint = "{$baseUrl}/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => $maxTokens,
                'topP'            => 0.9,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ],
        ];

        try {
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

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Gemini connection failed: ' . $this->redact($e->getMessage()));
            throw new \RuntimeException('Gemini connection failed.');
        }
    }

    public function testConnection(string $apiKey, string $model, ?string $endpoint = null): array
    {
        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            return [
                'success'    => false,
                'message'    => 'API Key cannot be empty.',
                'latency_ms' => 0,
            ];
        }

        $baseUrl  = !empty($endpoint) ? rtrim($endpoint, '/') : $this->defaultBaseUrl;
        $url      = "{$baseUrl}/{$model}:generateContent?key={$apiKey}";
        $start    = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => 'Ping. Respond with ONE word: "Operational".']]],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 10,
                        'temperature'     => 0.1,
                    ],
                ]);

            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $parts = $response->json('candidates.0.content.parts', []);
                $reply = trim($parts[0]['text'] ?? 'Operational');
                return [
                    'success'      => true,
                    'message'      => "Connection successful! Model '{$model}' responded in {$latency}ms.",
                    'latency_ms'   => $latency,
                    'sample_reply' => $reply,
                ];
            }

            $errorMsg = $response->json('error.message') ?? "HTTP {$response->status()} - {$response->body()}";
            return [
                'success'    => false,
                'message'    => "Gemini error: {$errorMsg}",
                'latency_ms' => $latency,
            ];

        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            return [
                'success'    => false,
                'message'    => 'Connection failed: ' . $this->redact($e->getMessage()),
                'latency_ms' => $latency,
            ];
        }
    }

    private function redact(string $message): string
    {
        return preg_replace('/key=[^&\s]+/', 'key=redacted', $message) ?? $message;
    }
}
