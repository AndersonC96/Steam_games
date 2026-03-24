# Steam Library Explorer

Projeto pessoal em PHP que criei para praticar integração com a Steam Web API e transformar os dados em uma interface agradável de explorar.

![Steam Logo](https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png)

## Visão Geral

A ideia aqui foi simples: pegar um username da Steam e mostrar tudo de forma clara, sem cara de dashboard engessado.

Você informa um usuário da Steam e o sistema retorna:

- perfil público com avatar, país e data de criação da conta;
- biblioteca de jogos com capa, descrição, preço e conquistas;
- estatísticas gerais como horas totais e valor estimado da biblioteca;
- ordenação dinâmica e paginação.

## O que eu trabalhei aqui

- integração com Steam Web API e Steam Store API;
- estrutura simples de manter e evoluir;
- tratamento de exceções de requisições HTTP;
- cache local em arquivo para acelerar buscas repetidas;
- ordenação por preço, data e tempo jogado com parsing dos dados;
- UI responsiva com estilo autoral.

## Melhorias recentes

- botão de demo para abrir um perfil de exemplo rapidamente;
- filtros por status de jogo (jogados/não jogados), preço e conquistas;
- indicador de origem dos dados (cache local ou Steam API em tempo real);
- estado vazio amigável quando filtros não retornam jogos.

## Stack

- PHP 8+
- Composer
- GuzzleHTTP
- vlucas/phpdotenv
- HTML/CSS (responsivo)

## Endpoints Utilizados

- ISteamUser/ResolveVanityURL
- ISteamUser/GetPlayerSummaries
- IPlayerService/GetOwnedGames
- ISteamUserStats/GetPlayerAchievements
- ISteamUserStats/GetSchemaForGame
- store.steampowered.com/api/appdetails

## Como Rodar Localmente

1. Clone o repositório

```bash
git clone https://github.com/AndersonC96/Steam_games.git
cd Steam_games
```

2. Instale as dependências

```bash
composer install
```

3. Configure o arquivo .env

```dotenv
STEAM_API_KEY=sua_steam_api_key
STEAM_USERNAME=seu_usuario_padrao
```

4. Suba o servidor local

```bash
php -S localhost:8000
```

5. Acesse no navegador

http://localhost:8000

## Estrutura do Projeto

```bash
.
├── index.php
├── steam_api.php
├── composer.json
├── .env
├── cache/
├── img/
└── vendor/
```

## Próximos passos

- cache por jogo para reduzir chamadas ao endpoint de detalhes;
- filtros adicionais por faixa de preço e quantidade de conquistas;
- versão com autenticação OAuth da Steam para recursos privados;
- testes automatizados para funções de parsing e ordenação.