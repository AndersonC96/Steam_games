<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Controllers;

use Anderson\SteamGames\Services\SteamApiService;
use Anderson\SteamGames\Support\TextHelper;

final class DashboardController
{
    public function __construct(
        private readonly SteamApiService $steamApiService,
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
            } elseif ($apiKey === '') {
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

            $games = array_values(array_filter($games, static function (array $jogo) use ($playedFilter, $priceFilter, $achievementFilter): bool {
                $minutes = (int) ($jogo['tempo_jogado_minutos'] ?? 0);
                $price = TextHelper::parsePrice((string) ($jogo['preco_atual'] ?? 'Preço não disponível'));
                $hasAchievements = isset($jogo['conquistas']) && $jogo['conquistas'] !== 'Jogo sem conquistas';

                if ($playedFilter === 'jogados' && $minutes <= 0) {
                    return false;
                }

                if ($playedFilter === 'nao_jogados' && $minutes > 0) {
                    return false;
                }

                if ($priceFilter === 'gratis' && $price > 0) {
                    return false;
                }

                if ($priceFilter === 'pagos' && $price <= 0) {
                    return false;
                }

                if ($achievementFilter === 'com' && !$hasAchievements) {
                    return false;
                }

                if ($achievementFilter === 'sem' && $hasAchievements) {
                    return false;
                }

                return true;
            }));

            usort($games, static function (array $a, array $b) use ($orderBy): int {
                return match ($orderBy) {
                    'nome' => strcmp((string) $a['nome'], (string) $b['nome']),
                    'data_lancamento' => (int) strtotime((string) ($b['data_lancamento'] ?? '')) <=> (int) strtotime((string) ($a['data_lancamento'] ?? '')),
                    'preco_atual' => TextHelper::parsePrice((string) $b['preco_atual']) <=> TextHelper::parsePrice((string) $a['preco_atual']),
                    default => ((int) ($b['tempo_jogado_minutos'] ?? 0)) <=> ((int) ($a['tempo_jogado_minutos'] ?? 0)),
                };
            });

            foreach ($games as $jogo) {
                $totalMinutes += (int) ($jogo['tempo_jogado_minutos'] ?? 0);
                $totalValue += TextHelper::parsePrice((string) ($jogo['preco_atual'] ?? 'Preço não disponível'));
            }

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
            'shareUrl'
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
