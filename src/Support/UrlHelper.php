<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Support;

final class UrlHelper
{
    /**
     * Generates a safe base URL for the application, handling subdirectories and environments.
     */
    public static function base(string $path = '', array $server = []): string
    {
        $scriptName = $server['SCRIPT_NAME'] ?? '';
        $base = str_replace('\\', '/', dirname($scriptName));

        // If base is root, ensure it's empty to avoid double slashes
        if ($base === '/' || $base === '.') {
            $base = '';
        }

        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Builds a query string safely. Returns empty string if params are empty.
     */
    public static function buildQuery(array $params): string
    {
        $cleanParams = array_filter($params, static function ($value): bool {
            return $value !== null && $value !== '';
        });

        if (empty($cleanParams)) {
            return '';
        }

        return '?' . http_build_query($cleanParams);
    }
}
