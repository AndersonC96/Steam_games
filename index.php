<?php
    require 'vendor/autoload.php';
    require 'steam_api.php';
    $username = 'AndersonC96';
    $apiKey = 'ABB2DF2C7A82FC38765A0CC192B77798';
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