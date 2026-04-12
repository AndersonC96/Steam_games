<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Models;

final class SteamGame
{
    public function __construct(
        public readonly int $appId,
        public readonly string $name,
        public readonly int $playtimeMinutes,
        public readonly string $playtimeFormatted,
        public readonly string $priceFormatted,
        public readonly float $priceValue,
        public readonly string $description,
        public readonly string $headerImage,
        public readonly string $achievements,
        public readonly string $releaseDate
    ) {
    }
}
