<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicProvider implements AIProviderInterface
{
    private string $defaultBaseUrl = 'https://api.anthropic.com/v1';

    public function getId(): string
    {
        return 'anthropic';
    }

    public function getName(): string
    {
        return 'Anthropic (Claude)';
    }

    public function getDefaultModel(): string
    {
        return 'claude-3-5-sonnet-20241022';
    }

    public function getAvailableModels(): array
    {
        return [
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (State of the Art)',
            'claude-3-5-haiku-20241022'  => 'Claude 3.5 Haiku (Fast & Lightweight)',
            'claude-3-opus-20240229'     => 'Claude 3 Opus (Complex Analysis)',
        ];
    }

    public function chat(string $systemPrompt, string $userMessage, array $history = [], array $options = []): string
    {
        $apiKey      = $options['apiKey'] ?? config('services.anthropic.key', '');
        $model       = $options['model'] ?? $this->getDefaultModel();
        $temperature = (float) ($options['temperature'] ?? 0.7);
        $maxTokens   = (int) ($options['maxTokens'] ?? 600);
        $baseUrl     = !empty($options['endpoint']) ? rtrim($options['endpoint'], '/') : $this->defaultBaseUrl;

        if (empty($apiKey) || $apiKey === 'your_anthropic_api_key_here') {
            throw new \RuntimeException('Anthropic API key is not configured.');
        }

        $messages = [];
        foreach (array_slice($history, -6) as $turn) {
            $role = ($turn['role'] ?? '') === 'bot' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $turn['text'] ?? ''];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model'       => $model,
            'system'      => $systemPrompt,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temperature,
        ];

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ])
                ->post("{$baseUrl}/messages", $payload);

            if ($response->failed()) {
                $error = $response->json('error.message', 'Unknown Anthropic error');
                Log::warning("Anthropic API error [{$response->status()}]: {$error}");
                throw new \RuntimeException("Anthropic API error: {$error}");
            }

            $content = $response->json('content.0.text');
            if (empty(trim($content))) {
                throw new \RuntimeException('Anthropic returned an empty response.');
            }

            return trim($content);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Anthropic connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Anthropic connection failed: ' . $e->getMessage());
        }
    }

    public function testConnection(string $apiKey, string $model, ?string $endpoint = null): array
    {
        if (empty($apiKey) || $apiKey === 'your_anthropic_api_key_here') {
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
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ])
                ->post("{$baseUrl}/messages", [
                    'model'      => $model,
                    'messages'   => [
                        ['role' => 'user', 'content' => 'Ping. Respond with ONE word: "Operational".']
                    ],
                    'max_tokens' => 10,
                ]);

            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $reply = trim($response->json('content.0.text') ?? 'Operational');
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
                'message'    => "Anthropic error: {$errorMsg}",
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
