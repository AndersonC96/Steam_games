<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);

require $rootPath . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

// Centralized config access
$config = require $rootPath . '/config/app.php';

return [
    'root_path' => $rootPath,
    'config' => $config,
];
