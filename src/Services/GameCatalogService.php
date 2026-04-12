<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

use Anderson\SteamGames\Models\SteamGame;

final class GameCatalogService
{
    /**
     * @param SteamGame[] $games
     * @return SteamGame[]
     */
    public function applyFilters(array $games, string $playedFilter, string $priceFilter, string $achievementFilter): array
    {
        return array_values(array_filter($games, static function (SteamGame $game) use ($playedFilter, $priceFilter, $achievementFilter): bool {
            if ($playedFilter === 'jogados' && $game->playtimeMinutes <= 0) {
                return false;
            }

            if ($playedFilter === 'nao_jogados' && $game->playtimeMinutes > 0) {
                return false;
            }

            if ($priceFilter === 'gratis' && $game->priceValue > 0) {
                return false;
            }

            if ($priceFilter === 'pagos' && $game->priceValue <= 0) {
                return false;
            }

            $hasAchievements = $game->achievements !== 'Jogo sem conquistas';
            if ($achievementFilter === 'com' && !$hasAchievements) {
                return false;
            }

            if ($achievementFilter === 'sem' && $hasAchievements) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param SteamGame[] $games
     * @return SteamGame[]
     */
    public function sortGames(array $games, string $orderBy): array
    {
        usort($games, static function (SteamGame $a, SteamGame $b) use ($orderBy): int {
            return match ($orderBy) {
                'nome' => strcmp($a->name, $b->name),
                'data_lancamento' => (int) strtotime($b->releaseDate) <=> (int) strtotime($a->releaseDate),
                'preco_atual' => $b->priceValue <=> $a->priceValue,
                default => $b->playtimeMinutes <=> $a->playtimeMinutes,
            };
        });

        return $games;
    }

    /**
     * @param SteamGame[] $games
     */
    public function totals(array $games): array
    {
        $totalMinutes = 0;
        $totalValue = 0.0;

        foreach ($games as $game) {
            $totalMinutes += $game->playtimeMinutes;
            $totalValue += $game->priceValue;
        }

        return [
            'total_minutes' => $totalMinutes,
            'total_value' => $totalValue,
        ];
    }
}
