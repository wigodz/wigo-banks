# wigo-banks

Carteira financeira digital: os usuários podem **depositar**, **transferir** e **sacar** dinheiro, acompanhar o **saldo** e consultar o **histórico** completo de movimentações.

## Funcionalidades

- **Autenticação** com registro, login e **verificação em duas etapas (2FA)** por código enviado por e-mail.
- **Dashboard** com saldo atual, gráfico de saldo dos últimos 7 dias e resumo de entradas/saídas.
- **Depósito**, **transferência** entre contas e **saque** (este com confirmação por código).
- **Histórico** paginado, com filtros, movimentação (entrada/saída), período e recebedor.
- **Reversão** de transações: depósitos, saques e transferências podem ser revertidos (com regras de saldo e proteção contra reversão dupla).

## Stack

- **Backend:** Laravel 13 (PHP 8.3+), Laravel Passport, Laravel Horizon.
- **Frontend:** Inertia.js + Vue 3 + TypeScript + Tailwind CSS 4 (Vite).
- **Infra:** MySQL 8, Redis (cache e filas), Mailhog (e-mails em dev), Docker.

## Requisitos

- Docker e Docker Compose
- Node.js 20+ e npm (para os assets do frontend)

## Setup e execução

O ambiente roda em containers (app PHP-FPM, nginx, MySQL, Redis, Horizon e Mailhog). O frontend (Vite) roda na máquina host.

```bash
# 1. Variáveis de ambiente
cp .env.example .env
cp docker-compose.example.yml docker-compose.yml

# 2. Sobe os containers
docker compose up -d --build

# 3. Dependências e chave da aplicação (dentro do container app)
docker compose exec app composer install
docker compose exec app php artisan key:generate

# 4. Migrations + dados iniciais (ver seção abaixo)
docker compose exec app php artisan migrate --seed

# 5. Frontend (no host)
npm install
npm run dev
```

Aplicação disponível em **http://localhost:8002**.
Mailhog (caixa de entrada de e-mails, inclusive os códigos 2FA) em **http://localhost:8025**.

## Dados iniciais (seed)

`migrate --seed` cria usuários de demonstração e movimentações de exemplo (depósitos distribuídos nos últimos dias, transferências, um saque e uma transação revertida), além do client de acesso pessoal do Passport.

Os seeders só populam os dados de exemplo quando ainda não há usuários, então rodar `php artisan db:seed` novamente é seguro e não duplica registros. Para recriar tudo do zero use `php artisan migrate:fresh --seed`.

### Usuários de demonstração

Todos com a senha `password`:

| Nome         | E-mail            |
| ------------ | ----------------- |
| Test User    | test@example.com  |
| Maria Souza  | maria@example.com |
| João Pereira | joao@example.com  |
| Ana Lima     | ana@example.com   |

> O login exige 2FA. Em ambiente local o código chega no **Mailhog**
> (http://localhost:8025).

## Comandos úteis

```bash
docker compose exec app php artisan test    # testes 
docker compose exec app composer pint:fix   # formatação
docker compose logs -f horizon              # acompanhar as filas (Horizon)

npm run lint                                # ESLint (--fix)
npm run types:check                         # checagem de tipos (vue-tsc)
```
