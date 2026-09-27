<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
{
    private string $defaultBaseUrl = 'https://api.openai.com/v1';

    public function getId(): string
    {
        return 'openai';
    }

    public function getName(): string
    {
        return 'OpenAI (ChatGPT)';
    }

    public function getDefaultModel(): string
    {
        return 'gpt-4o-mini';
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-4o-mini'    => 'GPT-4o Mini (Affordable, Ultra Fast)',
            'gpt-4o'         => 'GPT-4o (High Intelligence, Omni)',
            'gpt-4-turbo'    => 'GPT-4 Turbo',
            'gpt-3.5-turbo'  => 'GPT-3.5 Turbo',
            'o1-mini'        => 'o1 Mini (Reasoning)',
        ];
    }

    public function chat(string $systemPrompt, string $userMessage, array $history = [], array $options = []): string
    {
        $apiKey      = $options['apiKey'] ?? config('services.openai.key', '');
        $model       = $options['model'] ?? $this->getDefaultModel();
        $temperature = (float) ($options['temperature'] ?? 0.7);
        $maxTokens   = (int) ($options['maxTokens'] ?? 600);
        $baseUrl     = !empty($options['endpoint']) ? rtrim($options['endpoint'], '/') : $this->defaultBaseUrl;

        if (empty($apiKey) || $apiKey === 'your_openai_api_key_here') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        // Messages payload
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

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                $error = $response->json('error.message', 'Unknown OpenAI error');
                Log::warning("OpenAI API error [{$response->status()}]: {$error}");
                throw new \RuntimeException("OpenAI API error: {$error}");
            }

            $content = $response->json('choices.0.message.content');
            if (empty(trim($content))) {
                throw new \RuntimeException('OpenAI returned an empty response.');
            }

            return trim($content);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('OpenAI connection failed: ' . $e->getMessage());
            throw new \RuntimeException('OpenAI connection failed: ' . $e->getMessage());
        }
    }

    public function testConnection(string $apiKey, string $model, ?string $endpoint = null): array
    {
        if (empty($apiKey) || $apiKey === 'your_openai_api_key_here') {
            return [
                'success'    => false,
                'message'    => 'API Key cannot be empty.',
                'latency_ms' => 0,
            ];
        }

        $baseUrl = !empty($endpoint) ? rtrim($endpoint, '/') : $this->defaultBaseUrl;
        $start   = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$baseUrl}/chat/completions", [
                    'model'      => $model,
                    'messages'   => [
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
                'message'    => "OpenAI error: {$errorMsg}",
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
