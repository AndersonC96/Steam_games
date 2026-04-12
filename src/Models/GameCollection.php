<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Models;

final class GameCollection
{
    /**
     * @param SteamGame[] $games
     */
    public function __construct(
        public readonly SteamProfile $profile,
        public array $games,
        public readonly array $meta
    ) {
    }
}
