<?php

declare(strict_types=1);

use Anderson\SteamGames\Support\TextHelper;
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
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
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
                <?php if ($isDemoMode): ?>
                    <input type="hidden" name="demo" value="1">
                <?php endif; ?>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Digite o nome da URL da Steam. Ex: gaben"
                    required
                    value="<?php echo TextHelper::escape($inputValue); ?>"
                >
                <button type="submit" class="button">Buscar</button>
            </form>
            <div class="control-stack">
                <a class="button-secondary" href="?username=demo&order_by=tempo_jogado&demo=1">Demo rápida</a>
                <button id="theme-toggle" class="button-secondary" type="button">Alternar tema</button>
            </div>
        </header>

        <?php if ($isDemoMode): ?>
            <div class="alert" style="margin-top: 16px;">
                Modo demonstração ativo: dados fixos de portfólio para apresentação (sem chamadas externas).
            </div>
        <?php endif; ?>

        <section class="hero">
            <h2>Dashboard de biblioteca Steam com foco em experiência de uso e performance.</h2>
            <p>Busca de perfil, enriquecimento de catálogo, filtros combinados e cache local para reduzir latência em consultas recorrentes.</p>
            <p class="hero-note">Portfolio pitch: production-grade API integration, resilient data flow, and UX-first information design.</p>
        </section>

        <section class="story-grid" aria-label="Resumo de valor do projeto">
            <article class="story-card">
                <h3>Problema</h3>
                <p>Explorar bibliotecas Steam costuma exigir múltiplas consultas manuais e pouca visibilidade consolidada.</p>
            </article>
            <article class="story-card">
                <h3>Solução</h3>
                <p>Pipeline único que resolve perfil, catálogo, conquistas e preço com filtros, ordenação e cache.</p>
            </article>
            <article class="story-card">
                <h3>Resultado</h3>
                <p>Leitura rápida do perfil, menor latência em buscas repetidas e URL compartilhável para análises.</p>
            </article>
        </section>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert"><?php echo TextHelper::escape($errorMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($profile)): ?>
            <section class="kpi-strip">
                <article class="kpi">
                    <small>tempo de resposta</small>
                    <strong><?php echo TextHelper::escape(number_format($queryTimeMs, 0, ',', '.')); ?> ms</strong>
                </article>
                <article class="kpi">
                    <small>fonte da consulta</small>
                    <strong>
                        <?php if ($dataSourceLabel === 'cache'): ?>
                            cache local
                        <?php elseif ($dataSourceLabel === 'demo'): ?>
                            base demo
                        <?php else: ?>
                            steam api
                        <?php endif; ?>
                    </strong>
                </article>
                <article class="kpi">
                    <small>volume total do perfil</small>
                    <strong><?php echo TextHelper::escape($totalGamesBeforeFilter); ?> jogos</strong>
                </article>
                <article class="kpi">
                    <small>média da sessão</small>
                    <strong><?php echo TextHelper::escape(number_format($sessionAvgMs, 0, ',', '.')); ?> ms</strong>
                </article>
                <article class="kpi">
                    <small>cache hit (sessão)</small>
                    <strong><?php echo TextHelper::escape(number_format($sessionCacheRate, 0, ',', '.')); ?>%</strong>
                </article>
            </section>

            <section class="profile">
                <div class="profile-card">
                    <div class="profile-main">
                        <img class="avatar" src="<?php echo TextHelper::escape($profile['avatar']); ?>" alt="Avatar do usuário">
                        <div>
                            <h3><?php echo TextHelper::escape($profile['username']); ?></h3>
                            <p>Criada em: <?php echo TextHelper::escape($profile['account_created']); ?></p>
                            <p>País: <?php echo TextHelper::escape($profile['country']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="stats">
                    <article class="stat">
                        <span class="stat-label">Jogos na biblioteca</span>
                        <strong class="stat-value"><?php echo TextHelper::escape($totalGames); ?></strong>
                    </article>
                    <article class="stat">
                        <span class="stat-label">Horas jogadas</span>
                        <strong class="stat-value"><?php echo TextHelper::escape(number_format($totalMinutes / 60, 1, ',', '.')); ?>h</strong>
                    </article>
                    <article class="stat">
                        <span class="stat-label">Valor estimado</span>
                        <strong class="stat-value">R$ <?php echo TextHelper::escape(number_format($totalValue, 2, ',', '.')); ?></strong>
                    </article>
                </div>
            </section>

            <section class="controls">
                <div class="control-stack">
                    <h4>
                        Jogos exibidos na página <?php echo TextHelper::escape($currentPage); ?> de <?php echo TextHelper::escape($totalPages); ?>
                        (<?php echo TextHelper::escape($totalGames); ?> de <?php echo TextHelper::escape($totalGamesBeforeFilter); ?> após filtros)
                    </h4>
                    <?php if ($dataSourceLabel === 'cache'): ?>
                        <span class="cache-tag">Origem: cache local</span>
                    <?php elseif ($dataSourceLabel === 'live'): ?>
                        <span class="cache-tag">Origem: Steam API</span>
                    <?php elseif ($dataSourceLabel === 'demo'): ?>
                        <span class="cache-tag">Origem: base demo</span>
                    <?php endif; ?>
                </div>

                <form method="GET" action="" class="control-form">
                    <input type="hidden" name="username" value="<?php echo TextHelper::escape($username); ?>">
                    <?php if ($isDemoMode): ?>
                        <input type="hidden" name="demo" value="1">
                    <?php endif; ?>
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

                    <?php if ($shareUrl !== ''): ?>
                        <button id="copy-link" class="button-secondary button-inline" data-share-url="<?php echo TextHelper::escape($shareUrl); ?>" type="button">Copiar URL dos filtros</button>
                    <?php endif; ?>
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
                            <img class="game-image" src="<?php echo TextHelper::escape($jogo['capa']); ?>" alt="Capa de <?php echo TextHelper::escape($jogo['nome']); ?>">
                            <div class="game-content">
                                <h5 class="game-title"><?php echo TextHelper::escape($jogo['nome']); ?></h5>
                                <p class="game-ach"><?php echo TextHelper::escape($jogo['conquistas']); ?></p>
                                <p class="game-description"><?php echo TextHelper::escape(TextHelper::cleanDescription((string) $jogo['descricao'])); ?></p>
                                <div class="game-meta">
                                    <span><strong>Tempo:</strong> <?php echo TextHelper::escape($jogo['tempo_jogado']); ?></span>
                                    <span><strong>Lançamento:</strong> <?php echo TextHelper::escape($jogo['data_lancamento']); ?></span>
                                    <span><strong>Preço:</strong> <?php echo TextHelper::escape($jogo['preco_atual']); ?></span>
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
                            href="?username=<?php echo urlencode($username); ?>&order_by=<?php echo urlencode($orderBy); ?>&played_filter=<?php echo urlencode($playedFilter); ?>&price_filter=<?php echo urlencode($priceFilter); ?>&achievement_filter=<?php echo urlencode($achievementFilter); ?>&page=<?php echo $i; ?><?php echo $isDemoMode ? '&demo=1' : ''; ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <footer class="footer">
            <span>Stack: PHP, GuzzleHTTP, Dotenv, Steam Web API, cache em arquivo</span>
            <span>
                GitHub: <a href="https://github.com/AndersonC96" target="_blank" rel="noreferrer">github.com/AndersonC96</a>
                <?php if ($lastUser !== ''): ?> | último perfil: <?php echo TextHelper::escape($lastUser); ?><?php endif; ?>
            </span>
        </footer>
    </main>

    <script src="public/assets/js/dashboard.js"></script>
</body>
</html>

