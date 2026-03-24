<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/steam_api.php';

use Dotenv\Dotenv;

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function parsePrice(string $value): float {
    if ($value === 'Preço não disponível') {
        return 0.0;
    }
    return (float) str_replace(',', '.', preg_replace('/[^0-9,]/', '', $value));
}

function cleanDescription(string $text, int $maxLength = 180): string {
    $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($decoded)));

    if ($plain === '') {
        return 'Descrição não disponível';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($plain) > $maxLength ? mb_substr($plain, 0, $maxLength) . '...' : $plain;
    }

    return strlen($plain) > $maxLength ? substr($plain, 0, $maxLength) . '...' : $plain;
}

$allowedOrders = ['nome', 'data_lancamento', 'tempo_jogado', 'preco_atual'];
$orderBy = $_GET['order_by'] ?? 'tempo_jogado';
$orderBy = in_array($orderBy, $allowedOrders, true) ? $orderBy : 'tempo_jogado';
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$errorMessage = '';
$apiKey = '';
$defaultUsername = '';
$detalhesJogos = null;

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    $apiKey = $_ENV['STEAM_API_KEY'] ?? '';
    $defaultUsername = $_ENV['STEAM_USERNAME'] ?? '';
} catch (Exception $e) {
    $errorMessage = 'Não foi possível carregar o ambiente. Verifique o arquivo .env';
}

$username = trim((string) ($_GET['username'] ?? ''));
$inputValue = $username !== '' ? $username : $defaultUsername;

if (isset($_GET['username'])) {
    if ($username === '') {
        $errorMessage = 'Digite um nome de usuário Steam para buscar.';
    } elseif ($apiKey === '') {
        $errorMessage = 'A chave STEAM_API_KEY não foi encontrada no arquivo .env.';
    } else {
        $detalhesJogos = getUserGameDetails($username, $apiKey);
        if (is_string($detalhesJogos)) {
            $errorMessage = $detalhesJogos;
            $detalhesJogos = null;
        }
    }
}

$games = [];
$profile = [];
$itemsPerPage = 9;
$totalPages = 1;
$totalGames = 0;
$totalMinutes = 0;
$totalValue = 0.0;

