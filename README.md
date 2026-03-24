# Steam Spotlight

Projeto de portfolio em PHP que consome múltiplos endpoints da Steam Web API para criar uma experiência visual de exploração de perfis e bibliotecas de jogos.

![Steam Logo](https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png)

## Visão Geral

Steam Spotlight foi desenhado para demonstrar integração de APIs externas, tratamento de dados e construção de interface moderna com foco em apresentação profissional.

Você informa um usuário da Steam e o sistema retorna:

- perfil público com avatar, país e data de criação da conta;
- biblioteca de jogos com capa, descrição, preço e conquistas;
- estatísticas de portfolio como horas totais e valor estimado da biblioteca;
- ordenação dinâmica e paginação.

## Destaques Técnicos

- integração com Steam Web API e Steam Store API;
- arquitetura simples e legível para estudo e evolução;
- tratamento de exceções de requisições HTTP;
- normalização de dados para ordenação por preço, data e tempo jogado;
- UI responsiva com identidade visual própria.

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
├── img/
└── vendor/
```

## Melhorias Futuras

- cache das respostas por usuário para reduzir tempo de carregamento;
- filtros adicionais por faixa de preço e quantidade de conquistas;
- versão com autenticação OAuth da Steam para recursos privados;
- testes automatizados para funções de parsing e ordenação.