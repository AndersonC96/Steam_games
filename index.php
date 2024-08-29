<?php
    require 'vendor/autoload.php';
    require 'steam_api.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $username = $_ENV['STEAM_USERNAME'];
    $apiKey = $_ENV['STEAM_API_KEY'];
    $detalhesJogos = getUserGameDetails($username, $apiKey);
    if (is_string($detalhesJogos)) {
        echo $detalhesJogos;
    } else {
        echo "<p><strong>{$detalhesJogos['profile']['username']}</p>";
        echo "<p><strong>Membro desde</strong>: {$detalhesJogos['profile']['account_created']}</p>";
        echo "<img src='{$detalhesJogos['profile']['avatar']}' alt='Avatar do Usuário' style='width:100px;height:auto;'><br><br>";
        echo "<h2>Jogos</h2>";
        foreach ($detalhesJogos['games'] as $jogo) {
            echo "<img src='{$jogo['capa']}' alt='Capa do jogo' style='width:200px;height:auto;'>";
            echo "<h3>{$jogo['nome']} ({$jogo['conquistas']})</h3>";
            echo "<p><strong>Tempo jogado</strong>: {$jogo['tempo_jogado']}</p>";
            echo "<p><strong>Descrição</strong>: {$jogo['descricao']}</p>";
            echo "<p><strong>Data de lançamento:</strong>: {$jogo['data_lancamento']}</p>";
            echo "<p><strong>Preço atual</strong>: {$jogo['preco_atual']}</p>";
            echo "<hr>";
        }
    }