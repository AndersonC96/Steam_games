<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

use Anderson\SteamGames\Exceptions\SteamApiException;
use Anderson\SteamGames\Infrastructure\SteamApiClient;
use Anderson\SteamGames\Models\GameCollection;
use Anderson\SteamGames\Models\SteamGame;
use Anderson\SteamGames\Models\SteamProfile;
use Anderson\SteamGames\Services\Contracts\SteamApiInterface;
use Anderson\SteamGames\Support\TextHelper;

final class SteamApiService implements SteamApiInterface
{
    public function __construct(
        private readonly SteamApiClient $apiClient,
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

        $steamId = $this->apiClient->resolveSteamId($username, $apiKey);
        $player = $this->apiClient->getPlayerSummaries($steamId, $apiKey);
        $allGames = $this->apiClient->getOwnedGames($steamId, $apiKey);

        // Priorização de jogos
        usort($allGames, fn ($a, $b) => ($b['playtime_forever'] ?? 0) <=> ($a['playtime_forever'] ?? 0));
        $gamesToProcess = array_slice($allGames, 0, $this->maxGamesToProcess);
        
        $gamesDetails = $this->fetchGamesDetails($steamId, $gamesToProcess, $apiKey);

        $processedGames = [];
        foreach ($gamesToProcess as $game) {
            $appId = (int) $game['appid'];
            $details = $gamesDetails[$appId];
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
            $this->mapProfile($player),
            $processedGames,
            ['source' => 'live', 'cache_ttl' => $this->cacheTtlSeconds]
        );

        $this->cacheService->write('user_games', $cacheKey, [
            'profile' => (array) $result->profile,
            'games' => array_map(fn (SteamGame $g) => (array) $g, $result->games),
        ]);

        return $result;
    }

    private function fetchGamesDetails(string $steamId, array $games, string $apiKey): array
    {
        $details = [];
        $appIdsToFetch = [];

        foreach ($games as $game) {
            $appId = (int) $game['appid'];
            $cache = $this->cacheService->read('user_game_details', $steamId . '_' . $appId, $this->cacheTtlSeconds);
            
            if ($cache) {
                $details[$appId] = $cache;
            } else {
                $appIdsToFetch[] = $appId;
            }
        }

        if (!empty($appIdsToFetch)) {
            $apiResponses = $this->apiClient->getMultipleAppDetailsAsync($appIdsToFetch);
            
            foreach ($apiResponses as $appId => $response) {
                $data = json_decode((string) $response->getBody(), true);
                $gameDetails = $this->processGameResponse($data, (int) $appId, $steamId, $apiKey);
                
                $this->cacheService->write('user_game_details', $steamId . '_' . $appId, $gameDetails);
                $details[$appId] = $gameDetails;
            }
        }

        return $details;
    }

    private function processGameResponse(?array $data, int $appId, string $steamId, string $apiKey): array
    {
        if (!isset($data[$appId]['data'])) {
            return $this->fallbackDetails();
        }

        $gameData = $data[$appId]['data'];
        $achievements = $this->apiClient->getPlayerAchievements($steamId, $appId, $apiKey);

        return [
            'price' => $gameData['price_overview']['final_formatted'] ?? 'Grátis',
            'description' => $gameData['short_description'] ?? 'Descrição não disponível',
            'image' => $gameData['header_image'] ?? 'img/padrao.png',
            'achievements' => $this->formatAchievements($achievements),
            'release_date' => $gameData['release_date']['date'] ?? 'N/A',
        ];
    }

    private function mapProfile(array $player): SteamProfile
    {
        return new SteamProfile(
            $player['personaname'] ?? 'N/A',
            $player['avatarfull'] ?? 'img/padrao.png',
            isset($player['timecreated']) ? date('d/m/Y', $player['timecreated']) : 'N/A',
            $player['loccountrycode'] ?? 'N/A'
        );
    }

    private function formatAchievements(array $data): string
    {
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

    /**
     * @throws \Exception If game details cannot be retrieved
     */
    private function getGameDetailsOrThrow(int $appId, string $steamId, string $apiKey): array
    {
        $details = $this->fetchSingleGameDetails($appId, $steamId, $apiKey);

        if ($details === null) {
            throw new \Exception("Não foi possível obter detalhes para o jogo ID: {$appId}");
        }

        return $details;
    }

    private function fetchSingleGameDetails(int $appId, string $steamId, string $apiKey): ?array
    {
        $cacheKey = $steamId . '_' . $appId;
        $cachedResult = $this->cacheService->read('user_game_details', $cacheKey, $this->cacheTtlSeconds);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        try {
            $apiResponse = $this->apiClient->getAppDetails($appId);
            $data = json_decode((string) $apiResponse->getBody(), true);

            if (!isset($data[$appId]['data'])) {
                return null;
            }

            $gameData = $data[$appId]['data'];
            $achievements = $this->apiClient->getPlayerAchievements($steamId, $appId, $apiKey);

            $gameDetails = [
                'price' => $gameData['price_overview']['final_formatted'] ?? 'Grátis',
                'description' => $gameData['short_description'] ?? 'Descrição não disponível',
                'image' => $gameData['header_image'] ?? 'img/padrao.png',
                'achievements' => $this->formatAchievements($achievements),
                'release_date' => $gameData['release_date']['date'] ?? 'N/A',
            ];

            $this->cacheService->write('user_game_details', $cacheKey, $gameDetails);
            return $gameDetails;
        } catch (\Exception $e) {
            // Log the exception if logging is available
            return null;
        }
    }

    private function formatPlaytime(int $minutes): string
    {
        if ($minutes <= 0) return 'Não jogado';
        if ($minutes < 60) return $minutes . ' min';
        return floor($minutes / 60) . 'h ' . ($minutes % 60) . 'min';
    }
}
