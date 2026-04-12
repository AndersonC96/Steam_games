<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services\Contracts;

use Anderson\SteamGames\Models\GameCollection;

interface SteamApiInterface
{
    /**
     * @throws \Exception If user not found or other API errors
     */
    public function getUserGameDetails(string $username, string $apiKey): GameCollection;
}
