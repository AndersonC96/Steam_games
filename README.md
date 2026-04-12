# Steam Library Explorer

Aplicação PHP para análise e visualização de bibliotecas Steam, com foco em integração de APIs, estratégias de cache e arquitetura limpa.

![Steam Logo](https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png)

## 🎯 Visão Geral

Este projeto é um portfólio técnico que demonstra como construir uma integração resiliente com APIs externas (Steam Web e Steam Store). Ele resolve o perfil de um usuário, agrega seu catálogo de jogos, enriquece com dados reais da loja e oferece uma interface para filtragem e ordenação dinâmica.

## 🚀 Funcionalidades

- **Resolução de Perfil:** Busca de avatares, país e data de criação via SteamID ou Vanity URL.
- **Catálogo Enriquecido:** Agregação de preços reais, descrições, capas e conquistas.
- **Performance de Elite:** Estratégia de cache em dois níveis (sessão e arquivo) para reduzir latência e evitar bloqueios por rate limiting.
- **Filtros Combinados:** Filtragem por status de jogo (jogados/não jogados), faixa de preço e conquistas.
- **Ordenação Dinâmica:** Ordenação por tempo de jogo, preço, data de lançamento ou nome.
- **Modo Demonstração:** Possibilidade de validar toda a interface e fluxo sem necessidade de uma API Key real (`?demo=1`).
- **URLs Compartilháveis:** Geração de links que preservam o estado exato dos filtros aplicados.

## 🏗️ Arquitetura e Design

O projeto segue princípios de **Clean Code** e **SOLID**, evitando acoplamento excessivo e garantindo testabilidade:

- **DTOs (Data Transfer Objects):** Uso de modelos imutáveis (`SteamGame`, `SteamProfile`) para transporte de dados entre camadas.
- **Service Layer:** Separação clara entre integração de API (`SteamApiService`), lógica de negócio/catálogo (`GameCatalogService`) e persistência temporária (`CacheService`).
- **Injeção de Dependência:** Uso de interfaces (`SteamApiInterface`) para permitir a troca fácil entre o provedor real e o provedor de demonstração.
- **Portabilidade:** Helper de URL para garantir que o projeto funcione em subdiretórios ou via PHP built-in server sem ajustes manuais.

## 🛠️ Stack Técnica

- **Linguagem:** PHP 8.2+ (Tipagem estrita)
- **HTTP Client:** GuzzleHTTP
- **Ambiente:** PHP Dotenv
- **Frontend:** Vanilla CSS (Moderno, com suporte a Dark Mode) e JavaScript.
- **Gerenciador de Dependências:** Composer

## ⚙️ Configuração Local

1. **Clonar o repositório:**
   ```bash
   git clone https://github.com/AndersonC96/Steam_games.git
   cd Steam_games
   ```

2. **Instalar dependências:**
   ```bash
   composer install
   ```

3. **Configurar variáveis de ambiente:**
   Crie um arquivo `.env` na raiz:
   ```dotenv
   STEAM_API_KEY=sua_chave_aqui
   STEAM_USERNAME=usuario_padrao_opcional
   ```

4. **Executar o servidor:**
   ```bash
   php -S localhost:8000
   ```
   Acesse: `http://localhost:8000`

## 📂 Estrutura de Pastas

```text
.
├── bootstrap/          # Inicialização e carregamento de config
├── config/             # Configurações centralizadas
├── public/             # Entrypoint público e assets (CSS/JS)
├── src/
│   ├── Controllers/    # Orquestração da requisição
│   ├── Models/         # DTOs (SteamGame, SteamProfile, etc)
│   ├── Services/       # Regras de negócio e integrações
│   └── Support/        # Helpers (Text, Url)
├── views/              # Templates PHP limpos
├── tests/              # Suíte de testes (Unitários e Integração)
└── cache/              # Armazenamento local de payloads JSON
```

## 🧪 Testes

O projeto conta com uma suíte de testes customizada para validar a lógica de parsing e filtragem:

```bash
php tests/run.php
```

## ⚠️ Limitações e Observações

- **Rate Limiting:** A Steam Store API é restritiva. O projeto utiliza um limite de processamento de 100 jogos (configurável em `config/app.php`) e cache agressivo para mitigar isso.
- **Privacidade:** A conta Steam pesquisada deve ter o perfil e a biblioteca configurados como **públicos**.

## 📝 Licença

Este projeto é destinado a fins de estudo e portfólio. Os dados de imagem e marcas pertencem à Valve Corporation.
