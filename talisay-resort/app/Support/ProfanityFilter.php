<?php

namespace App\Support;

/**
 * Blocks offensive words in guest reviews.
 * Latin, Filipino, Cebuano, and other spaced languages match whole words,
 * including letter spacing, punctuation, and simple number tricks (f*ck, sh1t).
 * Chinese, Japanese, Korean, and Thai match the word inside the sentence,
 * because those languages are not written with spaces.
 */
class ProfanityFilter
{
    public static function contains(?string $text): bool
    {
        if ($text === null || trim($text) === '') {
            return false;
        }

        $normalized = self::normalize($text);

        foreach (self::words() as $word) {
            if (self::usesSubstring($word)) {
                if (mb_stripos(self::compact($normalized), self::compact(self::normalize($word))) !== false) {
                    return true;
                }
                continue;
            }

            if (preg_match(self::pattern($word), $normalized)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public static function words(): array
    {
        return [
            // English
            'fuck', 'fck', 'fuk', 'fk', 'phuck', 'motherfucker', 'shit', 'sht', 'shitty', 'bullshit',
            'bitch', 'btch', 'asshole', 'bastard', 'cunt', 'dick', 'pussy', 'cock', 'whore', 'slut',
            'dumbass', 'jackass', 'retard', 'nigger', 'nigga', 'faggot', 'fag',
            // Filipino
            'putangina', 'putang ina', 'tangina', 'tang ina', 'gago', 'gaga', 'tarantado',
            'tarantada', 'ulol', 'ogag', 'bobo', 'inutil', 'pakshet', 'pakyu', 'hayop ka',
            'punyeta', 'kantot', 'iyot', 'kupal', 'hudas', 'putang',
            // Cebuano
            'yawa', 'piste', 'pisti', 'buang', 'bilat', 'atay nimo',
            // Spanish
            'puta', 'puto', 'mierda', 'cabron', 'cabrón', 'joder', 'maricon', 'maricón',
            'pendejo', 'gilipollas', 'hijo de puta', 'coño',
            // French
            'merde', 'putain', 'salope', 'connard', 'encule', 'enculé', 'nique', 'fils de pute',
            // German
            'scheisse', 'scheiße', 'fotze', 'hurensohn', 'arschloch', 'ficken', 'wichser',
            // Portuguese
            'merda', 'porra', 'caralho', 'foda', 'buceta', 'viado', 'filho da puta',
            // Italian
            'cazzo', 'stronzo', 'puttana', 'vaffanculo', 'coglione',
            // Russian
            'блядь', 'блять', 'сука', 'хуй', 'пизда', 'ебать', 'ебан', 'мудак', 'пидор',
            'blyad', 'suka', 'khuy', 'pizda',
            // Arabic
            'كس', 'شرموط', 'شرموطة', 'عرص', 'منيوك',
            // Hindi
            'madarchod', 'behenchod', 'chutiya', 'bhosdike', 'bhosdi', 'randi',
            'मादरचोद', 'बेहेनचोद', 'चूतिया', 'भोसड़ी',
            // Indonesian and Malay
            'bangsat', 'bajingan', 'kontol', 'memek', 'pukimak', 'jancok', 'jancuk', 'puki', 'lancau',
            // Chinese
            '傻逼', '操你妈', '草泥马', '婊子', '肏你',
            // Japanese
            'くそ', 'クソ', '死ね', 'ちんこ', 'まんこ', 'ファック',
            // Korean
            '씨발', '병신', '좆', '지랄', '개새끼',
            // Thai
            'ควย', 'เหี้ย', 'สัส', 'เย็ด',
            // Vietnamese
            'địt', 'đụ', 'lồn', 'cặc', 'đéo',
        ];
    }

    private static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_KD);
            if (is_string($normalized)) {
                $text = preg_replace('/\p{Mn}/u', '', $normalized) ?? $normalized;
            }
        }

        $text = str_replace(['*', '#'], '', $text);

        return strtr($text, [
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '5' => 's',
            '7' => 't',
            '@' => 'a',
            '$' => 's',
        ]);
    }

    private static function compact(string $text): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $text) ?? $text;
    }

    private static function usesSubstring(string $word): bool
    {
        return (bool) preg_match('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Thai}]/u', $word);
    }

    private static function pattern(string $word): string
    {
        $word = self::normalize($word);
        $chunks = preg_split('/\s+/u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = [];

        foreach ($chunks as $chunk) {
            $chars = preg_split('//u', $chunk, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $letters = array_map(
                fn (string $char) => preg_quote($char, '/').'+',
                $chars
            );
            $parts[] = implode('[^\p{L}\p{N}]*', $letters);
        }

        $body = implode('[^\p{L}\p{N}]+', $parts);
        $suffix = '';
        if (count($chunks) === 1 && preg_match('/^\p{Latin}+$/u', $word) && mb_strlen($word) >= 3) {
            $suffix = '(?:ing|ers|er|ed|es|s)?';
        }

        return '/(?<![\p{L}\p{N}])'.$body.$suffix.'(?![\p{L}\p{N}])/iu';
    }
}
