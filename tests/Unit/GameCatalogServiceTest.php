<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Tests\Unit;

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
            ['nome' => 'A', 'tempo_jogado_minutos' => 120, 'preco_atual' => 'R$ 10,00', 'conquistas' => '1/10 Conquistas'],
            ['nome' => 'B', 'tempo_jogado_minutos' => 0, 'preco_atual' => 'Preço não disponível', 'conquistas' => 'Jogo sem conquistas'],
        ];

        $filtered = $service->applyFilters($games, 'jogados', 'pagos', 'com');
        $this->assertSame(1, count($filtered), 'filters should keep only matching games');
        $this->assertSame('A', $filtered[0]['nome'], 'filters should keep game A');
    }

    private function testSortByPlaytime(): void
    {
        $service = new GameCatalogService();
        $games = [
            ['nome' => 'A', 'tempo_jogado_minutos' => 10, 'preco_atual' => 'R$ 5,00', 'data_lancamento' => 'Jan 01, 2020'],
            ['nome' => 'B', 'tempo_jogado_minutos' => 200, 'preco_atual' => 'R$ 5,00', 'data_lancamento' => 'Jan 01, 2020'],
        ];

        $sorted = $service->sortGames($games, 'tempo_jogado');
        $this->assertSame('B', $sorted[0]['nome'], 'tempo_jogado sort should put highest playtime first');
    }

    private function testTotals(): void
    {
        $service = new GameCatalogService();
        $games = [
            ['tempo_jogado_minutos' => 60, 'preco_atual' => 'R$ 10,00'],
            ['tempo_jogado_minutos' => 30, 'preco_atual' => 'R$ 20,00'],
        ];

        $totals = $service->totals($games);
        $this->assertSame(90, $totals['total_minutes'], 'totals should sum minutes');
        $this->assertSame(30.0, $totals['total_value'], 'totals should sum prices');
    }

    private function testFiltersNoMatches(): void
    {
        $service = new GameCatalogService();
        $games = [
            ['nome' => 'A', 'tempo_jogado_minutos' => 0, 'preco_atual' => 'Preço não disponível', 'conquistas' => 'Jogo sem conquistas'],
        ];

        $filtered = $service->applyFilters($games, 'jogados', 'pagos', 'com');
        $this->assertSame(0, count($filtered), 'filters should return empty array when no game matches criteria');
    }

    private function testSortByPrice(): void
    {
        $service = new GameCatalogService();
        $games = [
            ['nome' => 'A', 'tempo_jogado_minutos' => 10, 'preco_atual' => 'R$ 2,00', 'data_lancamento' => 'Jan 01, 2020'],
            ['nome' => 'B', 'tempo_jogado_minutos' => 10, 'preco_atual' => 'R$ 40,00', 'data_lancamento' => 'Jan 01, 2020'],
        ];

        $sorted = $service->sortGames($games, 'preco_atual');
        $this->assertSame('B', $sorted[0]['nome'], 'preco_atual sort should put highest price first');
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' | expected: ' . var_export($expected, true) . ' got: ' . var_export($actual, true));
        }
    }
}
