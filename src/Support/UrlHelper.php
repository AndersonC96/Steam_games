<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Support;

final class UrlHelper
{
    /**
     * Generates a safe URL for the application, handling subdirectories.
     */
    public static function base(string $path = '', array $server = []): string
    {
        $scriptName = $server['SCRIPT_NAME'] ?? '';
        $base = str_replace('\\', '/', dirname($scriptName));

        if ($base === '/') {
            $base = '';
        }

        return $base . '/' . ltrim($path, '/');
    }

    public static function buildQuery(array $params): string
    {
        return '?' . http_build_query($params);
    }
}
