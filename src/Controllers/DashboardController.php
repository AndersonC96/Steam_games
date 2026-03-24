<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Controllers;

use Anderson\SteamGames\Services\Contracts\SteamApiInterface;
use Anderson\SteamGames\Services\GameCatalogService;

final class DashboardController
{
    public function __construct(
        private readonly SteamApiInterface $steamApiService,
        private readonly GameCatalogService $gameCatalogService,
        private readonly array $config,
        private readonly string $rootPath
    ) {
    }

    public function handle(array $query, array &$session, array $server): array
    {
        $allowedOrders = ['nome', 'data_lancamento', 'tempo_jogado', 'preco_atual'];
        $allowedPlayed = ['todos', 'jogados', 'nao_jogados'];
        $allowedPrice = ['todos', 'gratis', 'pagos'];
        $allowedAchievements = ['todos', 'com', 'sem'];

        $requestedOrderBy = (string) ($query['order_by'] ?? 'tempo_jogado');
        $requestedPlayedFilter = (string) ($query['played_filter'] ?? 'todos');
        $requestedPriceFilter = (string) ($query['price_filter'] ?? 'todos');
        $requestedAchievementFilter = (string) ($query['achievement_filter'] ?? 'todos');
        $isDemoMode = isset($query['demo']) && (string) $query['demo'] === '1';

        $orderBy = in_array($requestedOrderBy, $allowedOrders, true) ? $requestedOrderBy : 'tempo_jogado';
        $playedFilter = in_array($requestedPlayedFilter, $allowedPlayed, true) ? $requestedPlayedFilter : 'todos';
        $priceFilter = in_array($requestedPriceFilter, $allowedPrice, true) ? $requestedPriceFilter : 'todos';
        $achievementFilter = in_array($requestedAchievementFilter, $allowedAchievements, true) ? $requestedAchievementFilter : 'todos';
        $currentPage = max(1, (int) ($query['page'] ?? 1));

        $errorMessage = '';
        $username = trim((string) ($query['username'] ?? ''));
        $apiKey = $_ENV['STEAM_API_KEY'] ?? '';
        $defaultUsername = $_ENV['STEAM_USERNAME'] ?? '';
        $inputValue = $username !== '' ? $username : $defaultUsername;

        $detalhesJogos = null;
        $queryTimeMs = 0.0;

        $this->initializeSessionMetrics($session);

        if (isset($query['username'])) {
            if ($username === '') {
                $errorMessage = 'Digite um nome de usuário Steam para buscar.';
            } elseif ($apiKey === '' && !$isDemoMode) {
                $errorMessage = 'A chave STEAM_API_KEY não foi encontrada no arquivo .env.';
            } else {
                $queryStart = microtime(true);
                $detalhesJogos = $this->steamApiService->getUserGameDetails($username, $apiKey);
                $queryTimeMs = (microtime(true) - $queryStart) * 1000;

                if (is_string($detalhesJogos)) {
                    $errorMessage = $detalhesJogos;
                    $detalhesJogos = null;
                } else {
                    $session['portfolio_metrics']['queries']++;
                    $session['portfolio_metrics']['total_ms'] += $queryTimeMs;
                    $session['portfolio_metrics']['last_username'] = $username;

                    if (($detalhesJogos['meta']['source'] ?? '') === 'cache') {
                        $session['portfolio_metrics']['cache_hits']++;
                    }
                }
            }
        }

        $games = [];
        $profile = [];
        $itemsPerPage = (int) ($this->config['items_per_page'] ?? 9);
        $totalPages = 1;
        $totalGames = 0;
        $totalGamesBeforeFilter = 0;
        $totalMinutes = 0;
        $totalValue = 0.0;
        $dataSourceLabel = '';
        $shareUrl = '';

        if (is_array($detalhesJogos)) {
            $games = $detalhesJogos['games'];
            $profile = $detalhesJogos['profile'];
            $dataSourceLabel = $detalhesJogos['meta']['source'] ?? '';
            $totalGamesBeforeFilter = count($games);

            $games = $this->gameCatalogService->applyFilters($games, $playedFilter, $priceFilter, $achievementFilter);
            $games = $this->gameCatalogService->sortGames($games, $orderBy);

            $totals = $this->gameCatalogService->totals($games);
            $totalMinutes = (int) $totals['total_minutes'];
            $totalValue = (float) $totals['total_value'];

            $totalGames = count($games);
            $totalPages = max(1, (int) ceil($totalGames / $itemsPerPage));
            $currentPage = min($currentPage, $totalPages);
            $startIndex = ($currentPage - 1) * $itemsPerPage;
            $games = array_slice($games, $startIndex, $itemsPerPage);

            $sharePath = strtok((string) ($server['REQUEST_URI'] ?? ''), '?');
            $shareParams = [
                'username' => $username,
                'order_by' => $orderBy,
                'played_filter' => $playedFilter,
                'price_filter' => $priceFilter,
                'achievement_filter' => $achievementFilter,
                'page' => $currentPage,
            ];

            if ($isDemoMode) {
                $shareParams['demo'] = '1';
            }

            $shareUrl = $sharePath . '?' . http_build_query($shareParams);
        }

        $sessionQueries = (int) ($session['portfolio_metrics']['queries'] ?? 0);
        $sessionAvgMs = $sessionQueries > 0 ? ((float) ($session['portfolio_metrics']['total_ms'] ?? 0.0) / $sessionQueries) : 0.0;
        $sessionCacheRate = $sessionQueries > 0 ? (((int) ($session['portfolio_metrics']['cache_hits'] ?? 0) / $sessionQueries) * 100) : 0.0;
        $lastUser = (string) ($session['portfolio_metrics']['last_username'] ?? '');

        return compact(
            'orderBy',
            'playedFilter',
            'priceFilter',
            'achievementFilter',
            'currentPage',
            'errorMessage',
            'username',
            'inputValue',
            'queryTimeMs',
            'games',
            'profile',
            'totalPages',
            'totalGames',
            'totalGamesBeforeFilter',
            'totalMinutes',
            'totalValue',
            'dataSourceLabel',
            'sessionAvgMs',
            'sessionCacheRate',
            'lastUser',
            'shareUrl',
            'isDemoMode'
        );
    }

    public function render(array $data): void
    {
        extract($data, EXTR_SKIP);
        require $this->rootPath . '/views/dashboard.php';
    }

    private function initializeSessionMetrics(array &$session): void
    {
        if (!isset($session['portfolio_metrics']) || !is_array($session['portfolio_metrics'])) {
            $session['portfolio_metrics'] = [
                'queries' => 0,
                'total_ms' => 0.0,
                'cache_hits' => 0,
                'last_username' => '',
            ];
        }
    }
}
