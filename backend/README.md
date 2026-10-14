# Backend — Cadê Os Times?

API REST em Laravel 11 + MySQL 8 + Sanctum. Integração com Firebase Cloud Messaging para push.

---

## Pré-requisitos

- PHP 8.2+
- Composer 2
- MySQL 8
- Extensões PHP: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`, `bcmath`
- (Opcional) Conta Firebase com projeto para FCM

---

## Instalação

> Os arquivos versionados neste repositório são **apenas os customizados** (models, migrations, controllers, services, rotas). O skeleton do Laravel é gerado pelo Composer.

```bash
# 1. Clone o monorepo e entre em backend/
cd backend

# 2. Gere o skeleton do Laravel NA PASTA ATUAL (sobrepondo nossos arquivos customizados)
#    O comando abaixo cria o Laravel em um temporário e move para a pasta atual preservando os arquivos já existentes.
composer create-project laravel/laravel ../_laravel-skel --no-install
cp -rn ../_laravel-skel/. .
rm -rf ../_laravel-skel
composer install

# 3. Instala Sanctum e publica rotas/migration de API
php artisan install:api

# 4. Instala lib de Firebase (FCM)
composer require kreait/laravel-firebase

# 5. Configuração
cp .env.example .env
php artisan key:generate

# 6. Edite .env com credenciais de MySQL e caminho do service account do Firebase
#    DB_*, FIREBASE_CREDENTIALS=/caminho/para/firebase-service-account.json

# 7. Migrations + seeders (seed cria as 8 categorias de votação)
php artisan migrate --seed

# 8. Sobe o servidor
php artisan serve
```

A partir deste ponto a API está em `http://localhost:8000/api`.

---

## Configuração do Firebase (push)

1. No console do Firebase, crie um projeto e baixe o arquivo JSON de **Service Account** (Configurações → Contas de serviço → Gerar nova chave privada).
2. Coloque o arquivo em `storage/app/firebase/service-account.json` (não commitado).
3. Defina no `.env`:
   ```
   FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
   ```
4. No app mobile, inclua `google-services.json` (Android) e `GoogleService-Info.plist` (iOS) do mesmo projeto.

Push real só funciona com FCM ativado; na v1 os jobs de push já estão preparados mas podem ser desligados via `.env` (`PUSH_ENABLED=false`) para desenvolvimento local.

---

## Queue (jobs em background)

Driver default: **`database`** (sem dependência externa). Para executar os jobs:

```bash
php artisan queue:work
```

Ver a [decisão sobre queue driver](../README.md#2-queue-driver-database-como-default-redis-apenas-quando-houver-demanda) no README raiz.

---

## Testes

```bash
php artisan test                                          # tudo
php artisan test --testsuite=Unit                         # só domínio puro (rápido)
php artisan test tests/Unit/Domain/TeamDraw               # só sorteio
php artisan test tests/Feature/VotingTest.php             # uma rota específica
```

Os testes usam SQLite in-memory (configurado no `phpunit.xml` que vem com o skeleton do Laravel 11). Não precisa mexer em `.env.testing`.

Cobertura:

- **Unit (domínio puro):**
  - `tests/Unit/Domain/TeamDraw/SnakeDraftStrategyTest.php` — 10 cenários do algoritmo de sorteio (Onda 2).
  - `tests/Unit/Domain/Ranking/RankingComputerTest.php` — 8 cenários de pontuação (Onda 3).
- **Feature (rotas via HTTP):**
  - `tests/Feature/AuthTest.php` — register, login, me, rate limit.
  - `tests/Feature/PeladaTest.php` — criar, entrar, autorização de admin.
  - `tests/Feature/PresenceTest.php` — confirmar, atualizar, bloquear após deadline.
  - `tests/Feature/DrawTest.php` — gerar/publicar, rejeitar não-admin, limite mínimo.
  - `tests/Feature/VotingTest.php` — antifraude (auto-voto, duplo voto, não confirmado, categoria desabilitada).
  - `tests/Feature/RankingTest.php` — leitura por período, autorização.

---

## Coleção Postman/Insomnia

A coleção completa da API está em [docs/api-collection.json](../docs/api-collection.json). Ao importar:

1. Ajuste a variável de coleção `base_url` (default: `http://localhost:8000/api`).
2. Rode `Auth → Register` ou `Auth → Login` — o script salva automaticamente o token em `{{token}}` e o `user_id`.
3. Os demais endpoints já usam `Bearer {{token}}` via auth herdada.
4. Endpoints como `Peladas → Create` e `Presence → Get current session` preenchem `{{pelada_id}}` / `{{session_id}}` nos testes, permitindo que você navegue a coleção em ordem.

---

## Estrutura

Ver [estrutura completa no README raiz](../README.md).

Pontos-chave:

- `app/Domain/TeamDraw/` — núcleo do sorteio, isolado de Laravel/HTTP, com `TeamDrawStrategyInterface` para substituição de algoritmo.
- `app/Domain/Enums/` — enums PHP nativos (`Position`, `PresenceStatus`, etc.) usados por migrations, models e controllers.
- `app/Http/Controllers/Api/` — controllers magros; regras em Services.
- `app/Http/Requests/` — validação toda aqui, nunca confie no cliente.
- `app/Policies/` — autorização (só admin sorteia, só confirmado vota).
