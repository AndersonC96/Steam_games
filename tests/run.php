<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Unit/TextHelperTest.php';
require __DIR__ . '/Unit/GameCatalogServiceTest.php';
require __DIR__ . '/Integration/DashboardControllerIntegrationTest.php';

use Anderson\SteamGames\Tests\Integration\DashboardControllerIntegrationTest;
use Anderson\SteamGames\Tests\Unit\GameCatalogServiceTest;
use Anderson\SteamGames\Tests\Unit\TextHelperTest;

$tests = [
    new TextHelperTest(),
    new GameCatalogServiceTest(),
    new DashboardControllerIntegrationTest(),
];

foreach ($tests as $test) {
    $test->run();
}

echo "All tests passed\n";
