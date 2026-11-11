<?php

namespace App\Support;

class UiLocales
{
    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            'en' => 'English',
            'fil' => 'Filipino',
            'ceb' => 'Cebuano',
            'es' => 'Español',
            'zh' => '中文',
            'ja' => '日本語',
            'ko' => '한국어',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'pt' => 'Português',
            'it' => 'Italiano',
            'ru' => 'Русский',
            'ar' => 'العربية',
            'hi' => 'हिन्दी',
            'id' => 'Bahasa Indonesia',
            'ms' => 'Bahasa Melayu',
            'th' => 'ไทย',
            'vi' => 'Tiếng Việt',
        ];
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function allows(string $code): bool
    {
        return in_array($code, self::codes(), true);
    }
}
