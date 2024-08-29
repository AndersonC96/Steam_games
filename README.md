# 🚀 Steam Profile Search

![Steam Logo](https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Steam_icon_logo.svg/1024px-Steam_icon_logo.svg.png)

Steam Profile Search é um projeto em PHP que permite buscar informações de perfis da Steam, como nome de usuário, data de criação da conta, jogos, tempo jogado, conquistas, preços atuais dos jogos, e muito mais. O sistema suporta paginação, ordenação por diferentes critérios e oferece uma interface elegante com tema escuro e transparência estilo "vidro".

## 🛠️ Funcionalidades

- **Busca de Perfis**: Busque informações detalhadas de qualquer usuário da Steam inserindo o nome de usuário.
- **Exibição de Jogos**: Exibe até 12 jogos por página com informações detalhadas.
- **Ordenação Personalizada**: Ordene os jogos por nome, data de lançamento, tempo jogado ou preço atual.
- **Tema Escuro**: Interface com tema escuro e elementos transparentes para uma experiência visual moderna.
- **Perfil do Usuário**: Exibe informações do perfil do usuário em um card centralizado.
- **Paginação**: Navegue por páginas de jogos com facilidade.

## 🧑‍💻 Tecnologias Utilizadas

- **PHP**
- **Tailwind CSS** (via CDN)
- **GuzzleHTTP** (para requisições API)
- **Steam Web API**

## 🚀 Como Instalar e Executar

### 1. Clone o Repositório

```bash
git clone https://github.com/AndersonC96/Steam_games.git
cd Steam_games
```

### 2. Instale as Dependências
Certifique-se de ter o Composer instalado.

```bash
composer install
```

### 3. Configuração do Ambiente
Crie um arquivo .env na raiz do projeto e adicione suas credenciais da Steam API:

```bash
STEAM_API_KEY=your_steam_api_key
STEAM_USERNAME=your_default_username
```

### 4. Execute o Projeto
Você pode usar o servidor embutido do PHP para rodar o projeto:

```bash
php -S localhost:8000
```

Agora, acesse http://localhost:8000 no seu navegador.

## ⚙️ Estrutura do Projeto

```bash
├── vendor/              # Dependências do Composer
├── steam_api.php        # Arquivo PHP principal para interações com a Steam API
├── index.php            # Arquivo principal que carrega a interface e lida com as buscas
├── .env                 # Arquivo de configuração do ambiente
└── README.md            # Documentação do projeto
```