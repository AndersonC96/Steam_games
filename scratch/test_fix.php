<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Anderson\SteamGames\Infrastructure\SteamApiClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$apiKey = $_ENV['STEAM_API_KEY'];
$client = new SteamApiClient();

try {
    echo "Testando resolveSteamId...\n";
    $steamId = $client->resolveSteamId('AndersonC96', $apiKey);
    echo "SteamID: $steamId\n";

    echo "Testando getAppDetails (sincrono)...\n";
    $resp = $client->getAppDetails(440); // TF2
    $data = json_decode((string) $resp->getBody(), true);
    echo "Jogo: " . $data[440]['data']['name'] . "\n";

    echo "Teste concluído com sucesso!\n";
} catch (\Throwable $e) {
    echo "ERRO NO TESTE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
