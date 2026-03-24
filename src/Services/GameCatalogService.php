<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

use Anderson\SteamGames\Support\TextHelper;

final class GameCatalogService
{
    public function applyFilters(array $games, string $playedFilter, string $priceFilter, string $achievementFilter): array
    {
        return array_values(array_filter($games, static function (array $game) use ($playedFilter, $priceFilter, $achievementFilter): bool {
            $minutes = (int) ($game['tempo_jogado_minutos'] ?? 0);
            $price = TextHelper::parsePrice((string) ($game['preco_atual'] ?? 'Preço não disponível'));
            $hasAchievements = isset($game['conquistas']) && $game['conquistas'] !== 'Jogo sem conquistas';

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
    }

    public function sortGames(array $games, string $orderBy): array
    {
        usort($games, static function (array $a, array $b) use ($orderBy): int {
            return match ($orderBy) {
                'nome' => strcmp((string) $a['nome'], (string) $b['nome']),
                'data_lancamento' => (int) strtotime((string) ($b['data_lancamento'] ?? '')) <=> (int) strtotime((string) ($a['data_lancamento'] ?? '')),
                'preco_atual' => TextHelper::parsePrice((string) $b['preco_atual']) <=> TextHelper::parsePrice((string) $a['preco_atual']),
                default => ((int) ($b['tempo_jogado_minutos'] ?? 0)) <=> ((int) ($a['tempo_jogado_minutos'] ?? 0)),
            };
        });

        return $games;
    }

    public function totals(array $games): array
    {
        $totalMinutes = 0;
        $totalValue = 0.0;

        foreach ($games as $game) {
            $totalMinutes += (int) ($game['tempo_jogado_minutos'] ?? 0);
            $totalValue += TextHelper::parsePrice((string) ($game['preco_atual'] ?? 'Preço não disponível'));
        }

        return [
            'total_minutes' => $totalMinutes,
            'total_value' => $totalValue,
        ];
    }
}
