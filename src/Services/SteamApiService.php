<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class SteamApiService
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly int $cacheTtlSeconds = 900
    ) {
    }

    public function getUserGameDetails(string $username, string $apiKey): array|string
    {
        $cacheKey = strtolower(trim($username));
        $cachedResult = $this->cacheService->read('user_games', $cacheKey, $this->cacheTtlSeconds);

        if (is_array($cachedResult)) {
            $cachedResult['meta'] = [
                'source' => 'cache',
                'cache_ttl' => $this->cacheTtlSeconds,
            ];

            return $cachedResult;
        }

        $steamId = $this->getSteamUserId($username, $apiKey);
        if (!$steamId) {
            return 'Usuário não encontrado.';
        }

        $profile = $this->getUserProfile($steamId, $apiKey);
        if (!$profile) {
            return 'Perfil do usuário não encontrado.';
        }

        $games = $this->getSteamUserGames($steamId, $apiKey);
        if (empty($games)) {
            return 'Nenhum jogo encontrado para este usuário.';
        }

        $result = [
            'profile' => $profile,
            'games' => [],
        ];

        foreach ($games as $game) {
            $details = $this->getGameDetails($steamId, (int) $game['appid'], $apiKey);
            $playtimeMinutes = (int) ($game['playtime_forever'] ?? 0);

            $result['games'][] = [
                'nome' => $game['name'],
                'tempo_jogado_minutos' => $playtimeMinutes,
                'tempo_jogado' => $this->formatPlaytime($playtimeMinutes),
                'preco_atual' => $details['price'],
                'descricao' => $details['description'],
                'capa' => $details['image'],
                'conquistas' => $details['achievements'],
                'data_lancamento' => $details['release_date'],
            ];
        }

        $result['meta'] = [
            'source' => 'live',
            'cache_ttl' => $this->cacheTtlSeconds,
        ];

        $this->cacheService->write('user_games', $cacheKey, $result);

        return $result;
    }

    private function client(): Client
    {
        return new Client([
            'timeout' => 12,
            'connect_timeout' => 8,
        ]);
    }

    private function getJson(string $url, array $query): array
    {
        $response = $this->client()->request('GET', $url, ['query' => $query]);
        return json_decode((string) $response->getBody(), true) ?? [];
    }

    private function getSteamUserId(string $username, string $apiKey): ?string
    {
        try {
            $data = $this->getJson('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/', [
                'key' => $apiKey,
                'vanityurl' => $username,
            ]);
        } catch (GuzzleException) {
            return null;
        }

        if (isset($data['response']['success']) && (int) $data['response']['success'] === 1) {
            return $data['response']['steamid'] ?? null;
        }

        return null;
    }

    private function getUserProfile(string $steamId, string $apiKey): ?array
    {
        try {
            $data = $this->getJson('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/', [
                'key' => $apiKey,
                'steamids' => $steamId,
            ]);
        } catch (GuzzleException) {
            return null;
        }

        if (!isset($data['response']['players'][0])) {
            return null;
        }

        $player = $data['response']['players'][0];

        return [
            'username' => $player['personaname'] ?? 'Nome não disponível',
            'avatar' => $player['avatarfull'] ?? 'img/avatar_padrao.png',
            'account_created' => isset($player['timecreated']) ? date('d/m/Y', $player['timecreated']) : 'Data de criação não disponível',
            'country' => $player['loccountrycode'] ?? 'N/A',
        ];
    }

    private function getSteamUserGames(string $steamId, string $apiKey): array
    {
        try {
            $data = $this->getJson('https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/', [
                'key' => $apiKey,
                'steamid' => $steamId,
                'include_appinfo' => true,
                'include_played_free_games' => false,
            ]);
        } catch (GuzzleException) {
            return [];
        }

        return $data['response']['games'] ?? [];
    }

    private function getAchievements(string $steamId, int $appId, string $apiKey): string
    {
        $client = $this->client();

        try {
            $response = $client->request('GET', 'https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/', [
                'query' => [
                    'key' => $apiKey,
                    'steamid' => $steamId,
                    'appid' => $appId,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if (isset($data['playerstats']['achievements'])) {
                $unlocked = count(array_filter($data['playerstats']['achievements'], static fn (array $achievement): bool => isset($achievement['achieved']) && (int) $achievement['achieved'] === 1));
            } else {
                $unlocked = 0;
            }
        } catch (GuzzleException) {
            return 'Jogo sem conquistas';
        }

        try {
            $response = $client->request('GET', 'https://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/', [
                'query' => [
                    'key' => $apiKey,
                    'appid' => $appId,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $total = isset($data['game']['availableGameStats']['achievements'])
                ? count($data['game']['availableGameStats']['achievements'])
                : 0;

            if ($total === 0) {
                return 'Jogo sem conquistas';
            }
        } catch (GuzzleException) {
            return 'Jogo sem conquistas';
        }

        return $unlocked . '/' . $total . ' Conquistas';
    }

    private function getGameDetails(string $steamId, int $appId, string $apiKey): array
    {
        try {
            $data = $this->getJson('https://store.steampowered.com/api/appdetails', [
                'appids' => $appId,
            ]);
        } catch (GuzzleException) {
            return $this->fallbackDetails();
        }

        if (!isset($data[$appId]['data'])) {
            return $this->fallbackDetails();
        }

        $gameData = $data[$appId]['data'];

        return [
            'price' => $gameData['price_overview']['final_formatted'] ?? 'Preço não disponível',
            'description' => $gameData['detailed_description'] ?? 'Descrição não disponível',
            'image' => isset($gameData['header_image']) && $gameData['header_image'] !== null ? $gameData['header_image'] : 'img/padrao.png',
            'achievements' => $this->getAchievements($steamId, $appId, $apiKey),
            'release_date' => $gameData['release_date']['date'] ?? 'Data de lançamento não disponível',
        ];
    }

    private function fallbackDetails(): array
    {
        return [
            'price' => 'Preço não disponível',
            'description' => 'Descrição não disponível',
            'image' => 'img/padrao.png',
            'achievements' => 'Jogo sem conquistas',
            'release_date' => 'Data de lançamento não disponível',
        ];
    }

    private function formatPlaytime(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'Não jogado';
        }

        if ($minutes === 1) {
            return '1 minuto';
        }

        if ($minutes < 60) {
            return $minutes . ' minutos';
        }

        $hours = (int) floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 1) {
            return $hours . ' horas e 1 minuto';
        }

        return $hours . ' horas e ' . $remainingMinutes . ' minutos';
    }
}
