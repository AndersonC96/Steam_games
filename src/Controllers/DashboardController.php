<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Controllers;

use Anderson\SteamGames\Models\GameCollection;
use Anderson\SteamGames\Services\Contracts\SteamApiInterface;
use Anderson\SteamGames\Services\GameCatalogService;
use Exception;

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
        // 1. Inputs & Defaults
        $isDemoMode = isset($query['demo']) && (string) $query['demo'] === '1';
        $username = trim((string) ($query['username'] ?? ''));
        $apiKey = $_ENV['STEAM_API_KEY'] ?? '';
        $defaultUsername = $_ENV['STEAM_USERNAME'] ?? '';
        $inputValue = $username !== '' ? $username : $defaultUsername;

        // 2. Filters & Pagination
        $orderBy = $this->validateParam($query['order_by'] ?? 'tempo_jogado', ['nome', 'data_lancamento', 'tempo_jogado', 'preco_atual'], 'tempo_jogado');
        $playedFilter = $this->validateParam($query['played_filter'] ?? 'todos', ['todos', 'jogados', 'nao_jogados'], 'todos');
        $priceFilter = $this->validateParam($query['price_filter'] ?? 'todos', ['todos', 'gratis', 'pagos'], 'todos');
        $achievementFilter = $this->validateParam($query['achievement_filter'] ?? 'todos', ['todos', 'com', 'sem'], 'todos');
        $currentPage = max(1, (int) ($query['page'] ?? 1));

        // 3. Execution
        $errorMessage = '';
        $collection = null;
        $queryTimeMs = 0.0;
        $this->initializeSessionMetrics($session);

        if (isset($query['username'])) {
            try {
                if ($username === '') {
                    throw new Exception('Digite um nome de usuário Steam para buscar.');
                }
                if ($apiKey === '' && !$isDemoMode) {
                    throw new Exception('A chave STEAM_API_KEY não foi encontrada no arquivo .env.');
                }

                $queryStart = microtime(true);
                $collection = $this->steamApiService->getUserGameDetails($username, $apiKey);
                $queryTimeMs = (microtime(true) - $queryStart) * 1000;

                $this->updateSessionMetrics($session, $queryTimeMs, $username, $collection->meta['source'] ?? '');

            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }

        // 4. Post-processing (Filtering, Totals, Pagination)
        $games = [];
        $profile = null;
        $totalPages = 1;
        $totalGames = 0;
        $totalGamesBeforeFilter = 0;
        $totalMinutes = 0;
        $totalValue = 0.0;
        $dataSourceLabel = '';
        $shareUrl = '';

        if ($collection instanceof GameCollection) {
            $profile = $collection->profile;
            $allGames = $collection->games;
            $dataSourceLabel = $collection->meta['source'] ?? '';
            $totalGamesBeforeFilter = count($allGames);

            $filteredGames = $this->gameCatalogService->applyFilters($allGames, $playedFilter, $priceFilter, $achievementFilter);
            $sortedGames = $this->gameCatalogService->sortGames($filteredGames, $orderBy);

            $totals = $this->gameCatalogService->totals($sortedGames);
            $totalMinutes = (int) $totals['total_minutes'];
            $totalValue = (float) $totals['total_value'];

            $totalGames = count($sortedGames);
            $itemsPerPage = (int) ($this->config['items_per_page'] ?? 9);
            $totalPages = max(1, (int) ceil($totalGames / $itemsPerPage));
            $currentPage = min($currentPage, $totalPages);

            $games = array_slice($sortedGames, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);

            $shareUrl = $this->generateShareUrl($server, [
                'username' => $username,
                'order_by' => $orderBy,
                'played_filter' => $playedFilter,
                'price_filter' => $priceFilter,
                'achievement_filter' => $achievementFilter,
                'page' => $currentPage,
                'demo' => $isDemoMode ? '1' : null,
            ]);
        }

        // 5. Session Stats for UI
        $sessionStats = $this->getSessionStats($session);

        return array_merge(compact(
            'orderBy', 'playedFilter', 'priceFilter', 'achievementFilter',
            'currentPage', 'errorMessage', 'username', 'inputValue',
            'queryTimeMs', 'games', 'profile', 'totalPages', 'totalGames',
            'totalGamesBeforeFilter', 'totalMinutes', 'totalValue',
            'dataSourceLabel', 'shareUrl', 'isDemoMode'
        ), $sessionStats);
    }

    public function render(array $data): void
    {
        extract($data, EXTR_SKIP);
        require $this->rootPath . '/views/dashboard.php';
    }

    private function validateParam(mixed $value, array $allowed, string $default): string
    {
        return in_array((string) $value, $allowed, true) ? (string) $value : $default;
    }

    private function generateShareUrl(array $server, array $params): string
    {
        $path = strtok((string) ($server['REQUEST_URI'] ?? ''), '?');
        $cleanParams = array_filter($params, fn($v) => $v !== null && $v !== '');
        return $path . '?' . http_build_query($cleanParams);
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

    private function updateSessionMetrics(array &$session, float $timeMs, string $username, string $source): void
    {
        $session['portfolio_metrics']['queries']++;
        $session['portfolio_metrics']['total_ms'] += $timeMs;
        $session['portfolio_metrics']['last_username'] = $username;
        if ($source === 'cache') {
            $session['portfolio_metrics']['cache_hits']++;
        }
    }

    private function getSessionStats(array $session): array
    {
        $m = $session['portfolio_metrics'];
        $queries = (int) $m['queries'];
        return [
            'sessionAvgMs' => $queries > 0 ? ($m['total_ms'] / $queries) : 0.0,
            'sessionCacheRate' => $queries > 0 ? (($m['cache_hits'] / $queries) * 100) : 0.0,
            'lastUser' => (string) $m['last_username'],
        ];
    }
}
