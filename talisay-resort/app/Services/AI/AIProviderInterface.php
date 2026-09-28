<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    /**
     * Unique identifier for the provider (e.g. 'gemini', 'openai', 'anthropic', 'custom').
     */
    public function getId(): string;

    /**
     * Human-readable name (e.g. 'Google Gemini', 'OpenAI ChatGPT', 'Anthropic Claude').
     */
    public function getName(): string;

    /**
     * Default model identifier for this provider.
     */
    public function getDefaultModel(): string;

    /**
     * List of popular/recommended models supported by this provider.
     *
     * @return array<string, string> Key = model id, Value = display label
     */
    public function getAvailableModels(): array;

    /**
     * Send a conversation message and return the model's text response.
     *
     * @param  string  $systemPrompt  System instructions defining persona and role limits
     * @param  string  $userMessage   Current user query
     * @param  array   $history       Conversation turns: [['role'=>'user'|'bot','text'=>'...']]
     * @param  array   $options       Parameters: ['apiKey', 'model', 'endpoint', 'temperature', 'maxTokens']
     * @return string                 AI response text
     *
     * @throws \RuntimeException      If the request fails
     */
    public function chat(string $systemPrompt, string $userMessage, array $history = [], array $options = []): string;

    /**
     * Send a lightweight probe to verify credentials and endpoint reachability.
     *
     * @param  string       $apiKey    API Key to validate
     * @param  string       $model     Model identifier
     * @param  string|null  $endpoint  Optional custom endpoint URL
     * @return array{success: bool, message: string, latency_ms: int, sample_reply?: string}
     */
    public function testConnection(string $apiKey, string $model, ?string $endpoint = null): array;
}
