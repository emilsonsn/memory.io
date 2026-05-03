# Memory.io API

Backend API de um gerenciador de notas de texto com filtros avancados, organizado como uma biblioteca pessoal de anotacoes. No dominio do sistema, cada nota e chamada de memoria.

## Visão Geral

O projeto foi construído para oferecer:

- Biblioteca pessoal de notas de texto (memorias)
- CRUD completo de memorias com categorias e due date
- Filtros avancados para busca e organizacao de memorias
- Controle de recursos por plano
- Notificações de domínio baseadas em eventos/listeners
- Auditoria de operações críticas
- Cache versionado para listagens e logs

## Stack Técnica

- PHP 8.3
- Laravel 13
- PostgreSQL
- Redis (cache/queue)
- JWT Auth (php-open-source-saver/jwt-auth)
- Spatie Permission (roles/permissions)
- Spatie Activitylog (auditoria)
- Laravel Sail (ambiente local)

## Domínio Principal

- Users
	- autenticação via JWT
	- associação com plano
	- roles (`admin`, `user`)
- Plans
	- limites de memórias/categorias
	- flags de funcionalidades (export e IA)
- Categories
	- categorias por usuário
- Memories
	- notas de texto do usuario (chamadas de memorias)
	- filtros avancados por texto, categorias e datas
	- logs de auditoria e exportação em texto
- Notifications
	- listagem e marcação como lida
	- criação por eventos de domínio

## Arquitetura

O projeto segue organização por camadas:

- `app/Http/Controllers`: entrada de requisições e respostas
- `app/Http/Requests`: validação de payload
- `app/Services`: regras de negócio
- `app/Policies`: autorização por ação/recurso
- `app/Events` e `app/Listeners`: automações de domínio
- `app/Support/VersionedCache`: estratégia de cache versionado

## Autenticação

A autenticação usa JWT no guard `api`.

Fluxo básico:

1. `POST /api/auth/login`
2. Receber `access_token`
3. Enviar header `Authorization: Bearer <token>` nas rotas protegidas

Rotas de autenticação:

- `POST /api/auth/login`
- `GET /api/auth/me`
- `POST /api/auth/refresh`
- `POST /api/auth/logout`

## Resumo de Rotas

Públicas:

- `POST /api/users` (registro)
- `POST /api/auth/login`

Protegidas (`auth:api`):

- Notifications
	- `GET /api/notifications`
	- `PATCH /api/notifications/read`
	- `PATCH /api/notifications/{notification}/read`
- Memories
	- `GET /api/memories`
	- `POST /api/memories`
	- `GET /api/memories/{memory}`
	- `PATCH /api/memories/{memory}`
	- `DELETE /api/memories/{memory}`
	- `GET /api/memories/{memory}/logs`
	- `GET /api/memories/{memory}/export`
- Categories
	- `apiResource /api/categories`
- Admin only (`role:admin`)
	- `apiResource /api/plans`
	- `apiResource /api/users` (exceto store)

## Setup Local (Sail)

Pré-requisitos:

- Docker + Docker Compose

Passos:

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan jwt:secret
./vendor/bin/sail artisan migrate --seed
```

API disponível em:

- `http://localhost`

Serviços de infra no `compose.yaml`:

- `pgsql`
- `redis`

## Usuários de Seed

Após `migrate --seed`, os usuários padrão são:

- `admin@memory.io` / `password`
- `basic@memory.io` / `password`
- `premium@memory.io` / `password`

## Testes

Exemplo de execução das suítes críticas:

```bash
./vendor/bin/sail artisan test tests/Feature/MemoryApiTest.php tests/Feature/CategoryApiTest.php tests/Feature/NotificationApiTest.php tests/Feature/DeleteExpiredMemoriesJobTest.php
```

Executar todos os testes:

```bash
./vendor/bin/sail artisan test
```

## CI

Pipeline em `.github/workflows/ci.yml` com gates mínimos:

- Lint/format (`pint --test`)
- Checagem de migrations (`migrate:fresh --force`)
- Testes feature críticos

## Operação em Produção

### Scheduler

Jobs agendados em `routes/console.php`:

- `NotifyMemoriesPendingDeletionJob`: diário às `00:05`
- `DeleteExpiredMemoriesJob`: diário às `00:10`

Configurar cron:

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker

Executar worker continuamente (Supervisor/systemd recomendado):

```bash
php artisan queue:work --queue=default --tries=3 --timeout=90
```

Para Redis explicitamente:

```bash
php artisan queue:work redis --queue=default --tries=3 --timeout=90
```

Variável recomendada:

```env
QUEUE_CONNECTION=redis
```

### Redis

Variáveis recomendadas:

```env
CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

Validação rápida:

```bash
php artisan config:clear
php artisan cache:clear
php artisan tinker --execute="cache()->put('healthcheck','ok',60); echo cache('healthcheck');"
```

### Logs

Usar canal diário com retenção:

```env
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=14
```

### Backup PostgreSQL

Exemplo genérico:

```bash
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "backup-$(date +%F-%H%M).sql.gz"
```

Exemplo com Sail:

```bash
./vendor/bin/sail exec pgsql sh -lc 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' | gzip > "backup-$(date +%F-%H%M).sql.gz"
```

Política mínima:

- backup diário
- retenção entre 7 e 30 dias
- teste periódico de restore em staging
