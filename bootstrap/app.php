<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);

require $rootPath . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

return [
    'root_path' => $rootPath,
    'config' => require $rootPath . '/config/app.php',
];
