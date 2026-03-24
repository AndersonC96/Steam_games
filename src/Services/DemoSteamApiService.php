<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

use Anderson\SteamGames\Services\Contracts\SteamApiInterface;

final class DemoSteamApiService implements SteamApiInterface
{
    public function getUserGameDetails(string $username, string $apiKey): array|string
    {
        return [
            'profile' => [
                'username' => 'AndersonC96 (Demo)',
                'avatar' => 'https://avatars.steamstatic.com/c852f8478cffe910a2fe196c32ff2e5ed34b1a9_full.jpg',
                'account_created' => '12/04/2014',
                'country' => 'BR',
            ],
            'games' => [
                [
                    'nome' => 'Counter-Strike 2',
                    'tempo_jogado_minutos' => 1580,
                    'tempo_jogado' => '26 horas e 20 minutos',
                    'preco_atual' => 'Preço não disponível',
                    'descricao' => 'Competitive FPS with tactical gameplay and frequent seasonal updates.',
                    'capa' => 'https://cdn.akamai.steamstatic.com/steam/apps/730/header.jpg',
                    'conquistas' => 'Jogo sem conquistas',
                    'data_lancamento' => '21 Aug, 2012',
                ],
                [
                    'nome' => 'Elden Ring',
                    'tempo_jogado_minutos' => 940,
                    'tempo_jogado' => '15 horas e 40 minutos',
                    'preco_atual' => 'R$ 229,90',
                    'descricao' => 'Open world action RPG focused on exploration and high challenge encounters.',
                    'capa' => 'https://cdn.akamai.steamstatic.com/steam/apps/1245620/header.jpg',
                    'conquistas' => '12/42 Conquistas',
                    'data_lancamento' => '24 Feb, 2022',
                ],
                [
                    'nome' => 'Hades',
                    'tempo_jogado_minutos' => 320,
                    'tempo_jogado' => '5 horas e 20 minutos',
                    'preco_atual' => 'R$ 73,99',
                    'descricao' => 'Fast paced roguelike with tight combat loops and excellent progression design.',
                    'capa' => 'https://cdn.akamai.steamstatic.com/steam/apps/1145360/header.jpg',
                    'conquistas' => '7/49 Conquistas',
                    'data_lancamento' => '17 Sep, 2020',
                ],
                [
                    'nome' => 'Portal 2',
                    'tempo_jogado_minutos' => 0,
                    'tempo_jogado' => 'Não jogado',
                    'preco_atual' => 'R$ 32,99',
                    'descricao' => 'Puzzle platform experience that combines humor and excellent level design.',
                    'capa' => 'https://cdn.akamai.steamstatic.com/steam/apps/620/header.jpg',
                    'conquistas' => '0/51 Conquistas',
                    'data_lancamento' => '18 Apr, 2011',
                ],
                [
                    'nome' => 'DOTA 2',
                    'tempo_jogado_minutos' => 460,
                    'tempo_jogado' => '7 horas e 40 minutos',
                    'preco_atual' => 'Preço não disponível',
                    'descricao' => 'MOBA title with deep strategic gameplay and a competitive ranked environment.',
                    'capa' => 'https://cdn.akamai.steamstatic.com/steam/apps/570/header.jpg',
                    'conquistas' => 'Jogo sem conquistas',
                    'data_lancamento' => '9 Jul, 2013',
                ],
            ],
            'meta' => [
                'source' => 'demo',
                'cache_ttl' => 0,
            ],
        ];
    }
}
