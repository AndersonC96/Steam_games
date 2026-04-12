<?php

declare(strict_types=1);

use Anderson\SteamGames\Controllers\DashboardController;
use Anderson\SteamGames\Infrastructure\SteamApiClient;
use Anderson\SteamGames\Services\CacheService;
use Anderson\SteamGames\Services\DemoSteamApiService;
use Anderson\SteamGames\Services\GameCatalogService;
use Anderson\SteamGames\Services\SteamApiService;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$rootPath = $app['root_path'];
$config = $app['config'];

$cacheService = new CacheService($config['cache_dir']);
$isDemoMode = isset($_GET['demo']) && $_GET['demo'] === '1';

if ($isDemoMode) {
    $steamApiService = new DemoSteamApiService();
} else {
    $apiClient = new SteamApiClient();
    $steamApiService = new SteamApiService(
        $apiClient,
        $cacheService,
        (int) $config['cache_ttl_seconds'],
        (int) $config['max_games_to_process']
    );
}

$gameCatalogService = new GameCatalogService();
$controller = new DashboardController($steamApiService, $gameCatalogService, $config, $rootPath);

$data = $controller->handle($_GET, $_SESSION, $_SERVER);
$controller->render($data);
