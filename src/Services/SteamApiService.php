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

final class SteamApiService implements SteamApiInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly int $cacheTtlSeconds = 900,
        private readonly int $maxGamesToProcess = 100
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
            throw new Exception('Usuário não encontrado.');
        }

        $profile = $this->getUserProfile($steamId, $apiKey);
        if (!$profile) {
            throw new Exception('Perfil do usuário não encontrado.');
        }

        $allGames = $this->getSteamUserGames($steamId, $apiKey);
        if (empty($allGames)) {
            throw new Exception('Nenhum jogo encontrado para este usuário.');
        }

        // Sort by playtime to process the most relevant games first
        usort($allGames, fn ($a, $b) => ($b['playtime_forever'] ?? 0) <=> ($a['playtime_forever'] ?? 0));

        $gamesToProcess = array_slice($allGames, 0, $this->maxGamesToProcess);
        $processedGames = [];

        foreach ($gamesToProcess as $game) {
            $appId = (int) $game['appid'];
            $details = $this->getGameDetailsCached($steamId, $appId, $apiKey);
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

        // Save to cache (convert DTOs to arrays for storage)
        $this->cacheService->write('user_games', $cacheKey, [
            'profile' => (array) $result->profile,
            'games' => array_map(fn (SteamGame $g) => (array) $g, $result->games),
        ]);

        return $result;
    }

    private function getGameDetailsCached(string $steamId, int $appId, string $apiKey): array
    {
        $cacheKey = (string) $appId;
        // Game details change less frequently, so we could use a longer TTL here if we wanted.
        // For now, let's stick to the same TTL or maybe longer?
        // Actually, game details like price change, but let's cache them for 24h to be safe and fast.
        $cached = $this->cacheService->read('game_details', $cacheKey, 86400);

        if ($cached) {
            // Check if achievements are also cached (they depend on steamId)
            // Wait, achievements depend on the user, so they should be cached per user or inside the user_games cache.
            // But game details (price, description) are global.
            // Let's keep it simple: cache global details and fetch achievements.
        }

        // To keep it simple and consistent with current project:
        // Let's just use the current logic but wrap it in a per-game cache.
        // I will include achievements in the per-game-per-user cache key for now.
        $userAppKey = $steamId . '_' . $appId;
        $cachedUserApp = $this->cacheService->read('user_game_details', $userAppKey, $this->cacheTtlSeconds);

        if ($cachedUserApp) {
            return $cachedUserApp;
        }

        $details = $this->getGameDetails($steamId, $appId, $apiKey);
        $this->cacheService->write('user_game_details', $userAppKey, $details);

        return $details;
    }

    private function client(): Client
    {
        return new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
            'http_errors' => false, // Handle errors manually
        ]);
    }

    private function getJson(string $url, array $query): array
    {
        try {
            $response = $this->client()->request('GET', $url, ['query' => $query]);
            if ($response->getStatusCode() !== 200) {
                return [];
            }
            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException) {
            return [];
        }
    }

    private function getSteamUserId(string $username, string $apiKey): ?string
    {
        $data = $this->getJson('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/', [
            'key' => $apiKey,
            'vanityurl' => $username,
        ]);

        if (isset($data['response']['success']) && (int) $data['response']['success'] === 1) {
            return $data['response']['steamid'] ?? null;
        }

        // If it's already a SteamID
        if (is_numeric($username) && strlen($username) === 17) {
            return $username;
        }

        return null;
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
            'username' => $player['personaname'] ?? 'Nome não disponível',
            'avatar' => $player['avatarfull'] ?? 'img/padrao.png',
            'account_created' => isset($player['timecreated']) ? date('d/m/Y', $player['timecreated']) : 'Data de criação não disponível',
            'country' => $player['loccountrycode'] ?? 'N/A',
        ];
    }

    private function getSteamUserGames(string $steamId, string $apiKey): array
    {
        $data = $this->getJson('https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/', [
            'key' => $apiKey,
            'steamid' => $steamId,
            'include_appinfo' => true,
            'include_played_free_games' => false,
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
            $unlocked = count(array_filter($data['playerstats']['achievements'], static fn (array $a): bool => isset($a['achieved']) && (int) $a['achieved'] === 1));

            // Need total achievements from schema
            $schema = $this->getJson('https://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/', [
                'key' => $apiKey,
                'appid' => $appId,
            ]);

            $total = isset($schema['game']['availableGameStats']['achievements'])
                ? count($schema['game']['availableGameStats']['achievements'])
                : 0;

            if ($total > 0) {
                return $unlocked . '/' . $total . ' Conquistas';
            }
        }

        return 'Jogo sem conquistas';
    }

    private function getGameDetails(string $steamId, int $appId, string $apiKey): array
    {
        $data = $this->getJson('https://store.steampowered.com/api/appdetails', [
            'appids' => $appId,
            'l' => 'brazilian', // Get prices and descriptions in PT-BR
        ]);

        if (!isset($data[$appId]['data'])) {
            return $this->fallbackDetails();
        }

        $gameData = $data[$appId]['data'];

        return [
            'price' => $gameData['price_overview']['final_formatted'] ?? 'Grátis',
            'description' => $gameData['short_description'] ?? ($gameData['detailed_description'] ?? 'Descrição não disponível'),
            'image' => $gameData['header_image'] ?? 'img/padrao.png',
            'achievements' => $this->getAchievements($steamId, $appId, $apiKey),
            'release_date' => $gameData['release_date']['date'] ?? 'N/A',
        ];
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
        if ($minutes <= 0) {
            return 'Não jogado';
        }

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = floor($minutes / 60);
        $rem = $minutes % 60;

        if ($rem === 0) {
            return $hours . 'h';
        }

        return $hours . 'h ' . $rem . 'min';
    }
}
