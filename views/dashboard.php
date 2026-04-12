<?php

declare(strict_types=1);

use Anderson\SteamGames\Support\TextHelper;
use Anderson\SteamGames\Support\UrlHelper;

/** @var \Anderson\SteamGames\Models\SteamProfile|null $profile */
/** @var \Anderson\SteamGames\Models\SteamGame[] $games */
/** @var string $inputValue */
/** @var string $errorMessage */
/** @var bool $isDemoMode */
/** @var float $queryTimeMs */
/** @var string $dataSourceLabel */
/** @var int $totalGamesBeforeFilter */
/** @var int $totalGames */
/** @var float $sessionAvgMs */
/** @var float $sessionCacheRate */
/** @var int $totalMinutes */
/** @var float $totalValue */
/** @var int $currentPage */
/** @var int $totalPages */
/** @var string $username */
/** @var string $orderBy */
/** @var string $playedFilter */
/** @var string $priceFilter */
/** @var string $achievementFilter */
/** @var string $shareUrl */
/** @var string $lastUser */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steam Library Explorer - Portfólio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo UrlHelper::base('public/assets/css/dashboard.css', $_SERVER); ?>">
</head>
<body>
    <main class="page">
        <header class="topbar">
            <div class="brand">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png" alt="Logo Steam">
                <div>
                    <h1 class="brand-title">steam_library_explorer.php</h1>
                    <p class="brand-subtitle">Análise de bibliotecas com Steam Web API e Cache</p>
                </div>
            </div>

            <form method="GET" action="" class="search">
                <?php if ($isDemoMode): ?>
                    <input type="hidden" name="demo" value="1">
                <?php endif; ?>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Nome da URL da Steam (ex: gaben)"
                    required
                    value="<?php echo TextHelper::escape($inputValue); ?>"
                >
                <button type="submit" class="button">Explorar</button>
            </form>
            <div class="control-stack">
                <a class="button-secondary" href="?username=demo&demo=1">Modo Demo</a>
                <button id="theme-toggle" class="button-secondary" type="button">Tema</button>
            </div>
        </header>

        <?php if ($isDemoMode): ?>
            <div class="alert alert-info">
                <strong>Modo Demonstração:</strong> Exibindo dados estáticos para fins de apresentação.
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error"><?php echo TextHelper::escape($errorMessage); ?></div>
        <?php endif; ?>

        <?php if ($profile === null && $errorMessage === ''): ?>
            <section class="hero">
                <h2>Explore qualquer biblioteca Steam com agilidade.</h2>
                <p>Este projeto demonstra a integração resiliente com APIs externas, tratamento de dados complexos, paginação eficiente e estratégia de cache multicamadas.</p>
                
                <div class="story-grid">
                    <article class="story-card">
                        <h3>Performance</h3>
                        <p>Cache local reduz latência em até 95% para consultas repetidas.</p>
                    </article>
                    <article class="story-card">
                        <h3>Enriquecimento</h3>
                        <p>Agregação de dados entre Steam Web API e Steam Store API em um único fluxo.</p>
                    </article>
                    <article class="story-card">
                        <h3>UX</h3>
                        <p>Interface responsiva focada em legibilidade e filtros instantâneos.</p>
                    </article>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($profile !== null): ?>
            <section class="kpi-strip">
                <article class="kpi">
                    <small>latência</small>
                    <strong><?php echo number_format($queryTimeMs, 0, ',', '.'); ?> ms</strong>
                </article>
                <article class="kpi">
                    <small>origem</small>
                    <strong class="source-tag source-<?php echo $dataSourceLabel; ?>">
                        <?php echo match($dataSourceLabel) { 'cache' => 'Cache Local', 'demo' => 'Base Demo', default => 'Steam API' }; ?>
                    </strong>
                </article>
                <article class="kpi">
                    <small>total perfil</small>
                    <strong><?php echo $totalGamesBeforeFilter; ?></strong>
                </article>
                <article class="kpi">
                    <small>sessão (média)</small>
                    <strong><?php echo number_format($sessionAvgMs, 0, ',', '.'); ?> ms</strong>
                </article>
                <article class="kpi">
                    <small>cache hit rate</small>
                    <strong><?php echo number_format($sessionCacheRate, 1, ',', '.'); ?>%</strong>
                </article>
            </section>

            <section class="profile">
                <div class="profile-card">
                    <div class="profile-main">
                        <img class="avatar" src="<?php echo TextHelper::escape($profile->avatar); ?>" alt="Avatar">
                        <div>
                            <h3><?php echo TextHelper::escape($profile->username); ?></h3>
                            <p>Conta criada: <?php echo TextHelper::escape($profile->accountCreated); ?></p>
                            <p>País: <?php echo TextHelper::escape($profile->country); ?></p>
                        </div>
                    </div>
                </div>

                <div class="stats">
                    <article class="stat">
                        <span class="stat-label">Exibindo</span>
                        <strong class="stat-value"><?php echo $totalGames; ?> jogos</strong>
                    </article>
                    <article class="stat">
                        <span class="stat-label">Tempo total</span>
                        <strong class="stat-value"><?php echo number_format($totalMinutes / 60, 1, ',', '.'); ?>h</strong>
                    </article>
                    <article class="stat">
                        <span class="stat-label">Valor total</span>
                        <strong class="stat-value">R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></strong>
                    </article>
                </div>
            </section>

            <section class="controls">
                <form method="GET" action="" class="control-form">
                    <input type="hidden" name="username" value="<?php echo TextHelper::escape($username); ?>">
                    <?php if ($isDemoMode): ?>
                        <input type="hidden" name="demo" value="1">
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <label for="order_by">Ordenar por</label>
                        <select id="order_by" name="order_by" onchange="this.form.submit()">
                            <option value="tempo_jogado"<?php echo $orderBy === 'tempo_jogado' ? ' selected' : ''; ?>>Tempo jogado</option>
                            <option value="preco_atual"<?php echo $orderBy === 'preco_atual' ? ' selected' : ''; ?>>Preço</option>
                            <option value="data_lancamento"<?php echo $orderBy === 'data_lancamento' ? ' selected' : ''; ?>>Lançamento</option>
                            <option value="nome"<?php echo $orderBy === 'nome' ? ' selected' : ''; ?>>Nome</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="played_filter">Status</label>
                        <select id="played_filter" name="played_filter" onchange="this.form.submit()">
                            <option value="todos"<?php echo $playedFilter === 'todos' ? ' selected' : ''; ?>>Todos</option>
                            <option value="jogados"<?php echo $playedFilter === 'jogados' ? ' selected' : ''; ?>>Jogados</option>
                            <option value="nao_jogados"<?php echo $playedFilter === 'nao_jogados' ? ' selected' : ''; ?>>Não jogados</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="price_filter">Preço</label>
                        <select id="price_filter" name="price_filter" onchange="this.form.submit()">
                            <option value="todos"<?php echo $priceFilter === 'todos' ? ' selected' : ''; ?>>Todos</option>
                            <option value="gratis"<?php echo $priceFilter === 'gratis' ? ' selected' : ''; ?>>Grátis</option>
                            <option value="pagos"<?php echo $priceFilter === 'pagos' ? ' selected' : ''; ?>>Pagos</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="achievement_filter">Conquistas</label>
                        <select id="achievement_filter" name="achievement_filter" onchange="this.form.submit()">
                            <option value="todos"<?php echo $achievementFilter === 'todos' ? ' selected' : ''; ?>>Todas</option>
                            <option value="com"<?php echo $achievementFilter === 'com' ? ' selected' : ''; ?>>Com conquistas</option>
                            <option value="sem"<?php echo $achievementFilter === 'sem' ? ' selected' : ''; ?>>Sem conquistas</option>
                        </select>
                    </div>

                    <?php if ($shareUrl !== ''): ?>
                        <button id="copy-link" class="button-secondary" data-share-url="<?php echo TextHelper::escape($shareUrl); ?>" type="button">Copiar Link</button>
                    <?php endif; ?>
                </form>
            </section>

            <section class="games-grid">
                <?php if (empty($games)): ?>
                    <div class="alert alert-info" style="grid-column: 1 / -1;">
                        Nenhum jogo encontrado para os filtros selecionados.
                    </div>
                <?php else: ?>
                    <?php foreach ($games as $jogo): ?>
                        <article class="game">
                            <img class="game-image" src="<?php echo TextHelper::escape($jogo->headerImage); ?>" alt="Capa" loading="lazy">
                            <div class="game-content">
                                <h5 class="game-title"><?php echo TextHelper::escape($jogo->name); ?></h5>
                                <p class="game-ach"><?php echo TextHelper::escape($jogo->achievements); ?></p>
                                <p class="game-description"><?php echo TextHelper::escape(TextHelper::cleanDescription($jogo->description)); ?></p>
                                <div class="game-meta">
                                    <span><strong>Tempo:</strong> <?php echo TextHelper::escape($jogo->playtimeFormatted); ?></span>
                                    <span><strong>Preço:</strong> <?php echo TextHelper::escape($jogo->priceFormatted); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a
                            class="page-link<?php echo $i === $currentPage ? ' active' : ''; ?>"
                            href="?username=<?php echo urlencode($username); ?>&order_by=<?php echo urlencode($orderBy); ?>&played_filter=<?php echo urlencode($playedFilter); ?>&price_filter=<?php echo urlencode($priceFilter); ?>&achievement_filter=<?php echo urlencode($achievementFilter); ?>&page=<?php echo $i; ?><?php echo $isDemoMode ? '&demo=1' : ''; ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <footer class="footer">
            <div class="stack-info">
                <span>Stack: PHP 8.2 • Guzzle • Cache • DTOs • OOP</span>
                <?php if ($lastUser !== ''): ?>
                    <span> • Última busca: <strong><?php echo TextHelper::escape($lastUser); ?></strong></span>
                <?php endif; ?>
            </div>
            <div class="author-info">
                Desenvolvido por <a href="https://github.com/AndersonC96" target="_blank">AndersonC96</a>
            </div>
        </footer>
    </main>

    <script src="<?php echo UrlHelper::base('public/assets/js/dashboard.js', $_SERVER); ?>"></script>
</body>
</html>
