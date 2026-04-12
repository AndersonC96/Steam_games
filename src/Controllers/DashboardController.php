<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Controllers;

use Anderson\SteamGames\Exceptions\SteamApiException;
use Anderson\SteamGames\Models\GameCollection;
use Anderson\SteamGames\Services\Contracts\SteamApiInterface;
use Anderson\SteamGames\Services\GameCatalogService;
use Anderson\SteamGames\Support\UrlHelper;

final class DashboardController
{
    private const ALLOWED_ORDERS = ['nome', 'data_lancamento', 'tempo_jogado', 'preco_atual'];
    private const ALLOWED_PLAYED = ['todos', 'jogados', 'nao_jogados'];
    private const ALLOWED_PRICE = ['todos', 'gratis', 'pagos'];
    private const ALLOWED_ACHIEVEMENTS = ['todos', 'com', 'sem'];

    public function __construct(
        private readonly SteamApiInterface $steamApiService,
        private readonly GameCatalogService $gameCatalogService,
        private readonly array $config,
        private readonly string $rootPath
    ) {
    }

    public function handle(array $query, array &$session, array $server): array
    {
        $input = $this->parseInput($query);
        $this->initializeSessionMetrics($session);

        $errorMessage = '';
        $collection = null;
        $queryTimeMs = 0.0;

        if ($input['isSearchRequested']) {
            try {
                $this->validateSearchPrerequisites($input['username'], $input['apiKey'], $input['isDemoMode']);

                $queryStart = microtime(true);
                $collection = $this->steamApiService->getUserGameDetails($input['username'], $input['apiKey']);
                $queryTimeMs = (microtime(true) - $queryStart) * 1000;

                $this->updateSessionMetrics($session, $queryTimeMs, $input['username'], $collection->meta['source'] ?? '');
            } catch (SteamApiException $e) {
                $errorMessage = $e->getMessage();
            } catch (\Throwable $e) {
                // Catch any unexpected error to prevent crashing the view
                $errorMessage = "Ocorreu um erro interno inesperado ao processar a busca.";
                // In a real system, we would log $e->getMessage() here
            }
        }

        return $this->buildViewModel($input, $collection, $session, $server, $errorMessage, $queryTimeMs);
    }

    public function render(array $data): void
    {
        extract($data, EXTR_SKIP);
        require $this->rootPath . '/views/dashboard.php';
    }

    private function parseInput(array $query): array
    {
        $username = trim((string) ($query['username'] ?? ''));
        $defaultUsername = $_ENV['STEAM_USERNAME'] ?? '';

        return [
            'isSearchRequested' => isset($query['username']),
            'isDemoMode' => isset($query['demo']) && (string) $query['demo'] === '1',
            'username' => $username,
            'inputValue' => $username !== '' ? $username : $defaultUsername,
            'apiKey' => $_ENV['STEAM_API_KEY'] ?? '',
            'orderBy' => $this->validateParam($query['order_by'] ?? 'tempo_jogado', self::ALLOWED_ORDERS, 'tempo_jogado'),
            'playedFilter' => $this->validateParam($query['played_filter'] ?? 'todos', self::ALLOWED_PLAYED, 'todos'),
            'priceFilter' => $this->validateParam($query['price_filter'] ?? 'todos', self::ALLOWED_PRICE, 'todos'),
            'achievementFilter' => $this->validateParam($query['achievement_filter'] ?? 'todos', self::ALLOWED_ACHIEVEMENTS, 'todos'),
            'currentPage' => max(1, (int) ($query['page'] ?? 1)),
        ];
    }

    /**
     * @throws SteamApiException
     */
    private function validateSearchPrerequisites(string $username, string $apiKey, bool $isDemoMode): void
    {
        if ($username === '') {
            throw new SteamApiException('Digite um nome de usuário Steam para buscar.');
        }

        if ($apiKey === '' && !$isDemoMode) {
            throw new SteamApiException('A API Key não está configurada no servidor.');
        }
    }

    private function buildViewModel(array $input, ?GameCollection $collection, array $session, array $server, string $errorMessage, float $queryTimeMs): array
    {
        $viewModel = array_merge($input, [
            'errorMessage' => $errorMessage,
            'queryTimeMs' => $queryTimeMs,
            'games' => [],
            'profile' => null,
            'totalPages' => 1,
            'totalGames' => 0,
            'totalGamesBeforeFilter' => 0,
            'totalMinutes' => 0,
            'totalValue' => 0.0,
            'dataSourceLabel' => '',
            'shareUrl' => '',
        ]);

        if ($collection instanceof GameCollection) {
            $viewModel['profile'] = $collection->profile;
            $viewModel['dataSourceLabel'] = $collection->meta['source'] ?? '';
            $viewModel['totalGamesBeforeFilter'] = count($collection->games);

            $filteredGames = $this->gameCatalogService->applyFilters(
                $collection->games,
                $input['playedFilter'],
                $input['priceFilter'],
                $input['achievementFilter']
            );
            $sortedGames = $this->gameCatalogService->sortGames($filteredGames, $input['orderBy']);
            $totals = $this->gameCatalogService->totals($sortedGames);

            $viewModel['totalMinutes'] = (int) $totals['total_minutes'];
            $viewModel['totalValue'] = (float) $totals['total_value'];
            $viewModel['totalGames'] = count($sortedGames);

            $itemsPerPage = (int) ($this->config['items_per_page'] ?? 9);
            $viewModel['totalPages'] = max(1, (int) ceil($viewModel['totalGames'] / $itemsPerPage));
            $viewModel['currentPage'] = min($input['currentPage'], $viewModel['totalPages']);

            $viewModel['games'] = array_slice($sortedGames, ($viewModel['currentPage'] - 1) * $itemsPerPage, $itemsPerPage);

            $viewModel['shareUrl'] = $this->generateShareUrl($server, [
                'username' => $input['username'],
                'order_by' => $input['orderBy'],
                'played_filter' => $input['playedFilter'],
                'price_filter' => $input['priceFilter'],
                'achievement_filter' => $input['achievementFilter'],
                'page' => $viewModel['currentPage'],
                'demo' => $input['isDemoMode'] ? '1' : null,
            ]);
        }

        return array_merge($viewModel, $this->getSessionStats($session));
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
