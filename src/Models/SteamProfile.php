<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Models;

final class SteamProfile
{
    public function __construct(
        public readonly string $username,
        public readonly string $avatar,
        public readonly string $accountCreated,
        public readonly string $country
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['username'] ?? 'N/A'),
            (string) ($data['avatar'] ?? 'img/padrao.png'),
            (string) ($data['account_created'] ?? 'N/A'),
            (string) ($data['country'] ?? 'N/A')
        );
    }
}
