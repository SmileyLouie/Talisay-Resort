<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomCompatibleProvider implements AIProviderInterface
{
    public function getId(): string
    {
        return 'custom';
    }

    public function getName(): string
    {
        return 'Custom / Compatible (Groq, OpenRouter, Ollama)';
    }

    public function getDefaultModel(): string
    {
        return 'llama-3.3-70b-versatile';
    }

    public function getAvailableModels(): array
    {
        return [
            'llama-3.3-70b-versatile' => 'Llama 3.3 70B (Fast Open-Source)',
            'mistral-large-latest'    => 'Mistral Large',
            'deepseek-chat'           => 'DeepSeek Chat',
            'qwen-2.5-72b'            => 'Qwen 2.5 72B',
            'custom-model'            => 'Custom Model Name...',
        ];
    }

    public function chat(string $systemPrompt, string $userMessage, array $history = [], array $options = []): string
    {
        $apiKey      = $options['apiKey'] ?? '';
        $model       = $options['model'] ?? $this->getDefaultModel();
        $temperature = (float) ($options['temperature'] ?? 0.7);
        $maxTokens   = (int) ($options['maxTokens'] ?? 600);
        $endpoint    = $options['endpoint'] ?? '';

        if (empty($endpoint)) {
            throw new \RuntimeException('Custom API endpoint / base URL is required.');
        }

        $baseUrl  = rtrim($endpoint, '/');
        // If user entered base without /chat/completions
        $url = str_ends_with($baseUrl, '/chat/completions') ? $baseUrl : "{$baseUrl}/chat/completions";

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        foreach (array_slice($history, -6) as $turn) {
            $role = ($turn['role'] ?? '') === 'bot' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $turn['text'] ?? ''];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];

        $headers = ['Content-Type' => 'application/json'];
        if (!empty($apiKey)) {
            $headers['Authorization'] = "Bearer {$apiKey}";
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->post($url, $payload);

            if ($response->failed()) {
                $error = $response->json('error.message', 'Unknown custom endpoint error');
                Log::warning("Custom AI API error [{$response->status()}]: {$error}");
                throw new \RuntimeException("Custom AI error: {$error}");
            }

            $content = $response->json('choices.0.message.content');
            if (empty(trim($content))) {
                throw new \RuntimeException('Custom provider returned an empty response.');
            }

            return trim($content);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Custom AI connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Custom AI connection failed: ' . $e->getMessage());
        }
    }

    public function testConnection(string $apiKey, string $model, ?string $endpoint = null): array
    {
        if (empty($endpoint)) {
            return [
                'success'    => false,
                'message'    => 'Custom API endpoint / base URL is required.',
                'latency_ms' => 0,
            ];
        }

        $baseUrl = rtrim($endpoint, '/');
        $url     = str_ends_with($baseUrl, '/chat/completions') ? $baseUrl : "{$baseUrl}/chat/completions";
        $start   = microtime(true);

        $headers = ['Content-Type' => 'application/json'];
        if (!empty($apiKey)) {
            $headers['Authorization'] = "Bearer {$apiKey}";
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($url, [
                    'model'    => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Ping. Respond with ONE word: "Operational".']
                    ],
                    'max_tokens' => 10,
                ]);

            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $reply = trim($response->json('choices.0.message.content') ?? 'Operational');
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
                'message'    => "Provider error: {$errorMsg}",
                'latency_ms' => $latency,
            ];

        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            return [
                'success'    => false,
                'message'    => 'Connection failed: ' . $e->getMessage(),
                'latency_ms' => $latency,
            ];
        }
    }
}
