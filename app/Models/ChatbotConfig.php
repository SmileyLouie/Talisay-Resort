<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ChatbotConfig
 *
 * Stores the active AI Chatbot configuration (provider, model, credentials, parameters, behavior).
 * Supports modular providers: Google Gemini, OpenAI, Anthropic, or Custom OpenAI-compatible endpoints.
 * API key is encrypted using AES-256 via Laravel's encrypted cast.
 */
class ChatbotConfig extends Model
{
    use HasFactory;

    protected $table = 'chatbot_configs';

    protected $fillable = [
        'provider',
        'model',
        'api_key',
        'api_endpoint',
        'system_prompt',
        'temperature',
        'max_tokens',
        'is_enabled',
        'response_language',
        'personality',
        'welcome_message',
        'fallback_message',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    /**
     * The api_key is hidden from arrays and JSON serialization
     * to prevent accidental leakage in frontend responses.
     */
    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key'          => 'encrypted',
            'is_enabled'       => 'boolean',
            'temperature'      => 'float',
            'max_tokens'       => 'integer',
            'last_tested_at'   => 'datetime',
        ];
    }

    /**
     * Retrieve the active singleton configuration instance,
     * creating a default configuration if none exists.
     */
    public static function current(): self
    {
        $config = static::first();

        if (!$config) {
            $defaultKey = config('services.gemini.key');
            if ($defaultKey === 'your_gemini_api_key_here') {
                $defaultKey = null;
            }

            $config = static::create([
                'provider'          => 'gemini',
                'model'             => config('services.gemini.model', 'gemini-1.5-flash'),
                'api_key'           => $defaultKey,
                'api_endpoint'      => null,
                'system_prompt'     => "You are the official Talisay Beach Resort AI tourism assistant in Baybay City, Leyte, Philippines. Help visitors, tourists, and staff with resort information, room rates, cottage availability, facilities, policies, and booking inquiries. Be courteous, concise, and helpful. Never invent information not available in the system.",
                'temperature'       => 0.70,
                'max_tokens'        => 600,
                'is_enabled'        => true,
                'response_language' => 'en',
                'personality'       => 'friendly',
                'welcome_message'   => SystemSetting::get('chatbot_welcome', 'Welcome to Talisay Beach Resort! How can I help you today?'),
                'fallback_message'  => SystemSetting::get('chatbot_fallback', "I'm not sure about that. Would you like to speak with our resort front desk staff?"),
            ]);
        }

        return $config;
    }

    /**
     * Check if a valid API key is present.
     */
    public function hasApiKey(): bool
    {
        $raw = $this->api_key;
        if (empty($raw)) {
            return false;
        }

        $placeholders = [
            'your_gemini_api_key_here',
            'your_openai_api_key_here',
            'your_anthropic_api_key_here',
            '••••••••••••••••',
        ];

        return !in_array($raw, $placeholders, true);
    }

    /**
     * Mask the stored API key for UI display (never expose plain text).
     */
    public function getMaskedApiKeyAttribute(): string
    {
        if (!$this->hasApiKey()) {
            return '';
        }

        $key = (string) $this->api_key;
        $len = strlen($key);

        if ($len <= 8) {
            return '••••••••••••••••';
        }

        // e.g. "sk-••••••••••••abcd"
        $prefix = substr($key, 0, 4);
        $suffix = substr($key, -4);
        return $prefix . '••••••••••••' . $suffix;
    }

    /**
     * Check if the AI engine is enabled and ready to handle requests.
     */
    public function isOperational(): bool
    {
        return $this->is_enabled && $this->hasApiKey();
    }
}
