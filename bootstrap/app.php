<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);

require $rootPath . '/vendor/autoload.php';

// Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

// Validação básica de segurança: Verifica se o arquivo .env existe em ambiente local
// Se não existir, o sistema ainda funciona no modo demo, mas alertamos sobre a ausência da chave
$hasApiKey = !empty($_ENV['STEAM_API_KEY']);
$isDemo = isset($_GET['demo']) && $_GET['demo'] === '1';

if (!$hasApiKey && !$isDemo && PHP_SAPI !== 'cli') {
    // Definimos uma flag para o controller tratar esse aviso amigavelmente
    $GLOBALS['CONFIG_WARNING'] = 'Aviso: Arquivo .env não configurado ou STEAM_API_KEY ausente. Use o Modo Demo para testar.';
}

// Centralized config access
$config = require $rootPath . '/config/app.php';

return [
    'root_path' => $rootPath,
    'config' => $config,
];
