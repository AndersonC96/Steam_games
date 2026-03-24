<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Support;

final class TextHelper
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function parsePrice(string $value): float
    {
        if ($value === 'Preço não disponível') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) preg_replace('/[^0-9,]/', '', $value));
    }

    public static function cleanDescription(string $text, int $maxLength = 180): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim((string) preg_replace('/\s+/', ' ', strip_tags($decoded)));

        if ($plain === '') {
            return 'Descrição não disponível';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($plain) > $maxLength ? mb_substr($plain, 0, $maxLength) . '...' : $plain;
        }

        return strlen($plain) > $maxLength ? substr($plain, 0, $maxLength) . '...' : $plain;
    }
}
