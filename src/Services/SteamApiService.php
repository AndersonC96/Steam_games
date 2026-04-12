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
        $appsToFetch = [];

        foreach ($games as $game) {
            $appId = (int) $game['appid'];
            $cacheKey = $steamId . '_' . $appId;
            $cachedResult = $this->cacheService->read('user_game_details', $cacheKey, $this->cacheTtlSeconds);

            if ($cachedResult !== null) {
                $details[$appId] = $cachedResult;
            } else {
                $appsToFetch[] = $appId;
            }
        }

        if (!empty($appsToFetch)) {
            $parallelResults = $this->fetchDetailsInParallel($steamId, $appsToFetch, $apiKey);
            foreach ($parallelResults as $appId => $data) {
                $details[$appId] = $data;
                $this->cacheService->write('user_game_details', $steamId . '_' . $appId, $data);
            }
        }

        return $details;
    }

    private function fetchDetailsInParallel(string $steamId, array $appIds, string $apiKey): array
    {
        $results = [];
        $appPromises = [];
        $achievementPromises = [];

        foreach ($appIds as $appId) {
            $appPromises[$appId] = $this->apiClient->getAppDetailsAsync($appId);
            $achievementPromises[$appId] = $this->apiClient->getPlayerAchievementsAsync($steamId, $appId, $apiKey);
        }

        try {
            $appResponses = \GuzzleHttp\Promise\Utils::unwrap($appPromises);
            $achievementResponses = \GuzzleHttp\Promise\Utils::unwrap($achievementPromises);

            foreach ($appIds as $appId) {
                $response = $appResponses[$appId];
                $data = json_decode((string) $response->getBody(), true);

                if (!isset($data[$appId]['data'])) {
                    $results[$appId] = $this->fallbackDetails();
                    continue;
                }

                $gameData = $data[$appId]['data'];
                $achievements = json_decode((string) $achievementResponses[$appId]->getBody(), true) ?? [];

                $results[$appId] = [
                    'price' => $gameData['price_overview']['final_formatted'] ?? 'Grátis',
                    'description' => $gameData['short_description'] ?? 'Descrição não disponível',
                    'image' => $gameData['header_image'] ?? 'img/padrao.png',
                    'achievements' => $this->formatAchievements($achievements),
                    'release_date' => $gameData['release_date']['date'] ?? 'N/A',
                ];
            }
        } catch (\Throwable $e) {
            // Se falhar o lote, volta para o fallback unitário para cada app que falhou
            foreach ($appIds as $appId) {
                $results[$appId] = $this->fallbackDetails();
            }
        }

        return $results;
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
            $unlocked = count(array_filter(
                $data['playerstats']['achievements'],
                fn ($a) => (int)($a['achieved'] ?? 0) === 1
            ));
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
