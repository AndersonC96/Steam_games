<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Busca de Perfil Steam</title>
    </head>
    <body>
        <h1>Busca de Perfil Steam</h1>
        <form method="GET" action="">
            <label for="username">Nome de Usuário Steam:</label>
            <input type="text" id="username" name="username" required>
            <button type="submit">Buscar</button>
        </form>
        <?php
            if (isset($_GET['username'])) {
                require 'vendor/autoload.php';
                require 'steam_api.php';
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
                $dotenv->load();
                $username = htmlspecialchars($_GET['username']);
                $apiKey = $_ENV['STEAM_API_KEY'];
                $detalhesJogos = getUserGameDetails($username, $apiKey);
                if (is_string($detalhesJogos)) {
                    echo "<p>{$detalhesJogos}</p>";
                } else {
                    echo "<h2>Perfil do Usuário</h2>";
                    echo "<p><strong>Nome de Usuário:</strong> {$detalhesJogos['profile']['username']}</p>";
                    echo "<p><strong>Data de Criação:</strong> {$detalhesJogos['profile']['account_created']}</p>";
                    $valorTotal = 0;
                    foreach ($detalhesJogos['games'] as $jogo) {
                        $valorJogo = floatval(str_replace(['R$', ','], ['', '.'], $jogo['preco_atual']));
                        $valorTotal += $valorJogo;
                    }
                    echo "<h3>Valor Total dos Jogos: R$ " . number_format($valorTotal, 2, ',', '.') . "</h3>";
                    echo "<img src='{$detalhesJogos['profile']['avatar']}' alt='Avatar do Usuário' style='width:100px;height:auto;'><br><br>";
                    $valorTotal = 0;
                    echo "<h3>Jogos</h3>";
                    foreach ($detalhesJogos['games'] as $jogo) {
                        echo "<img src='{$jogo['capa']}' alt='Capa do jogo' style='width:200px;height:auto;'>";
                        echo "<h4>{$jogo['nome']} ({$jogo['conquistas']})</h4>";
                        echo "<p><strong>Tempo jogado:</strong> {$jogo['tempo_jogado']}</p>";
                        echo "<p><strong>Descrição:</strong> {$jogo['descricao']}</p>";
                        echo "<p><strong>Data de lançamento:</strong> {$jogo['data_lancamento']}</p>";
                        echo "<p><strong>Preço atual:</strong> {$jogo['preco_atual']}</p>";
                        if ($jogo['preco_atual'] !== 'Preço não disponível') {
                            $valorJogo = floatval(str_replace(['R$', ','], ['', '.'], $jogo['preco_atual']));
                            $valorTotal += $valorJogo;
                        }
                        echo "<hr>";
                    }
                    echo "<h3>Valor Total dos Jogos: R$ " . number_format($valorTotal, 2, ',', '.') . "</h3>";
                }
            }
        ?>
    </body>
</html>