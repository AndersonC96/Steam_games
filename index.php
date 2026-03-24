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
    <title>Steam Library Explorer | Projeto Pessoal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1722;
            --bg-soft: #182536;
            --panel: rgba(21, 34, 50, 0.88);
            --line: rgba(132, 168, 204, 0.24);
            --text: #e8edf4;
            --muted: #a7b6c7;
            --accent: #ffb347;
            --accent-soft: #ffd89d;
            --danger: #ff7373;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
            background:
                linear-gradient(180deg, #131e2e 0%, #0f1722 100%),
                repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.015) 0, rgba(255, 255, 255, 0.015) 1px, transparent 1px, transparent 14px);
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
            background: rgba(8, 17, 31, 0.78);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.18);
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
            font-size: 1rem;
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
            background: rgba(12, 26, 48, 0.85);
            color: var(--text);
            border-radius: 10px;
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
            border-radius: 10px;
            padding: 12px 20px;
            font: 600 0.95rem 'IBM Plex Sans', sans-serif;
            letter-spacing: 0.02em;
            color: #2b1a00;
            background: linear-gradient(120deg, var(--accent), var(--accent-soft));
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 179, 71, 0.26);
        }

        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 10px;
            padding: 12px 16px;
            font: 600 0.9rem 'IBM Plex Sans', sans-serif;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.07);
            transition: all 0.2s ease;
        }

        .button-secondary:hover {
            border-color: var(--accent);
            color: #ffe5bf;
            transform: translateY(-2px);
        }

        .hero {
            margin: 20px 0 16px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background:
                linear-gradient(120deg, rgba(26, 40, 58, 0.95) 0%, rgba(16, 27, 41, 0.95) 60%),
                url('https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1400&q=80') center/cover;
            padding: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 16px 34px rgba(0, 0, 0, 0.26);
        }

        .hero h2 {
            margin: 0;
            font: 700 1.75rem/1.2 'IBM Plex Sans', sans-serif;
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
            margin-top: 14px;
        }

        .build-badge {
            display: inline-block;
            margin-top: 10px;
            font-size: 0.76rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #2a1a00;
            background: linear-gradient(120deg, #ffd08a, #ffe7c4);
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 700;
        }

        .chip {
            font-size: 0.82rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #e8eef8;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
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
            border-radius: 12px;
            background: var(--panel);
            padding: 20px;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(260px, 1.05fr) 1.3fr;
        }

        .profile-card {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
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
            border-radius: 12px;
            border: 2px solid rgba(255, 179, 71, 0.45);
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
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #f7ddae;
            background: rgba(255, 255, 255, 0.08);
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
            background: rgba(8, 19, 34, 0.82);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.22);
            transition: transform 0.25s ease, border-color 0.25s ease;
        }

        .game:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 179, 71, 0.65);
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
            color: #ffd28e;
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
            background: rgba(12, 26, 48, 0.75);
            transition: all 0.2s ease;
        }

        .page-link:hover {
            border-color: var(--accent);
            color: #ffe5bf;
        }

        .page-link.active {
            border-color: transparent;
            background: linear-gradient(120deg, var(--accent), var(--accent-soft));
            color: #2a1a00;
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
                    <h1 class="brand-title">steam_library_explorer.php</h1>
                    <p class="brand-subtitle">um projeto pessoal em evolução</p>
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
            <a class="button-secondary" href="?username=gaben&order_by=tempo_jogado">carregar demo</a>
        </header>

        <section class="hero">
            <h2>Montei esse app para estudar integrações com a Steam API sem abrir mão de um visual agradável.</h2>
            <p>Ele busca perfil, jogos, conquistas e preços. Os filtros e o cache foram surgindo conforme eu fui usando no dia a dia.</p>
            <span class="build-badge">build local: 2026.03.23</span>
            <div class="chips">
                <span class="chip">php + composer</span>
                <span class="chip">steam web api</span>
                <span class="chip">cache local em arquivo</span>
                <span class="chip">filtros de biblioteca</span>
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
    </main>
</body>
</html>