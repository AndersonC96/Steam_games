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
                    <img src="data:image/jpeg;base64,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" alt="Steam Logo" class="w-12 h-12">
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