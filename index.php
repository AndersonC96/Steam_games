<!DOCTYPE html>
<html lang="pt-BR" class="bg-steam-dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Busca de Perfil Steam</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .bg-steam-dark {
                background-color: #171A21;
            }
            .text-steam-blue {
                color: #66C0F4;
            }
            .glass-card {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body class="text-gray-100 bg-steam-dark">
        <nav class="bg-gray-800 p-4 mb-6">
            <div class="container mx-auto flex justify-between items-center">
                <a href="#" class="flex-shrink-0">
                    <img src="https://cdn.cloudflare.steamstatic.com/steamcommunity/public/images/avatars/0a/0a99b394a0d21e788ac16a025ff6213d7a6e92b2_full.jpg" alt="Steam Logo" class="w-12 h-12">
                </a>
                <form method="GET" action="" class="flex items-center mx-auto">
                    <input type="text" id="username" name="username" placeholder="Nome de Usuário Steam" required class="p-2 rounded-l bg-gray-700 border border-gray-600 text-gray-100 focus:outline-none">
                    <button type="submit" class="p-2 bg-steam-blue text-white rounded-r hover:bg-blue-600 transition">Buscar</button>
                </form>
            </div>
        </nav>
        <div class="container mx-auto p-4">
            <?php
                if (isset($_GET['username'])) {
                    require 'vendor/autoload.php';
                    require 'steam_api.php';
                    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
                    $dotenv->load();
                    $username = htmlspecialchars($_GET['username']);
                    $apiKey = $_ENV['STEAM_API_KEY'];
                    $orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'nome';
                    $detalhesJogos = getUserGameDetails($username, $apiKey);
                    if (is_string($detalhesJogos)) {
                        echo "<p class='text-red-500'>{$detalhesJogos}</p>";
                    } else {
                        usort($detalhesJogos['games'], function ($a, $b) use ($orderBy) {
                            switch ($orderBy) {
                                case 'nome':
                                    return strcmp($a['nome'], $b['nome']);
                                case 'data_lancamento':
                                    return strtotime($b['data_lancamento']) - strtotime($a['data_lancamento']);
                                case 'tempo_jogado':
                                    return $b['tempo_jogado'] - $a['tempo_jogado'];
                                case 'preco_atual':
                                    $priceA = $a['preco_atual'] !== 'Preço não disponível' ? floatval(str_replace(['R$', ','], ['', '.'], $a['preco_atual'])) : 0;
                                    $priceB = $b['preco_atual'] !== 'Preço não disponível' ? floatval(str_replace(['R$', ','], ['', '.'], $b['preco_atual'])) : 0;
                                    return $priceB - $priceA;
                                default:
                                return 0;
                            }
                        });
                        $valorTotal = 0;
                        foreach ($detalhesJogos['games'] as $jogo) {
                            if ($jogo['preco_atual'] !== 'Preço não disponível') {
                                $valorJogo = floatval(str_replace(['R$', ','], ['', '.'], $jogo['preco_atual']));
                                $valorTotal += $valorJogo;
                            }
                        }
                        echo "<div class='flex justify-center mb-8'>";
                        echo "<div class='glass-card p-6 rounded-lg text-center'>";
                        echo "<h2 class='text-2xl font-semibold mb-4'>{$detalhesJogos['profile']['username']}</h2>";
                        echo "<img src='{$detalhesJogos['profile']['avatar']}' alt='Avatar do Usuário' class='w-32 h-32 rounded-full mx-auto mb-4'>";
                        echo "<p class='text-lg mb-2'><strong>Data de Criação:</strong> {$detalhesJogos['profile']['account_created']}</p>";
                        echo "<p class='text-lg font-semibold'><strong>Valor Total dos Jogos:</strong> R$ " . number_format($valorTotal, 2, ',', '.') . "</p>";
                        echo "</div>";
                        echo "</div>";
                        $itemsPerPage = 12;
                        $totalGames = count($detalhesJogos['games']);
                        $totalPages = ceil($totalGames / $itemsPerPage);
                        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        $startIndex = ($currentPage - 1) * $itemsPerPage;
                        $currentGames = array_slice($detalhesJogos['games'], $startIndex, $itemsPerPage);
                        echo "<div class='flex justify-between items-center mb-4'>";
                        echo "<h3 class='text-xl font-semibold'>Jogos</h3>";
                        echo "<form method='GET' action='' class='flex items-center'>";
                        echo "<input type='hidden' name='username' value='{$username}'>";
                        echo "<select name='order_by' class='p-2 rounded bg-gray-700 text-gray-100 focus:outline-none' onchange='this.form.submit()'>";
                        echo "<option value='nome'" . ($orderBy == 'nome' ? ' selected' : '') . ">Nome</option>";
                        echo "<option value='data_lancamento'" . ($orderBy == 'data_lancamento' ? ' selected' : '') . ">Data de Lançamento</option>";
                        echo "<option value='tempo_jogado'" . ($orderBy == 'tempo_jogado' ? ' selected' : '') . ">Tempo Jogado</option>";
                        echo "<option value='preco_atual'" . ($orderBy == 'preco_atual' ? ' selected' : '') . ">Preço Atual</option>";
                        echo "</select>";
                        echo "</form>";
                        echo "</div>";
                        echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>";
                        foreach ($currentGames as $jogo) {
                            echo "<div class='glass-card p-4 rounded-lg'>";
                            echo "<img src='{$jogo['capa']}' alt='Capa do jogo' class='w-full h-auto mb-4 rounded'>";
                            echo "<h4 class='text-lg font-semibold mb-2'>{$jogo['nome']} ({$jogo['conquistas']})</h4>";
                            echo "<p><strong>Tempo jogado:</strong> {$jogo['tempo_jogado']}</p>";
                            echo "<p><strong>Descrição:</strong> {$jogo['descricao']}</p>";
                            echo "<p><strong>Data de lançamento:</strong> {$jogo['data_lancamento']}</p>";
                            echo "<p><strong>Preço atual:</strong> {$jogo['preco_atual']}</p>";
                            echo "</div>";
                        }
                        echo "</div>";
                        echo "<div class='flex justify-center mt-8'>";
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $activeClass = $i === $currentPage ? 'bg-steam-blue text-white' : 'bg-gray-700 text-gray-300';
                            echo "<a href='?username={$username}&order_by={$orderBy}&page={$i}' class='mx-2 px-4 py-2 rounded {$activeClass} hover:bg-blue-600 transition'>{$i}</a>";
                        }
                        echo "</div>";
                    }
                }
            ?>
        </div>
    </body>
</html>