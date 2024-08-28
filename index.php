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
            echo "<p><strong>Nome:</strong> {$jogo['nome']}</p>";
            echo "<p><strong>Tempo jogado:</strong> {$jogo['tempo_jogado']}</p>";
            echo "<p><strong>Preço atual:</strong> {$jogo['preco_atual']}</p>";
            echo "<hr>";
        }
    }