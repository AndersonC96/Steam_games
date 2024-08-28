<?php
    require 'vendor/autoload.php';
    require 'steam_api.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $username = $_ENV['STEAM_USERNAME'];
    $apiKey = $_ENV['STEAM_API_KEY'];
    $detalhesJogos = getUserGameDetails($username, $apiKey);
    if(is_string($detalhesJogos)){
        echo $detalhesJogos;
    }else{
        foreach($detalhesJogos as $jogo){
            echo "<img src='{$jogo['capa']}' alt='Capa do jogo' style='width:200px;height:auto;'>";
            echo "<h2>{$jogo['nome']} | {$jogo['conquistas']}</h2>";
            echo "<p><strong>Tempo jogado:</strong> {$jogo['tempo_jogado']}</p>";
            echo "<p><strong>Descrição:</strong> {$jogo['descricao']}</p>";
            echo "<p><strong>Preço atual:</strong> {$jogo['preco_atual']}</p>";
            echo "<hr>";
        }
    }