if (is_array($detalhesJogos)) {
    $games = $detalhesJogos['games'];
    $profile = $detalhesJogos['profile'];

    usort($games, static function ($a, $b) use ($orderBy): int {
        switch ($orderBy) {
            case 'nome':
                return strcmp($a['nome'], $b['nome']);
            case 'data_lancamento':
                return (int) strtotime((string) ($b['data_lancamento'] ?? '')) <=> (int) strtotime((string) ($a['data_lancamento'] ?? ''));
            case 'preco_atual':
                return parsePrice($b['preco_atual']) <=> parsePrice($a['preco_atual']);
            case 'tempo_jogado':
            default:
                return ((int) ($b['tempo_jogado_minutos'] ?? 0)) <=> ((int) ($a['tempo_jogado_minutos'] ?? 0));
        }
    });

    foreach ($games as $jogo) {
        $totalMinutes += (int) ($jogo['tempo_jogado_minutos'] ?? 0);
        $totalValue += parsePrice($jogo['preco_atual']);
    }

    $totalGames = count($games);
    $totalPages = max(1, (int) ceil($totalGames / $itemsPerPage));
    $currentPage = min($currentPage, $totalPages);
    $startIndex = ($currentPage - 1) * $itemsPerPage;
    $games = array_slice($games, $startIndex, $itemsPerPage);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steam Spotlight | Portfolio Project</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #08111f;
            --bg-soft: #0f1f36;
            --panel: rgba(10, 22, 39, 0.72);
            --line: rgba(139, 196, 255, 0.2);
            --text: #e7eef8;
            --muted: #9db3cd;
            --accent: #27d0f5;
            --accent-soft: #89efff;
            --danger: #ff7373;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--text);
            font-family: 'Sora', sans-serif;
            background: radial-gradient(circle at 20% 10%, #153057 0%, var(--bg) 45%, #050b16 100%);
            min-height: 100vh;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            width: 42vw;
            height: 42vw;
            filter: blur(70px);
            z-index: -1;
            opacity: 0.35;
            pointer-events: none;
        }

        body::before {
            background: #2eeafc;
            top: -14vw;
            right: -12vw;
        }

        body::after {
            background: #6ba7ff;
            bottom: -20vw;
            left: -16vw;
        }

        .page {
            width: min(1200px, 92vw);
            margin: 0 auto;
            padding: 26px 0 64px;
            animation: reveal 0.8s ease-out;
        }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
            justify-content: space-between;
            background: rgba(8, 17, 31, 0.78);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 270px;
        }

        .brand img {
            width: 40px;
            height: 40px;
        }

        .brand-title {
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
            font-size: 1.2rem;
            letter-spacing: 0.02em;
        }

        .brand-subtitle {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 0.86rem;
        }

        .search {
            display: grid;
            grid-template-columns: 1fr auto;
            width: min(700px, 100%);
            gap: 8px;
            margin-left: auto;
        }

        .search input,
        .sort-select {
            border: 1px solid var(--line);
            background: rgba(12, 26, 48, 0.85);
            color: var(--text);
            border-radius: 12px;
            padding: 12px 14px;
            font: inherit;
            outline: none;
        }

        .search input:focus,
        .sort-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(39, 208, 245, 0.15);
        }

        .button {
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font: 600 0.95rem 'Space Grotesk', sans-serif;
            letter-spacing: 0.02em;
            color: #01253a;
            background: linear-gradient(120deg, var(--accent), var(--accent-soft));
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(39, 208, 245, 0.34);
        }

        .hero {
            margin: 28px 0 18px;
            border-radius: 24px;
            border: 1px solid var(--line);
            background:
                linear-gradient(120deg, rgba(14, 36, 64, 0.92) 0%, rgba(7, 19, 34, 0.9) 60%),
                url('https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1600&q=80') center/cover;
            padding: 34px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: auto -80px -80px auto;
            width: 220px;
            height: 220px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 50%;
        }

        .hero h2 {
            margin: 0;
            font: 700 2rem/1.12 'Space Grotesk', sans-serif;
            max-width: 760px;
        }

        .hero p {
            color: var(--muted);
            font-size: 1rem;
            max-width: 760px;
            margin: 12px 0 0;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .build-badge {
            display: inline-block;
            margin-top: 12px;
            font-size: 0.76rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #052239;
            background: linear-gradient(120deg, #78ecff, #c3f6ff);
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 700;
        }

        .chip {
            font-size: 0.82rem;
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #dbe8ff;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
        }

        .alert {
            margin-top: 14px;
            border: 1px solid rgba(255, 115, 115, 0.4);
            background: rgba(80, 18, 18, 0.45);
            color: #ffd2d2;
            border-radius: 12px;
            padding: 14px 16px;
        }

        .profile {
            margin-top: 24px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--panel);
            backdrop-filter: blur(12px);
            padding: 20px;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(260px, 1.05fr) 1.3fr;
        }

        .profile-card {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            background: rgba(7, 18, 32, 0.62);
            padding: 18px;
        }

        .profile-main {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .avatar {
            width: 76px;
            height: 76px;
            border-radius: 18px;
            border: 2px solid rgba(39, 208, 245, 0.5);
            object-fit: cover;
        }

        .profile-main h3 {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.25rem;
        }

        .profile-main p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .stat {
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(12, 26, 48, 0.7);
            padding: 14px;
        }

        .stat-label {
            display: block;
            color: var(--muted);
            font-size: 0.8rem;
            margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.2rem;
        }

        .controls {
            margin: 24px 0 12px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .controls h4 {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.12rem;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .game {
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            background: rgba(8, 19, 34, 0.82);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.25s ease, border-color 0.25s ease;
        }

        .game:hover {
            transform: translateY(-5px);
            border-color: rgba(39, 208, 245, 0.45);
        }

        .game-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }

        .game-content {
            padding: 14px;
        }

        .game-title {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.05rem;
        }

        .game-ach {
            margin: 6px 0 10px;
            color: #87f4d1;
            font-size: 0.86rem;
        }

        .game-description {
            margin: 0 0 10px;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.5;
            min-height: 74px;
        }

        .game-meta {
            display: grid;
            gap: 6px;
            font-size: 0.86rem;
        }

        .game-meta strong {
            color: #c9d9ef;
        }

        .pagination {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .page-link {
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 8px 12px;
            min-width: 40px;
            text-align: center;
            background: rgba(12, 26, 48, 0.75);
            transition: all 0.2s ease;
        }

        .page-link:hover {
            border-color: var(--accent);
            color: var(--accent-soft);
        }

        .page-link.active {
            border-color: transparent;
            background: linear-gradient(120deg, var(--accent), var(--accent-soft));
            color: #01253a;
            font-weight: 700;
        }

        @keyframes reveal {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1024px) {
            .profile,
            .games-grid {
                grid-template-columns: 1fr 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 740px) {
            .page {
                width: min(1200px, 94vw);
            }

            .hero {
                padding: 24px;
            }

            .hero h2 {
                font-size: 1.6rem;
            }

            .search {
                grid-template-columns: 1fr;
            }

            .profile,
            .games-grid,
            .stats {
                grid-template-columns: 1fr;
            }

            .controls {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="topbar">
            <div class="brand">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png" alt="Logo Steam">
                <div>
                    <h1 class="brand-title">Steam Spotlight</h1>
                    <p class="brand-subtitle">Projeto de portfolio em PHP consumindo Steam Web API</p>
                </div>
            </div>

            <form method="GET" action="" class="search">
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Digite o nome da URL da Steam. Ex: gaben"
                    required
                    value="<?php echo e($inputValue); ?>"
                >
                <button type="submit" class="button">Buscar perfil</button>
            </form>
        </header>

        <section class="hero">
            <h2>Visualize, filtre e apresente bibliotecas da Steam em uma interface moderna e pronta para portfolio.</h2>
            <p>Este projeto reúne integração com múltiplos endpoints da Steam, paginação, ordenação e visualização de catálogo com métricas de usuário em tempo real.</p>
            <span class="build-badge">Portfolio Build 2026.03.23</span>
            <div class="chips">
                <span class="chip">PHP + Composer</span>
                <span class="chip">Steam Web API</span>
                <span class="chip">Arquitetura simples e legível</span>
                <span class="chip">UI Responsiva e animada</span>
            </div>
        </section>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($profile)): ?>
            <section class="profile">
                <div class="profile-card">
                    <div class="profile-main">
                        <img class="avatar" src="<?php echo e($profile['avatar']); ?>" alt="Avatar do usuário">
                        <div>
                            <h3><?php echo e($profile['username']); ?></h3>
                            <p>Criada em: <?php echo e($profile['account_created']); ?></p>
                            <p>País: <?php echo e($profile['country']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="stats">
                    <article class="stat">
                        <span class="stat-label">Jogos na biblioteca</span>
                        <strong class="stat-value"><?php echo e($totalGames); ?></strong>
                    </article>
                    <article class="stat">
                        <span class="stat-label">Horas jogadas</span>
                        <strong class="stat-value"><?php echo e(number_format($totalMinutes / 60, 1, ',', '.')); ?>h</strong>
                    </article>
                    <article class="stat">
                        <span class="stat-label">Valor estimado</span>
                        <strong class="stat-value">R$ <?php echo e(number_format($totalValue, 2, ',', '.')); ?></strong>
                    </article>
                </div>
            </section>

            <section class="controls">
                <h4>Jogos exibidos na página <?php echo e($currentPage); ?> de <?php echo e($totalPages); ?></h4>
                <form method="GET" action="">
                    <input type="hidden" name="username" value="<?php echo e($username); ?>">
                    <label for="order_by">Ordenar por </label>
                    <select id="order_by" name="order_by" class="sort-select" onchange="this.form.submit()">
                        <option value="tempo_jogado"<?php echo $orderBy === 'tempo_jogado' ? ' selected' : ''; ?>>Tempo jogado</option>
                        <option value="preco_atual"<?php echo $orderBy === 'preco_atual' ? ' selected' : ''; ?>>Preço atual</option>
                        <option value="data_lancamento"<?php echo $orderBy === 'data_lancamento' ? ' selected' : ''; ?>>Data de lançamento</option>
                        <option value="nome"<?php echo $orderBy === 'nome' ? ' selected' : ''; ?>>Nome</option>
                    </select>
                </form>
            </section>

            <section class="games-grid">
                <?php foreach ($games as $jogo): ?>
                    <article class="game">
                        <img class="game-image" src="<?php echo e($jogo['capa']); ?>" alt="Capa de <?php echo e($jogo['nome']); ?>">
                        <div class="game-content">
                            <h5 class="game-title"><?php echo e($jogo['nome']); ?></h5>
                            <p class="game-ach"><?php echo e($jogo['conquistas']); ?></p>
                            <p class="game-description"><?php echo e(cleanDescription($jogo['descricao'])); ?></p>
                            <div class="game-meta">
                                <span><strong>Tempo:</strong> <?php echo e($jogo['tempo_jogado']); ?></span>
                                <span><strong>Lançamento:</strong> <?php echo e($jogo['data_lancamento']); ?></span>
                                <span><strong>Preço:</strong> <?php echo e($jogo['preco_atual']); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Paginação de jogos">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a
                            class="page-link<?php echo $i === $currentPage ? ' active' : ''; ?>"
                            href="?username=<?php echo urlencode($username); ?>&order_by=<?php echo urlencode($orderBy); ?>&page=<?php echo $i; ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>