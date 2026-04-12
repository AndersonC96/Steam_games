<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

use Anderson\SteamGames\Models\GameCollection;
use Anderson\SteamGames\Models\SteamGame;
use Anderson\SteamGames\Models\SteamProfile;
use Anderson\SteamGames\Services\Contracts\SteamApiInterface;
use Anderson\SteamGames\Support\TextHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;

final class SteamApiService implements SteamApiInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly int $cacheTtlSeconds = 900,
        private readonly int $maxGamesToProcess = 50
    ) {
    }

    public function getUserGameDetails(string $username, string $apiKey): GameCollection
    {
        $cacheKey = strtolower(trim($username));
        $cachedResult = $this->cacheService->read('user_games', $cacheKey, $this->cacheTtlSeconds);

        if (is_array($cachedResult)) {
            return new GameCollection(
                SteamProfile::fromArray($cachedResult['profile']),
                array_map(fn (array $g) => new SteamGame(...$g), $cachedResult['games']),
                ['source' => 'cache', 'cache_ttl' => $this->cacheTtlSeconds]
            );
        }

        $steamId = $this->getSteamUserId($username, $apiKey);
        if (!$steamId) {
            throw new Exception('Usuário não encontrado na Steam.');
        }

        $profile = $this->getUserProfile($steamId, $apiKey);
        if (!$profile) {
            throw new Exception('Perfil não acessível (verifique a privacidade).');
        }

        $allGames = $this->getSteamUserGames($steamId, $apiKey);
        if (empty($allGames)) {
            throw new Exception('Biblioteca vazia ou privada.');
        }

        // Ordenar por tempo jogado para priorizar jogos relevantes na carga inicial
        usort($allGames, fn ($a, $b) => ($b['playtime_forever'] ?? 0) <=> ($a['playtime_forever'] ?? 0));

        $gamesToProcess = array_slice($allGames, 0, $this->maxGamesToProcess);
        
        // Fase 1: Identificar o que precisa ser buscado (Promises)
        $promises = [];
        $client = $this->client();

        foreach ($gamesToProcess as $game) {
            $appId = (int) $game['appid'];
            $userAppKey = $steamId . '_' . $appId;
            $cached = $this->cacheService->read('user_game_details', $userAppKey, $this->cacheTtlSeconds);

            if ($cached) {
                $promises[$appId] = Promise\Create::promiseFor($cached);
            } else {
                // Requisição assíncrona para a Store API
                $promises[$appId] = $client->requestAsync('GET', 'https://store.steampowered.com/api/appdetails', [
                    'query' => ['appids' => $appId, 'l' => 'brazilian']
                ])->then(function ($response) use ($appId, $steamId, $apiKey) {
                    $data = json_decode((string) $response->getBody(), true);
                    $details = $this->processGameResponse($data, $appId, $steamId, $apiKey);
                    
                    // Salva cache individual do jogo
                    $this->cacheService->write('user_game_details', $steamId . '_' . $appId, $details);
                    return $details;
                }, function () {
                    return $this->fallbackDetails();
                });
            }
        }

        // Fase 2: Aguardar todas as requisições (Processamento Paralelo)
        $responses = Promise\Utils::unwrap($promises);
        
        $processedGames = [];
        foreach ($gamesToProcess as $game) {
            $appId = (int) $game['appid'];
            $details = $responses[$appId] ?? $this->fallbackDetails();
            $playtimeMinutes = (int) ($game['playtime_forever'] ?? 0);

            $processedGames[] = new SteamGame(
                $appId,
                (string) $game['name'],
                $playtimeMinutes,
                $this->formatPlaytime($playtimeMinutes),
                $details['price'],
                TextHelper::parsePrice($details['price']),
                $details['description'],
                $details['image'],
                $details['achievements'],
                $details['release_date']
            );
        }

        $result = new GameCollection(
            SteamProfile::fromArray($profile),
            $processedGames,
            ['source' => 'live', 'cache_ttl' => $this->cacheTtlSeconds]
        );

        $this->cacheService->write('user_games', $cacheKey, [
            'profile' => (array) $result->profile,
            'games' => array_map(fn (SteamGame $g) => (array) $g, $result->games),
        ]);

        return $result;
    }

    private function processGameResponse(?array $data, int $appId, string $steamId, string $apiKey): array
    {
        if (!isset($data[$appId]['data'])) {
            return $this->fallbackDetails();
        }

        $gameData = $data[$appId]['data'];

        return [
            'price' => $gameData['price_overview']['final_formatted'] ?? 'Grátis',
            'description' => $gameData['short_description'] ?? 'Descrição não disponível',
            'image' => $gameData['header_image'] ?? 'img/padrao.png',
            'achievements' => $this->getAchievements($steamId, $appId, $apiKey),
            'release_date' => $gameData['release_date']['date'] ?? 'N/A',
        ];
    }

    private function client(): Client
    {
        return new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
            'http_errors' => false,
        ]);
    }

    private function getJson(string $url, array $query): array
    {
        try {
            $response = $this->client()->request('GET', $url, ['query' => $query]);
            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException) {
            return [];
        }
    }

    private function getSteamUserId(string $username, string $apiKey): ?string
    {
        if (is_numeric($username) && strlen($username) === 17) {
            return $username;
        }

        $data = $this->getJson('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/', [
            'key' => $apiKey,
            'vanityurl' => $username,
        ]);

        return (isset($data['response']['success']) && (int) $data['response']['success'] === 1) 
            ? $data['response']['steamid'] 
            : null;
    }

    private function getUserProfile(string $steamId, string $apiKey): ?array
    {
        $data = $this->getJson('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/', [
            'key' => $apiKey,
            'steamids' => $steamId,
        ]);

        if (!isset($data['response']['players'][0])) {
            return null;
        }

        $player = $data['response']['players'][0];
        return [
            'username' => $player['personaname'] ?? 'N/A',
            'avatar' => $player['avatarfull'] ?? 'img/padrao.png',
            'account_created' => isset($player['timecreated']) ? date('d/m/Y', $player['timecreated']) : 'N/A',
            'country' => $player['loccountrycode'] ?? 'N/A',
        ];
    }

    private function getSteamUserGames(string $steamId, string $apiKey): array
    {
        $data = $this->getJson('https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/', [
            'key' => $apiKey,
            'steamid' => $steamId,
            'include_appinfo' => true,
        ]);

        return $data['response']['games'] ?? [];
    }

    private function getAchievements(string $steamId, int $appId, string $apiKey): string
    {
        $data = $this->getJson('https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/', [
            'key' => $apiKey,
            'steamid' => $steamId,
            'appid' => $appId,
        ]);

        if (isset($data['playerstats']['achievements'])) {
            $unlocked = count(array_filter($data['playerstats']['achievements'], fn ($a) => (int)($a['achieved'] ?? 0) === 1));
            return $unlocked . ' Conquistas';
        }

        return 'Jogo sem conquistas';
    }

    private function fallbackDetails(): array
    {
        return [
            'price' => 'N/A',
            'description' => 'Descrição não disponível',
            'image' => 'img/padrao.png',
            'achievements' => 'Jogo sem conquistas',
            'release_date' => 'N/A',
        ];
    }

    private function formatPlaytime(int $minutes): string
    {
        if ($minutes <= 0) return 'Não jogado';
        if ($minutes < 60) return $minutes . ' min';
        return floor($minutes / 60) . 'h ' . ($minutes % 60) . 'min';
    }
}
