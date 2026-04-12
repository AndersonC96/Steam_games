<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

use Anderson\SteamGames\Models\GameCollection;
use Anderson\SteamGames\Models\SteamGame;
use Anderson\SteamGames\Models\SteamProfile;
use Anderson\SteamGames\Services\Contracts\SteamApiInterface;

final class DemoSteamApiService implements SteamApiInterface
{
    public function getUserGameDetails(string $username, string $apiKey): GameCollection
    {
        $profile = new SteamProfile(
            'AndersonC96 (Demo)',
            'https://avatars.steamstatic.com/c852f8478cffe910a2fe196c32ff2e5ed34b1a9_full.jpg',
            '12/04/2014',
            'BR'
        );

        $games = [
            new SteamGame(
                730,
                'Counter-Strike 2',
                1580,
                '26 horas e 20 minutos',
                'Preço não disponível',
                0.0,
                'Competitive FPS with tactical gameplay and frequent seasonal updates.',
                'https://cdn.akamai.steamstatic.com/steam/apps/730/header.jpg',
                'Jogo sem conquistas',
                '21 Aug, 2012'
            ),
            new SteamGame(
                1245620,
                'Elden Ring',
                940,
                '15 horas e 40 minutos',
                'R$ 229,90',
                229.90,
                'Open world action RPG focused on exploration and high challenge encounters.',
                'https://cdn.akamai.steamstatic.com/steam/apps/1245620/header.jpg',
                '12/42 Conquistas',
                '24 Feb, 2022'
            ),
            new SteamGame(
                1145360,
                'Hades',
                320,
                '5 horas e 20 minutos',
                'R$ 73,99',
                73.99,
                'Fast paced roguelike with tight combat loops and excellent progression design.',
                'https://cdn.akamai.steamstatic.com/steam/apps/1145360/header.jpg',
                '7/49 Conquistas',
                '17 Sep, 2020'
            ),
            new SteamGame(
                620,
                'Portal 2',
                0,
                'Não jogado',
                'R$ 32,99',
                32.99,
                'Puzzle platform experience that combines humor and excellent level design.',
                'https://cdn.akamai.steamstatic.com/steam/apps/620/header.jpg',
                '0/51 Conquistas',
                '18 Apr, 2011'
            ),
            new SteamGame(
                570,
                'DOTA 2',
                460,
                '7 horas e 40 minutos',
                'Preço não disponível',
                0.0,
                'MOBA title with deep strategic gameplay and a competitive ranked environment.',
                'https://cdn.akamai.steamstatic.com/steam/apps/570/header.jpg',
                'Jogo sem conquistas',
                '9 Jul, 2013'
            ),
        ];

        return new GameCollection($profile, $games, [
            'source' => 'demo',
            'cache_ttl' => 0,
        ]);
    }
}
