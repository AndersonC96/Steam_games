<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Tests\Unit;

use Anderson\SteamGames\Models\SteamGame;
use Anderson\SteamGames\Services\GameCatalogService;
use RuntimeException;

final class GameCatalogServiceTest
{
    public function run(): void
    {
        $this->testFilters();
        $this->testSortByPlaytime();
        $this->testTotals();
        $this->testFiltersNoMatches();
        $this->testSortByPrice();
    }

    private function testFilters(): void
    {
        $service = new GameCatalogService();
        $games = [
            $this->createGame('A', 120, 10.0, '1/10 Conquistas'),
            $this->createGame('B', 0, 0.0, 'Jogo sem conquistas'),
        ];

        $filtered = $service->applyFilters($games, 'jogados', 'pagos', 'com');
        $this->assertSame(1, count($filtered), 'filters should keep only matching games');
        $this->assertSame('A', $filtered[0]->name, 'filters should keep game A');
    }

    private function testSortByPlaytime(): void
    {
        $service = new GameCatalogService();
        $games = [
            $this->createGame('A', 10),
            $this->createGame('B', 200),
        ];

        $sorted = $service->sortGames($games, 'tempo_jogado');
        $this->assertSame('B', $sorted[0]->name, 'tempo_jogado sort should put highest playtime first');
    }

    private function testTotals(): void
    {
        $service = new GameCatalogService();
        $games = [
            $this->createGame('A', 60, 10.0),
            $this->createGame('B', 30, 20.0),
        ];

        $totals = $service->totals($games);
        $this->assertSame(90, $totals['total_minutes'], 'totals should sum minutes');
        $this->assertSame(30.0, $totals['total_value'], 'totals should sum prices');
    }

    private function testFiltersNoMatches(): void
    {
        $service = new GameCatalogService();
        $games = [
            $this->createGame('A', 0, 0.0, 'Jogo sem conquistas'),
        ];

        $filtered = $service->applyFilters($games, 'jogados', 'pagos', 'com');
        $this->assertSame(0, count($filtered), 'filters should return empty array when no game matches criteria');
    }

    private function testSortByPrice(): void
    {
        $service = new GameCatalogService();
        $games = [
            $this->createGame('A', 10, 2.0),
            $this->createGame('B', 10, 40.0),
        ];

        $sorted = $service->sortGames($games, 'preco_atual');
        $this->assertSame('B', $sorted[0]->name, 'preco_atual sort should put highest price first');
    }

    private function createGame(string $name, int $playtime, float $price = 0.0, string $ach = 'Jogo sem conquistas'): SteamGame
    {
        return new SteamGame(
            123,
            $name,
            $playtime,
            '',
            '',
            $price,
            '',
            '',
            $ach,
            ''
        );
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' | expected: ' . var_export($expected, true) . ' got: ' . var_export($actual, true));
        }
    }
}
