<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Tests\Integration;

use Anderson\SteamGames\Controllers\DashboardController;
use Anderson\SteamGames\Models\GameCollection;
use Anderson\SteamGames\Models\SteamGame;
use Anderson\SteamGames\Models\SteamProfile;
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
        // Note: The shareUrl generation might be slightly different now due to my changes in Controller (using array_filter)
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
    public function getUserGameDetails(string $username, string $apiKey): GameCollection
    {
        $profile = new SteamProfile('Gaben', 'img/padrao.png', '01/01/2000', 'US');
        $games = [
            new SteamGame(1, 'Portal 2', 200, '3h 20min', 'R$ 19,99', 19.99, 'Puzzle', 'img/padrao.png', '10/50', 'Apr 19, 2011'),
            new SteamGame(2, 'Half-Life 2', 120, '2h', 'R$ 9,99', 9.99, 'FPS', 'img/padrao.png', '5/30', 'Nov 16, 2004'),
        ];

        return new GameCollection($profile, $games, ['source' => 'cache']);
    }
}
