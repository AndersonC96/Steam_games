<?php

declare(strict_types=1);

use Anderson\SteamGames\Controllers\DashboardController;
use Anderson\SteamGames\Services\CacheService;
use Anderson\SteamGames\Services\SteamApiService;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$rootPath = $app['root_path'];
$config = $app['config'];

$cacheService = new CacheService($config['cache_dir']);
$steamApiService = new SteamApiService($cacheService, (int) $config['cache_ttl_seconds']);
$controller = new DashboardController($steamApiService, $config, $rootPath);

$data = $controller->handle($_GET, $_SESSION, $_SERVER);
$controller->render($data);
