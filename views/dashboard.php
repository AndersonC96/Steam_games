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
    <title>Steam Library Explorer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo UrlHelper::base('public/assets/css/dashboard.css', $_SERVER); ?>">
</head>
<body>
    <main class="page">
        <header class="topbar">
            <div class="brand">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png" alt="Steam Logo">
                <div>
                    <h1 class="brand-title">Steam Explorer</h1>
                    <p class="brand-subtitle">Library API Integration</p>
                </div>
            </div>

            <form method="GET" action="" class="search">
                <?php if ($isDemoMode): ?>
                    <input type="hidden" name="demo" value="1">
                <?php endif; ?>
                <input
                    type="text"
                    class="input"
                    id="username"
                    name="username"
                    placeholder="SteamID ou Vanity URL (ex: gaben)"
                    required
                    value="<?php echo TextHelper::escape($inputValue); ?>"
                >
                <button type="submit" class="btn btn-primary">Buscar Perfil</button>
            </form>
            <div class="control-stack">
                <a class="btn btn-secondary" href="?username=demo&demo=1">Modo Demo</a>
                <button id="theme-toggle" class="btn btn-secondary" type="button">Tema</button>
            </div>
        </header>

        <?php if ($isDemoMode): ?>
            <div class="alert alert-info">
                <strong>Modo Demo Ativo:</strong> Exibindo dados estáticos pré-carregados para fins de apresentação. Nenhuma chamada externa está sendo feita.
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <strong>Erro:</strong> <?php echo TextHelper::escape($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($profile === null && $errorMessage === ''): ?>
            <section class="hero">
                <h2>Explore qualquer biblioteca Steam.</h2>
                <p>Este projeto consome a Steam Web API para extrair o catálogo de usuários, aplicando enriquecimento assíncrono com dados da Store API e estratégias de cache para alta performance.</p>
                
                <div class="story-grid">
                    <article class="story-card">
                        <h3>Performance & Concorrência</h3>
                        <p>Uso de Guzzle Promises para processamento paralelo, garantindo carregamento rápido mesmo com limites rígidos da API da loja.</p>
                    </article>
                    <article class="story-card">
                        <h3>Cache Multi-camada</h3>
                        <p>Redução de latência de ~3000ms para ~5ms através de cache de arquivo para o catálogo e detalhes individuais dos jogos.</p>
                    </article>
                    <article class="story-card">
                        <h3>Filtros & Compartilhamento</h3>
                        <p>Filtros in-memory eficientes com atualização de estado via query string, permitindo compartilhamento exato da visão.</p>
                    </article>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($profile !== null): ?>
            <section class="kpi-strip">
                <article class="kpi">
                    <small>Latência da Consulta</small>
                    <strong class="mono"><?php echo number_format($queryTimeMs, 0, ',', '.'); ?> ms</strong>
                </article>
                <article class="kpi">
                    <small>Origem dos Dados</small>
                    <span class="tag tag-<?php echo $dataSourceLabel; ?>">
                        <?php echo match($dataSourceLabel) { 'cache' => 'Cache Local', 'demo' => 'Base Demo', default => 'Steam API' }; ?>
                    </span>
                </article>
                <article class="kpi">
                    <small>Tamanho do Catálogo</small>
                    <strong class="mono"><?php echo $totalGamesBeforeFilter; ?></strong>
                </article>
                <article class="kpi">
                    <small>Média da Sessão</small>
                    <strong class="mono"><?php echo number_format($sessionAvgMs, 0, ',', '.'); ?> ms</strong>
                </article>
                <article class="kpi">
                    <small>Cache Hit Rate</small>
                    <strong class="mono"><?php echo number_format($sessionCacheRate, 1, ',', '.'); ?>%</strong>
                </article>
            </section>

            <section class="profile-section">
                <div class="profile-card">
                    <img class="avatar" src="<?php echo TextHelper::escape($profile->avatar); ?>" alt="Avatar">
                    <div class="profile-info">
                        <h3><?php echo TextHelper::escape($profile->username); ?></h3>
                        <p>Conta: <?php echo TextHelper::escape($profile->accountCreated); ?> • País: <?php echo TextHelper::escape($profile->country); ?></p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <span>Exibindo</span>
                        <strong class="mono"><?php echo $totalGames; ?> jogos</strong>
                    </div>
                    <div class="stat-box">
                        <span>Tempo Total</span>
                        <strong class="mono"><?php echo number_format($totalMinutes / 60, 1, ',', '.'); ?>h</strong>
                    </div>
                    <div class="stat-box">
                        <span>Valor Total</span>
                        <strong class="mono">R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></strong>
                    </div>
                </div>
            </section>

            <section class="controls-bar">
                <form method="GET" action="" style="display: contents;">
                    <input type="hidden" name="username" value="<?php echo TextHelper::escape($username); ?>">
                    <?php if ($isDemoMode): ?>
                        <input type="hidden" name="demo" value="1">
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <label for="order_by">Ordenação</label>
                        <select class="select" id="order_by" name="order_by" onchange="this.form.submit()">
                            <option value="tempo_jogado"<?php echo $orderBy === 'tempo_jogado' ? ' selected' : ''; ?>>Maior tempo jogado</option>
                            <option value="preco_atual"<?php echo $orderBy === 'preco_atual' ? ' selected' : ''; ?>>Maior preço</option>
                            <option value="data_lancamento"<?php echo $orderBy === 'data_lancamento' ? ' selected' : ''; ?>>Lançamento mais recente</option>
                            <option value="nome"<?php echo $orderBy === 'nome' ? ' selected' : ''; ?>>Ordem Alfabética</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="played_filter">Status</label>
                        <select class="select" id="played_filter" name="played_filter" onchange="this.form.submit()">
                            <option value="todos"<?php echo $playedFilter === 'todos' ? ' selected' : ''; ?>>Todos os jogos</option>
                            <option value="jogados"<?php echo $playedFilter === 'jogados' ? ' selected' : ''; ?>>Apenas Jogados</option>
                            <option value="nao_jogados"<?php echo $playedFilter === 'nao_jogados' ? ' selected' : ''; ?>>Apenas Não Jogados</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="price_filter">Preço</label>
                        <select class="select" id="price_filter" name="price_filter" onchange="this.form.submit()">
                            <option value="todos"<?php echo $priceFilter === 'todos' ? ' selected' : ''; ?>>Todos</option>
                            <option value="gratis"<?php echo $priceFilter === 'gratis' ? ' selected' : ''; ?>>Grátis</option>
                            <option value="pagos"<?php echo $priceFilter === 'pagos' ? ' selected' : ''; ?>>Pagos</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="achievement_filter">Conquistas</label>
                        <select class="select" id="achievement_filter" name="achievement_filter" onchange="this.form.submit()">
                            <option value="todos"<?php echo $achievementFilter === 'todos' ? ' selected' : ''; ?>>Todas</option>
                            <option value="com"<?php echo $achievementFilter === 'com' ? ' selected' : ''; ?>>Com suporte a conquistas</option>
                            <option value="sem"<?php echo $achievementFilter === 'sem' ? ' selected' : ''; ?>>Sem conquistas</option>
                        </select>
                    </div>

                    <?php if ($shareUrl !== ''): ?>
                        <div class="filter-group" style="flex: 0; min-width: auto;">
                            <button id="copy-link" class="btn btn-secondary" data-share-url="<?php echo TextHelper::escape($shareUrl); ?>" type="button" title="Copiar URL com os filtros atuais">
                                Copiar Link
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </section>

            <section class="games-grid">
                <?php if (empty($games)): ?>
                    <div class="alert alert-info" style="grid-column: 1 / -1; text-align: center;">
                        Nenhum jogo encontrado para a combinação de filtros selecionada.
                    </div>
                <?php else: ?>
                    <?php foreach ($games as $jogo): ?>
                        <article class="game-card">
                            <img class="game-img" src="<?php echo TextHelper::escape($jogo->headerImage); ?>" alt="Capa de <?php echo TextHelper::escape($jogo->name); ?>" loading="lazy">
                            <div class="game-body">
                                <h4 class="game-title"><?php echo TextHelper::escape($jogo->name); ?></h4>
                                <p class="game-ach"><?php echo TextHelper::escape($jogo->achievements); ?></p>
                                <p class="game-desc"><?php echo TextHelper::escape(TextHelper::cleanDescription($jogo->description, 140)); ?></p>
                                <div class="game-meta mono">
                                    <span><strong>⏱</strong> <?php echo TextHelper::escape($jogo->playtimeFormatted); ?></span>
                                    <span><strong>$</strong> <?php echo TextHelper::escape($jogo->priceFormatted); ?></span>
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
                            class="page-link mono <?php echo $i === $currentPage ? ' active' : ''; ?>"
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
                PHP 8.2 • Guzzle HTTP • Promises • DTOs
            </div>
            <div class="author-info">
                Código Aberto no <a href="https://github.com/AndersonC96" target="_blank" rel="noopener">GitHub</a>
            </div>
        </footer>
    </main>

    <script src="<?php echo UrlHelper::base('public/assets/js/dashboard.js', $_SERVER); ?>"></script>
</body>
</html>
