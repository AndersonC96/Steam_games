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

$allowedPlayed = ['todos', 'jogados', 'nao_jogados'];
$playedFilter = $_GET['played_filter'] ?? 'todos';
$playedFilter = in_array($playedFilter, $allowedPlayed, true) ? $playedFilter : 'todos';

$allowedPrice = ['todos', 'gratis', 'pagos'];
$priceFilter = $_GET['price_filter'] ?? 'todos';
$priceFilter = in_array($priceFilter, $allowedPrice, true) ? $priceFilter : 'todos';

$allowedAchievements = ['todos', 'com', 'sem'];
$achievementFilter = $_GET['achievement_filter'] ?? 'todos';
$achievementFilter = in_array($achievementFilter, $allowedAchievements, true) ? $achievementFilter : 'todos';

$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$errorMessage = '';
$apiKey = '';
$defaultUsername = '';
$detalhesJogos = null;
$queryTimeMs = 0.0;

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
        $queryStart = microtime(true);
        $detalhesJogos = getUserGameDetails($username, $apiKey);
        $queryTimeMs = (microtime(true) - $queryStart) * 1000;
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
$totalGamesBeforeFilter = 0;
$totalMinutes = 0;
$totalValue = 0.0;
$dataSourceLabel = '';

if (is_array($detalhesJogos)) {
    $games = $detalhesJogos['games'];
    $profile = $detalhesJogos['profile'];
    $dataSourceLabel = $detalhesJogos['meta']['source'] ?? '';
    $totalGamesBeforeFilter = count($games);

    $games = array_values(array_filter($games, static function ($jogo) use ($playedFilter, $priceFilter, $achievementFilter): bool {
        $minutes = (int) ($jogo['tempo_jogado_minutos'] ?? 0);
        $price = parsePrice((string) ($jogo['preco_atual'] ?? 'Preço não disponível'));
        $hasAchievements = isset($jogo['conquistas']) && $jogo['conquistas'] !== 'Jogo sem conquistas';

        if ($playedFilter === 'jogados' && $minutes <= 0) {
            return false;
        }

        if ($playedFilter === 'nao_jogados' && $minutes > 0) {
            return false;
        }

        if ($priceFilter === 'gratis' && $price > 0) {
            return false;
        }

        if ($priceFilter === 'pagos' && $price <= 0) {
            return false;
        }

        if ($achievementFilter === 'com' && !$hasAchievements) {
            return false;
        }

        if ($achievementFilter === 'sem' && $hasAchievements) {
            return false;
        }

        return true;
    }));

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
    <title>Steam Portfolio Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f3f6fb;
            --bg-soft: #ffffff;
            --panel: #ffffff;
            --line: #d8e1ec;
            --text: #1f2b3a;
            --muted: #627386;
            --accent: #1d70f2;
            --accent-soft: #4a93ff;
            --danger: #d23f3f;
        }

        html[data-theme='dark'] {
            --bg: #111827;
            --bg-soft: #1b2637;
            --panel: #1b2637;
            --line: #33465f;
            --text: #e8edf4;
            --muted: #a6b6c8;
            --accent: #6aa6ff;
            --accent-soft: #8cbbff;
            --danger: #ff8080;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
            background:
                radial-gradient(circle at 10% 10%, rgba(74, 147, 255, 0.08), transparent 40%),
                var(--bg);
            min-height: 100vh;
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
            background: var(--bg-soft);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(26, 42, 61, 0.08);
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
            font-family: 'IBM Plex Mono', monospace;
            margin: 0;
            font-size: 1.02rem;
            letter-spacing: 0.01em;
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
            background: #fff;
            color: var(--text);
            border-radius: 10px;
            padding: 12px 14px;
            font: inherit;
            outline: none;
        }

        .search input:focus,
        .sort-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(29, 112, 242, 0.15);
        }

        .button {
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font: 600 0.95rem 'IBM Plex Sans', sans-serif;
            letter-spacing: 0.02em;
            color: #fff;
            background: linear-gradient(120deg, var(--accent), var(--accent-soft));
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(29, 112, 242, 0.26);
        }

        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 10px;
            padding: 12px 16px;
            font: 600 0.9rem 'IBM Plex Sans', sans-serif;
            color: #24415f;
            border: 1px solid #c8d6e7;
            background: #fff;
            transition: all 0.2s ease;
        }

        .button-secondary:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }

        .hero {
            margin: 20px 0 16px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 28px;
            box-shadow: 0 6px 16px rgba(26, 42, 61, 0.06);
        }

        .kpi-strip {
            margin: 0 0 16px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .kpi {
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--bg-soft);
            padding: 12px;
        }

        .kpi small {
            color: var(--muted);
            display: block;
            font-size: 0.76rem;
            margin-bottom: 6px;
        }

        .kpi strong {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 1.02rem;
        }

        .hero h2 {
            margin: 0;
            font: 700 1.75rem/1.2 'IBM Plex Sans', sans-serif;
            max-width: 760px;
            color: #162334;
        }

        .hero p {
            color: var(--muted);
            font-size: 1rem;
            max-width: 760px;
            margin: 12px 0 0;
        }

        .alert {
            margin-top: 14px;
            border: 1px solid #f2cccc;
            background: #fff3f3;
            color: #8a2e2e;
            border-radius: 12px;
            padding: 14px 16px;
        }

        .profile {
            margin-top: 24px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel);
            padding: 20px;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(260px, 1.05fr) 1.3fr;
        }

        .profile-card {
            border: 1px solid #e3ebf4;
            border-radius: 10px;
            background: #fafcff;
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
            border-radius: 12px;
            border: 2px solid rgba(29, 112, 242, 0.25);
            object-fit: cover;
        }

        .profile-main h3 {
            margin: 0;
            font-family: 'IBM Plex Sans', sans-serif;
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
            border-radius: 10px;
            border: 1px solid #e3ebf4;
            background: #fafcff;
            padding: 14px;
        }

        .stat-label {
            display: block;
            color: var(--muted);
            font-size: 0.8rem;
            margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'IBM Plex Mono', monospace;
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
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 1.12rem;
        }

        .control-stack {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .control-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cache-tag {
            font-size: 0.78rem;
            padding: 5px 8px;
            border-radius: 999px;
            border: 1px solid #d5e4fb;
            color: #285ea7;
            background: #eef5ff;
        }

        .footer {
            margin-top: 28px;
            border: 1px solid var(--line);
            background: var(--bg-soft);
            border-radius: 10px;
            padding: 14px 16px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px;
            color: var(--muted);
            font-size: 0.86rem;
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .game {
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 6px 18px rgba(26, 42, 61, 0.06);
            transition: transform 0.25s ease, border-color 0.25s ease;
        }

        .game:hover {
            transform: translateY(-5px);
            border-color: rgba(29, 112, 242, 0.45);
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
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 1.05rem;
        }

        .game-ach {
            margin: 6px 0 10px;
            color: #2f6cbc;
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
            border-radius: 8px;
            padding: 8px 12px;
            min-width: 40px;
            text-align: center;
            background: #fff;
            transition: all 0.2s ease;
        }

        .page-link:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .page-link.active {
            border-color: transparent;
            background: linear-gradient(120deg, var(--accent), var(--accent-soft));
            color: #fff;
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

            .kpi-strip {
                grid-template-columns: 1fr;
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
                    <h1 class="brand-title">steam_library_explorer.php</h1>
                    <p class="brand-subtitle">Steam API integration with filtering, pagination and cache</p>
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
                <button type="submit" class="button">Buscar</button>
            </form>
            <div class="control-stack">
                <a class="button-secondary" href="?username=gaben&order_by=tempo_jogado">Demo rápida</a>
                <button id="theme-toggle" class="button-secondary" type="button">Alternar tema</button>
            </div>
        </header>

        <section class="hero">
            <h2>Dashboard de biblioteca Steam com foco em experiência de uso e performance.</h2>
            <p>Busca de perfil, enriquecimento de catálogo, filtros combinados e cache local para reduzir latência em consultas recorrentes.</p>
        </section>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($profile)): ?>
            <section class="kpi-strip">
                <article class="kpi">
                    <small>tempo de resposta</small>
                    <strong><?php echo e(number_format($queryTimeMs, 0, ',', '.')); ?> ms</strong>
                </article>
                <article class="kpi">
                    <small>fonte da consulta</small>
                    <strong><?php echo e($dataSourceLabel === 'cache' ? 'cache local' : 'steam api'); ?></strong>
                </article>
                <article class="kpi">
                    <small>volume total do perfil</small>
                    <strong><?php echo e($totalGamesBeforeFilter); ?> jogos</strong>
                </article>
            </section>

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
                <div class="control-stack">
                    <h4>
                        Jogos exibidos na página <?php echo e($currentPage); ?> de <?php echo e($totalPages); ?>
                        (<?php echo e($totalGames); ?> de <?php echo e($totalGamesBeforeFilter); ?> após filtros)
                    </h4>
                    <?php if ($dataSourceLabel === 'cache'): ?>
                        <span class="cache-tag">Origem: cache local</span>
                    <?php elseif ($dataSourceLabel === 'live'): ?>
                        <span class="cache-tag">Origem: Steam API</span>
                    <?php endif; ?>
                </div>

                <form method="GET" action="" class="control-form">
                    <input type="hidden" name="username" value="<?php echo e($username); ?>">
                    <label for="order_by">Ordenar</label>
                    <select id="order_by" name="order_by" class="sort-select" onchange="this.form.submit()">
                        <option value="tempo_jogado"<?php echo $orderBy === 'tempo_jogado' ? ' selected' : ''; ?>>Tempo jogado</option>
                        <option value="preco_atual"<?php echo $orderBy === 'preco_atual' ? ' selected' : ''; ?>>Preço atual</option>
                        <option value="data_lancamento"<?php echo $orderBy === 'data_lancamento' ? ' selected' : ''; ?>>Data de lançamento</option>
                        <option value="nome"<?php echo $orderBy === 'nome' ? ' selected' : ''; ?>>Nome</option>
                    </select>

                    <label for="played_filter">Jogo</label>
                    <select id="played_filter" name="played_filter" class="sort-select" onchange="this.form.submit()">
                        <option value="todos"<?php echo $playedFilter === 'todos' ? ' selected' : ''; ?>>Todos</option>
                        <option value="jogados"<?php echo $playedFilter === 'jogados' ? ' selected' : ''; ?>>Jogados</option>
                        <option value="nao_jogados"<?php echo $playedFilter === 'nao_jogados' ? ' selected' : ''; ?>>Não jogados</option>
                    </select>

                    <label for="price_filter">Preço</label>
                    <select id="price_filter" name="price_filter" class="sort-select" onchange="this.form.submit()">
                        <option value="todos"<?php echo $priceFilter === 'todos' ? ' selected' : ''; ?>>Todos</option>
                        <option value="gratis"<?php echo $priceFilter === 'gratis' ? ' selected' : ''; ?>>Grátis</option>
                        <option value="pagos"<?php echo $priceFilter === 'pagos' ? ' selected' : ''; ?>>Pagos</option>
                    </select>

                    <label for="achievement_filter">Conquistas</label>
                    <select id="achievement_filter" name="achievement_filter" class="sort-select" onchange="this.form.submit()">
                        <option value="todos"<?php echo $achievementFilter === 'todos' ? ' selected' : ''; ?>>Todos</option>
                        <option value="com"<?php echo $achievementFilter === 'com' ? ' selected' : ''; ?>>Com</option>
                        <option value="sem"<?php echo $achievementFilter === 'sem' ? ' selected' : ''; ?>>Sem</option>
                    </select>
                </form>
            </section>

            <section class="games-grid">
                <?php if (empty($games)): ?>
                    <div class="alert" style="grid-column: 1 / -1; margin: 0;">
                        Nenhum jogo corresponde aos filtros selecionados. Tente ajustar os filtros para ampliar os resultados.
                    </div>
                <?php else: ?>
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
                <?php endif; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Paginação de jogos">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a
                            class="page-link<?php echo $i === $currentPage ? ' active' : ''; ?>"
                            href="?username=<?php echo urlencode($username); ?>&order_by=<?php echo urlencode($orderBy); ?>&played_filter=<?php echo urlencode($playedFilter); ?>&price_filter=<?php echo urlencode($priceFilter); ?>&achievement_filter=<?php echo urlencode($achievementFilter); ?>&page=<?php echo $i; ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <footer class="footer">
            <span>Stack: PHP, GuzzleHTTP, Dotenv, Steam Web API, cache em arquivo</span>
            <span>GitHub: <a href="https://github.com/AndersonC96" target="_blank" rel="noreferrer">github.com/AndersonC96</a></span>
        </footer>
    </main>

    <script>
        (function () {
            var doc = document.documentElement;
            var key = 'steam-dashboard-theme';
            var saved = localStorage.getItem(key);

            if (saved === 'dark') {
                doc.setAttribute('data-theme', 'dark');
            }

            var toggle = document.getElementById('theme-toggle');
            if (!toggle) {
                return;
            }

            toggle.addEventListener('click', function () {
                var isDark = doc.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    doc.removeAttribute('data-theme');
                    localStorage.setItem(key, 'light');
                } else {
                    doc.setAttribute('data-theme', 'dark');
                    localStorage.setItem(key, 'dark');
                }
            });
        })();
    </script>
</body>
</html>