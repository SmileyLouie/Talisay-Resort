<?php

namespace App\Services;

use App\Models\ChatbotConfig;
use App\Services\AI\AIProviderInterface;
use App\Services\AI\AnthropicProvider;
use App\Services\AI\CustomCompatibleProvider;
use App\Services\AI\GeminiProvider;
use App\Services\AI\OpenAIProvider;
use Illuminate\Support\Facades\Log;

/**
 * ChatbotAIService
 *
 * Central orchestration service for the Talisay Resort AI Chatbot.
 * Routes chat requests to the currently active modular provider (Gemini, OpenAI, Anthropic, Custom),
 * manages encrypted credentials, and provides connection health testing.
 */
class ChatbotAIService
{
    /** @var array<string, AIProviderInterface> */
    private array $providers = [];

    public function __construct()
    {
        $this->registerProvider(new GeminiProvider());
        $this->registerProvider(new OpenAIProvider());
        $this->registerProvider(new AnthropicProvider());
        $this->registerProvider(new CustomCompatibleProvider());
    }

    public function registerProvider(AIProviderInterface $provider): void
    {
        $this->providers[$provider->getId()] = $provider;
    }

    /**
     * Get the current database configuration.
     */
    public function getConfig(): ChatbotConfig
    {
        return ChatbotConfig::current();
    }

    /**
     * Check if the active provider is configured and operational.
     */
    public function isConfigured(): bool
    {
        $config = $this->getConfig();
        return $config->isOperational();
    }

    /**
     * Get an AI provider instance by ID (or the currently configured active provider).
     */
    public function getProvider(?string $providerId = null): AIProviderInterface
    {
        $id = $providerId ?: $this->getConfig()->provider;
        return $this->providers[$id] ?? $this->providers['gemini'];
    }

    /**
     * Get metadata for all available providers to populate admin forms.
     */
    public function getProviders(): array
    {
        $list = [];
        foreach ($this->providers as $id => $provider) {
            $list[$id] = [
                'id'             => $id,
                'name'           => $provider->getName(),
                'default_model'  => $provider->getDefaultModel(),
                'models'         => $provider->getAvailableModels(),
            ];
        }
        return $list;
    }

    /**
     * Send a message through the active AI provider.
     */
    public function chat(string $systemPrompt, string $userMessage, array $history = [], array $options = []): string
    {
        $config = $this->getConfig();

        if (!$config->is_enabled) {
            throw new \RuntimeException('AI Chatbot is currently disabled in System Settings.');
        }

        $providerId = $options['provider'] ?? $config->provider;
        $provider   = $this->getProvider($providerId);

        $mergedOptions = array_merge([
            'apiKey'      => $config->api_key,
            'model'       => $config->model ?: $provider->getDefaultModel(),
            'endpoint'    => $config->api_endpoint,
            'temperature' => (float) $config->temperature,
            'maxTokens'   => (int) $config->max_tokens,
        ], $options);

        // Enhance system prompt with language and personality instructions
        $enhancedPrompt = $this->buildFullPrompt($systemPrompt, $config);

        return $provider->chat($enhancedPrompt, $userMessage, $history, $mergedOptions);
    }

    /**
     * Test connection credentials against an AI provider.
     */
    public function testConnection(string $providerId, ?string $apiKey = null, ?string $model = null, ?string $endpoint = null): array
    {
        $config = $this->getConfig();
        $provider = $this->getProvider($providerId);

        // If no new API key is provided, use the existing encrypted key from the database
        $keyToUse = $apiKey;
        if (empty($keyToUse) || $keyToUse === '••••••••••••••••') {
            $keyToUse = $config->api_key;
        }

        $modelToUse    = $model ?: ($config->model ?: $provider->getDefaultModel());
        $endpointToUse = $endpoint !== null ? $endpoint : $config->api_endpoint;

        $result = $provider->testConnection($keyToUse, $modelToUse, $endpointToUse);

        // Record the test result timestamp on the config
        try {
            $config->update([
                'last_tested_at'    => now(),
                'last_test_status'  => $result['success'] ? 'success' : 'failed',
                'last_test_message' => $result['message'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed updating chatbot test status: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Assemble system prompt with administrator instructions, personality, and language directives.
     */
    private function buildFullPrompt(string $roleContextPrompt, ChatbotConfig $config): string
    {
        $directives = [];

        // 1. Core Persona & Administrator Custom Instructions
        if (!empty($config->system_prompt)) {
            $directives[] = "ADMINISTRATOR CORE DIRECTIVE:\n" . trim($config->system_prompt);
        }

        // 2. Personality directive
        $tone = match ($config->personality) {
            'professional' => "TONE: Professional, formal, courteous, and precise.",
            'casual'       => "TONE: Friendly, upbeat, conversational, warm, and casual.",
            default        => "TONE: Welcoming, helpful, hospitable, and warm Filipino resort hospitality.",
        };
        $directives[] = $tone;

        // 3. Language directive
        $lang = match ($config->response_language) {
            'fil'   => "LANGUAGE: Respond primarily in Filipino / Tagalog (or Taglish where natural).",
            'ceb'   => "LANGUAGE: Respond in Cebuano / Bisaya (the local language of Baybay, Leyte) or English as appropriate.",
            'auto'  => "LANGUAGE: Match the language of the user's message (English, Tagalog, or Cebuano).",
            default => "LANGUAGE: Respond clearly and politely in English.",
        };
        $directives[] = $lang;

        // 4. Role-specific context (guest, tourist, staff, admin)
        $directives[] = "ROLE-SPECIFIC CONTEXT & CONSTRAINTS:\n" . trim($roleContextPrompt);

        return implode("\n\n", $directives);
    }
}
