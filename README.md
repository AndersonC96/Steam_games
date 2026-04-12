# Steam Library Explorer

Uma aplicação PHP enxuta para análise e visualização de bibliotecas da Steam. O objetivo deste projeto é demonstrar a construção de uma integração resiliente com APIs externas, manipulação de dados assíncrona e estratégias de cache, sem a dependência de frameworks pesados.

![Steam Logo](https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png)

## Visão Geral

O projeto resolve o perfil de um usuário (via SteamID ou Vanity URL), agrega seu catálogo de jogos consumindo a Steam Web API e a Steam Store API, e apresenta uma interface para filtragem, ordenação e compartilhamento de estado.

Devido aos limites restritos de requisições da API pública da loja da Steam, o sistema implementa processamento concorrente (Promises) e cache em arquivo para garantir tempos de resposta viáveis na carga inicial e latência quase nula em consultas subsequentes.

## Funcionalidades Implementadas

- **Integração de APIs:** Consulta simultânea à Steam Web API (perfil e biblioteca base) e Steam Store API (preços, descrições e conquistas).
- **Processamento Concorrente:** Uso de Guzzle Promises para buscar detalhes de até 50 jogos simultaneamente, reduzindo o tempo de "cold start".
- **Estratégia de Cache:** Cache local (file-based) por perfil e por jogo para mitigar *rate limiting* (HTTP 429) e acelerar requisições.
- **Catálogo Filtrável:** Filtros in-memory por status de jogo (jogado/não jogado), faixa de preço e suporte a conquistas, com ordenação dinâmica.
- **Compartilhamento de Estado:** Os filtros e a paginação refletem na URL, permitindo o compartilhamento do estado exato da visão.
- **Modo Demo:** Um bypass de infraestrutura (`?demo=1`) que injeta um serviço com dados estáticos, útil para demonstrações onde a API Key não está disponível.

## Arquitetura e Decisões Técnicas

O projeto é estruturado em torno de princípios de separação de responsabilidades (Layered Architecture):

- **Infrastructure:** O `SteamApiClient` encapsula a biblioteca Guzzle e lida exclusivamente com comunicação HTTP, parsing básico e lançamento de Exceções de Domínio (ex: `UserNotFoundException`).
- **Services:** O `SteamApiService` orquestra a lógica: verifica cache, chama a infraestrutura, processa promises e monta DTOs (`SteamGame`, `SteamProfile`). O `GameCatalogService` isola as regras de negócio para ordenação e filtro.
- **Controllers & Views:** O `DashboardController` atua como um coordenador simples, recebendo o request, delegando aos serviços e populando o View Model para a renderização em `views/dashboard.php`.
- **Front Controller:** `public/index.php` serve como ponto de entrada único, centralizando o *bootstrap* e a injeção manual de dependências.

## Stack Utilizada

- **Linguagem:** PHP 8.2+
- **Dependências (Composer):** 
  - `guzzlehttp/guzzle`: Para chamadas HTTP e assincronismo.
  - `vlucas/phpdotenv`: Para gestão de variáveis de ambiente.
- **Frontend:** HTML5, CSS3 (Vanilla com custom properties para Theming) e JS mínimo para interatividade.
- **Testes:** Suíte de testes unitários e de integração nativa (sem PHPUnit, executada via runner próprio).

## Como Executar Localmente

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/AndersonC96/Steam_games.git
   cd Steam_games
   ```

2. **Instale as dependências:**
   ```bash
   composer install
   ```

3. **Configure as variáveis de ambiente:**
   Copie o arquivo de template e preencha com suas chaves:
   ```bash
   cp .env.example .env
   ```
   Edite o arquivo `.env` e insira sua `STEAM_API_KEY`. Você pode obter uma chave em [steamcommunity.com/dev/apikey](https://steamcommunity.com/dev/apikey).

4. **Inicie o servidor embutido do PHP:**
   ```bash
   php -S localhost:8000
   ```
   Acesse a aplicação em `http://localhost:8000`.


## Executando os Testes

Para rodar a suíte de testes do projeto:

```bash
php tests/run.php
```

## Estrutura de Diretórios

```text
.
├── bootstrap/          # Inicialização do app e carregamento de env
├── config/             # Arquivo central de configurações (limites, paths)
├── public/             # Entrypoint da aplicação e assets estáticos
├── src/
│   ├── Controllers/    # Orquestração de rotas/fluxo
│   ├── Exceptions/     # Exceções de domínio customizadas
│   ├── Infrastructure/ # Clientes de API externa (Guzzle)
│   ├── Models/         # DTOs imutáveis (SteamProfile, SteamGame)
│   ├── Services/       # Regras de negócio e cache
│   └── Support/        # Helpers estáticos (UrlHelper, TextHelper)
├── views/              # Camada de apresentação (templates PHP)
└── tests/              # Testes unitários e de integração
```

## Limitações Conhecidas

- Devido às fortes restrições de *rate limit* da Steam Store API, a aplicação processa detalhes aprofundados para um limite máximo de 50 jogos por perfil (priorizando os mais jogados).
- Perfis configurados como "Privados" na Steam não expõem a lista de jogos, resultando em uma exceção tratada na interface.

## Licença

Projeto acadêmico e de portfólio. Dados e imagens providos são de propriedade da Valve Corporation.
