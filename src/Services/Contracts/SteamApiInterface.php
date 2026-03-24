<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services\Contracts;

interface SteamApiInterface
{
    public function getUserGameDetails(string $username, string $apiKey): array|string;
}
