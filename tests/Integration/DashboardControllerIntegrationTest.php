<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Tests\Integration;

use Anderson\SteamGames\Controllers\DashboardController;
use Anderson\SteamGames\Services\Contracts\SteamApiInterface;
use Anderson\SteamGames\Services\GameCatalogService;
use RuntimeException;

final class DashboardControllerIntegrationTest
{
    public function run(): void
    {
        $controller = new DashboardController(
            new FakeSteamApiService(),
            new GameCatalogService(),
            ['items_per_page' => 9],
            dirname(__DIR__, 2)
        );

        $_ENV['STEAM_API_KEY'] = 'fake-key';
        $_ENV['STEAM_USERNAME'] = 'gaben';

        $session = [];
        $result = $controller->handle(
            ['username' => 'gaben', 'order_by' => 'tempo_jogado'],
            $session,
            ['REQUEST_URI' => '/Steam_Games/']
        );

        $this->assertSame('', $result['errorMessage'], 'controller should not return error for mocked successful response');
        $this->assertSame(2, $result['totalGames'], 'controller should expose totalGames from mocked dataset');
        $this->assertSame('cache', $result['dataSourceLabel'], 'controller should expose data source label from service result');
        $this->assertSame('/Steam_Games/?username=gaben&order_by=tempo_jogado&played_filter=todos&price_filter=todos&achievement_filter=todos&page=1', $result['shareUrl'], 'controller should generate stable share URL');
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' | expected: ' . var_export($expected, true) . ' got: ' . var_export($actual, true));
        }
    }
}

final class FakeSteamApiService implements SteamApiInterface
{
    public function getUserGameDetails(string $username, string $apiKey): array|string
    {
        return [
            'profile' => [
                'username' => 'Gaben',
                'avatar' => 'img/avatar_padrao.png',
                'account_created' => '01/01/2000',
                'country' => 'US',
            ],
            'games' => [
                [
                    'nome' => 'Portal 2',
                    'tempo_jogado_minutos' => 200,
                    'tempo_jogado' => '3 horas e 20 minutos',
                    'preco_atual' => 'R$ 19,99',
                    'descricao' => 'Puzzle game',
                    'capa' => 'img/padrao.png',
                    'conquistas' => '10/50 Conquistas',
                    'data_lancamento' => 'Apr 19, 2011',
                ],
                [
                    'nome' => 'Half-Life 2',
                    'tempo_jogado_minutos' => 120,
                    'tempo_jogado' => '2 horas e 0 minutos',
                    'preco_atual' => 'R$ 9,99',
                    'descricao' => 'FPS game',
                    'capa' => 'img/padrao.png',
                    'conquistas' => '5/30 Conquistas',
                    'data_lancamento' => 'Nov 16, 2004',
                ],
            ],
            'meta' => [
                'source' => 'cache',
                'cache_ttl' => 900,
            ],
        ];
    }
}
