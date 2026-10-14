# Cadê Os Times?

> *Cadê Os Times?* — a pergunta clássica da pelada, feita pelos jogadores enquanto os capitães demoram a escolher. Nosso app responde.

Aplicativo para organização de peladas amadoras. Resolve o que quase toda pelada enfrenta: **quem vai hoje?**, **como montar times equilibrados?** e **quem foi o craque da rodada?**. Backend em Laravel, mobile em React Native, push via Firebase.

---

## Sumário

- [O que o app faz](#o-que-o-app-faz)
- [Stack técnica](#stack-técnica)
- [Estrutura do monorepo](#estrutura-do-monorepo)
- [Pré-requisitos](#pré-requisitos)
- [Como rodar — backend](#como-rodar--backend)
- [Como rodar — mobile](#como-rodar--mobile)
- [Operação em produção](#operação-em-produção)
- [Como rodar os testes](#como-rodar-os-testes)
- [Coleção Postman](#coleção-postman)
- [Endpoints da API](#endpoints-da-api)
- [Eventos de push](#eventos-de-push)
- [Decisões arquiteturais](#decisões-arquiteturais)
- [Ondas de entrega](#ondas-de-entrega)

---

## O que o app faz

### Fluxo do jogador

1. **Cadastra** com nome, e-mail, senha; define seu nível (1-10 estrelas) e até 2 posições em campo (GOL/ZAG/LAT/MEI/ATA).
2. **Cria uma pelada** (como admin) ou **entra em uma existente** pelo código curto (`PEL-XXXXX`) ou link de convite.
3. A cada ocorrência da pelada, **confirma presença** ("Vou" ou "Não vou") dentro do prazo.
4. Depois que o admin fecha a lista e sorteia, **vê os times prontos** na tela "Cadê Os Times?".
5. Ao fim da partida, **vota nos destaques** durante 24h: Craque, Goleador, Garçom, Muralha, Paredão, Muleke da Pelada, Pior da Pelada, Pernas de Pau.
6. Acompanha o **ranking** com filtros de período (Mês / Ano / Últimas 10 / Geral).

### Fluxo do admin

Mesmo do jogador, mais:

- Configura a pelada (dia da semana, horário, jogadores por time, goleiros fixos ou não, prazo de confirmação, categorias de votação habilitadas).
- Marca membros como **goleiro fixo** (fica de fora do sorteio de linha e é distribuído entre os times).
- Aciona o **"Cadê Os Times?"** — botão-mantra que gera os times balanceados. Pode refazer quantas vezes quiser antes de publicar.
- **Publica** o sorteio — dispara push para todos os confirmados.
- Gerencia membros (remove, gera link de convite).

### Regras de negócio críticas

- **Balanceamento de estrelas** nos times via snake draft, tolerância ±2 estrelas entre maior e menor soma.
- **Goleiros fixos** não entram no cálculo de equilíbrio — são distribuídos à parte.
- **Máximo 60% da mesma posição** por time (exceto se o pool de confirmados não permitir — nesse caso, avisa).
- **Antifraude em votação:** não vota em si mesmo, só quem confirmou presença vota, uma vez por categoria (unique index garante no banco).
- **Pontos do ranking:** voto positivo = +1, mais votado da sessão = +3 bônus, voto humorístico = −1 sem bônus.
- **Empates em categoria positiva:** todos os líderes ganham o bônus (regra amigável, não arbitrária).

---

## Stack técnica

| Camada | Tecnologia | Versão |
|---|---|---|
| Backend | PHP + Laravel | 8.2+ / 11.x |
| Autenticação | Laravel Sanctum | — |
| Banco de dados | MySQL | 8+ |
| Queue | Laravel Queue (driver `database`) | — |
| Push | Firebase Cloud Messaging (`kreait/laravel-firebase`) | — |
| Mobile | React Native (CLI puro) + TypeScript | 0.76 / 5.5 |
| Navegação | React Navigation | 7.x |
| Validação | React Hook Form + Zod | — |
| Estado global mobile | Zustand | 5.x |
| HTTP client mobile | axios | 1.7+ |
| Storage seguro mobile | react-native-keychain | 9.x |
| Push mobile | @react-native-firebase/messaging | 21.x |

---

## Estrutura do monorepo

```
kd/
├── backend/                  # API REST Laravel 11
│   ├── app/
│   │   ├── Domain/           # Lógica pura (TeamDraw, Ranking, Notification, Enums) — zero dependência de Laravel
│   │   ├── Http/             # Controllers, Requests, Resources, Middleware
│   │   ├── Models/           # Eloquent models (User, Pelada, PeladaSession, Draw, Vote, etc.)
│   │   ├── Policies/         # Autorização (PeladaPolicy)
│   │   ├── Services/         # Pelada, Draw, Voting, Ranking, Notification
│   │   ├── Jobs/             # Jobs de ciclo de vida (OpenVoting, ClosePresence, SendPush, etc.)
│   │   ├── Console/Commands/ # peladas:open-sessions (scheduler)
│   │   └── Providers/        # AppServiceProvider (bindings)
│   ├── config/cade-os-times.php  # Config customizada (tolerâncias, deadlines)
│   ├── database/
│   │   ├── migrations/       # 17 migrations (todas as 18 tabelas do schema)
│   │   ├── seeders/          # VotingCategoriesSeeder (8 categorias)
│   │   └── factories/        # Factories para testes
│   ├── routes/
│   │   ├── api.php           # Todas as rotas da API REST
│   │   └── console.php       # Schedule do scheduler
│   ├── tests/
│   │   ├── Unit/             # Domínio puro (snake draft, ranking computer)
│   │   └── Feature/          # Rotas HTTP
│   └── README.md             # Setup detalhado do backend
│
├── mobile/                   # App React Native CLI puro
│   ├── App.tsx
│   ├── index.js
│   ├── src/
│   │   ├── theme/            # Cores, tipografia, espaçamento
│   │   ├── i18n/pt-BR.ts     # Strings da marca ("Cadê você?", "Craque", etc.)
│   │   ├── types/            # DTOs espelhando Resources do backend
│   │   ├── services/
│   │   │   ├── api/          # axios + endpoints por domínio
│   │   │   ├── storage/      # secure token (Keychain/Keystore)
│   │   │   └── push/         # FCM wrapper + routeFromMessage
│   │   ├── schemas/          # Zod schemas (auth, pelada, profile)
│   │   ├── state/            # Zustand authStore
│   │   ├── hooks/            # usePeladas, useCurrentSession, useDraw, useRanking, usePushRegistration
│   │   ├── navigation/       # RootNavigator, AuthStack, AppStack, linking, navigationRef
│   │   ├── screens/
│   │   │   ├── auth/         # Login, Register, ForgotPassword
│   │   │   ├── peladas/      # Home, Create, Join, Detail, Presence
│   │   │   ├── draw/         # DrawScreen ("Cadê Os Times?")
│   │   │   ├── voting/       # VotingScreen
│   │   │   ├── ranking/      # RankingScreen
│   │   │   ├── profile/      # ProfileScreen
│   │   │   └── SplashScreen.tsx
│   │   └── components/
│   │       ├── ui/           # Button, Input, Card, StarRating, CadeOsTimesButton
│   │       ├── pelada/       # PeladaCard, MemberRow, PresenceToggle
│   │       ├── draw/         # TeamCard
│   │       ├── voting/       # CategoryCard
│   │       └── ranking/      # RankingRow
│   └── README.md             # Setup detalhado do mobile
│
└── docs/
    ├── api-collection.json   # Coleção Postman v2.1 pronta pra importar
    └── ondas/                # Docs de entrega por onda (6 arquivos)
```

---

## Pré-requisitos

### Backend
- **PHP** 8.2+ com extensões: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`, `bcmath`
- **Composer** 2.x
- **MySQL** 8+ rodando localmente (ou remoto)
- **(Opcional para push)** Conta Firebase com projeto criado

### Mobile
- **Node.js** 18+
- **npm** ou **yarn**
- **Android:** JDK 17 + Android Studio (SDK 34+) + emulador ou device físico
- **iOS (só em macOS):** Xcode 15+ + CocoaPods + simulador ou device físico
- **(Opcional para push)** `google-services.json` (Android) e `GoogleService-Info.plist` (iOS) do mesmo projeto Firebase

### Ferramentas úteis
- **Postman** ou **Insomnia** para testar a API (coleção pronta em [docs/api-collection.json](docs/api-collection.json))
- **DBeaver** / **TablePlus** / similar para inspecionar o MySQL em dev

---

## Como rodar — backend

Os arquivos versionados neste repositório são **apenas os customizados** (migrations, models, controllers, services, rotas, testes). O skeleton do Laravel (bootstrap, public/, artisan, etc.) é gerado pelo Composer na primeira execução.

### Passo 1 — Gerar o skeleton e sobrepor os arquivos customizados

```bash
cd backend

# Gera o Laravel em um diretório temporário
composer create-project laravel/laravel ../_laravel-skel --no-install

# Sobrepõe nossos arquivos (cp -rn não sobrescreve os que já existem)
cp -rn ../_laravel-skel/. .
rm -rf ../_laravel-skel

composer install
```

### Passo 2 — Habilitar Sanctum (API tokens)

```bash
php artisan install:api
```

Isso publica a migration do `personal_access_tokens` e liga o middleware de auth da API.

### Passo 3 — Instalar integração Firebase

```bash
composer require kreait/laravel-firebase
```

### Passo 4 — Configurar o ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Edite `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cade_os_times
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

# Push: pode começar false em dev local (jobs logam mas não enviam)
PUSH_ENABLED=false
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json

# Janelas de tempo (valores do brief)
CADE_OS_TIMES_INVITE_TTL_HOURS=72
CADE_OS_TIMES_VOTING_WINDOW_HOURS=24
CADE_OS_TIMES_DRAW_STAR_TOLERANCE=2
CADE_OS_TIMES_POSITION_MAX_RATIO=0.6
CADE_OS_TIMES_MATCH_DURATION_HOURS=2
```

Crie o banco:

```bash
mysql -u root -e "CREATE DATABASE cade_os_times CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Passo 5 — Rodar migrations e seeds

```bash
php artisan migrate --seed
```

O seed popula as **8 categorias de votação** (Craque, Goleador, Garçom, Muralha, Paredão, Muleke, Pior, Pernas de Pau) com os pontos exatos do brief.

### Passo 6 — Configurar Firebase (pular se `PUSH_ENABLED=false`)

1. No [console Firebase](https://console.firebase.google.com), crie um projeto e baixe o arquivo **Service Account JSON** (Configurações → Contas de serviço → Gerar nova chave privada).
2. Salve em `storage/app/firebase/service-account.json`.
3. `PUSH_ENABLED=true` no `.env`.

### Passo 7 — Subir a API

```bash
php artisan serve
```

API disponível em `http://localhost:8000/api`. Teste com:

```bash
curl http://localhost:8000/api/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"Test","email":"test@example.com","password":"senha1234","password_confirmation":"senha1234"}'
```

Deve retornar `201` com `{user, token}`.

---

## Como rodar — mobile

Mesmo esquema do backend: versionamos só os arquivos customizados; o RN CLI gera o skeleton (`android/`, `ios/`, gradle, Podfile).

### Passo 1 — Gerar o skeleton do React Native

```bash
cd mobile
npx @react-native-community/cli@latest init CadeOsTimes \
  --version 0.76.0 --skip-install --directory ../_rn-skel
cp -rn ../_rn-skel/. .
rm -rf ../_rn-skel
```

### Passo 2 — Instalar dependências

```bash
npm install
```

### Passo 3 — iOS: instalar pods

```bash
cd ios && pod install && cd ..
```

### Passo 4 — Configurar o endpoint da API

O [client axios](mobile/src/services/api/client.ts) usa valores default por plataforma:

| Ambiente | URL |
|---|---|
| Emulador Android (AVD) | `http://10.0.2.2:8000/api` |
| Simulador iOS | `http://localhost:8000/api` |
| Device físico | IP da sua máquina (ex.: `http://192.168.1.10:8000/api`) |

Para sobrescrever, defina `API_BASE_URL` via `.env` ou variável nativa do Metro.

### Passo 5 — Configurar Firebase (opcional em dev)

1. Baixe `google-services.json` do Firebase e coloque em `android/app/`.
   - Em `android/build.gradle`: `classpath 'com.google.gms:google-services:4.4.2'`
   - Em `android/app/build.gradle`: `apply plugin: 'com.google.gms.google-services'`
2. Baixe `GoogleService-Info.plist` e adicione ao target `CadeOsTimes` no Xcode.
   - Em Capabilities: habilite **Push Notifications** e **Background Modes → Remote notifications**.
   - `cd ios && pod install`

Sem Firebase, o app roda normalmente; só não recebe push.

### Passo 6 — Rodar

Em um terminal:

```bash
npm start    # Metro bundler
```

Em outro:

```bash
npm run android    # ou
npm run ios
```

Ao abrir, você verá a Splash "Cadê Os Times?", depois a tela de Login. Registre uma conta e já está dentro.

### Testando deep link de convite

```bash
# Android
adb shell am start -W -a android.intent.action.VIEW \
  -d "cadeostimes://peladas/join?token=abc123"

# iOS simulator
xcrun simctl openurl booted "cadeostimes://peladas/join?token=abc123"
```

---

## Operação em produção

### Queue worker (processa jobs de ciclo de vida)

```bash
php artisan queue:work --queue=default --sleep=3 --tries=3
```

Supervisiona com **supervisord**, **systemd** ou similar. Os jobs essenciais:

- `ClosePresenceJob` — fecha presença no deadline.
- `SendPresenceReminderJob` — lembrete 2h antes.
- `OpenVotingJob` — abre votação ao fim da pelada.
- `SendVotingReminderJob` — lembrete 2h antes de fechar votação.
- `CloseVotingAndComputeRankingJob` — fecha votação e atualiza ranking.
- `SendPushNotificationJob` — envia cada push individual.

### Scheduler (cria sessões semanais)

```bash
* * * * * cd /caminho/do/backend && php artisan schedule:run >> /dev/null 2>&1
```

Registre o cron acima. O scheduler do Laravel executa a cada minuto e decide quando rodar os comandos agendados em [routes/console.php](backend/routes/console.php). Hoje agendamos:

- `peladas:open-sessions` — diariamente às 03:00 (America/Sao_Paulo). Cria a próxima `PeladaSession` de cada pelada e agenda os jobs do ciclo de vida.

### Variáveis de ambiente de produção

| Chave | Valor esperado |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL pública com HTTPS |
| `DB_*` | Credenciais do MySQL gerenciado |
| `QUEUE_CONNECTION` | `database` (ou `redis` se provisionar) |
| `PUSH_ENABLED` | `true` |
| `FIREBASE_CREDENTIALS` | Caminho absoluto ou via volume secreto |
| `SANCTUM_STATEFUL_DOMAINS` | Domínio do frontend (se web) |

### Observabilidade mínima recomendada

- **Sentry** (backend + mobile) — captura de erros.
- **Logs estruturados** — Laravel já escreve em `storage/logs/`; em cloud, pipe pra Stackdriver/CloudWatch.
- **Métricas básicas** — tamanho da fila (`php artisan queue:monitor`), taxa de push entregues vs tentados.

---

## Como rodar os testes

### Backend

Testes rodam contra **SQLite in-memory** (configurado no `phpunit.xml` do skeleton Laravel 11). Não é preciso configurar banco de teste.

```bash
cd backend

php artisan test                                  # tudo (Unit + Feature)
php artisan test --testsuite=Unit                 # domínio puro — milissegundos
php artisan test tests/Unit/Domain/TeamDraw       # só snake draft
php artisan test tests/Feature/VotingTest.php     # só uma rota
php artisan test --filter it_scores_positive      # por nome de teste
```

**Cobertura atual — 45 cenários:**

| Suite | Arquivos | Cenários |
|---|---|---|
| Unit | `SnakeDraftStrategyTest`, `RankingComputerTest` | 18 |
| Feature | `AuthTest`, `PeladaTest`, `PresenceTest`, `DrawTest`, `VotingTest`, `RankingTest` | 27 |

### Mobile

```bash
cd mobile

npm run typecheck    # tsc --noEmit (checa tipos)
npm run lint         # ESLint
npm test             # Jest (presets do RN)
```

---

## Coleção Postman

Importe [docs/api-collection.json](docs/api-collection.json) no Postman ou Insomnia.

Como usar:

1. Ajuste a variável de coleção `base_url` se necessário (default: `http://localhost:8000/api`).
2. Rode **Auth → Register** ou **Auth → Login** — o script salva `{{token}}` e `{{user_id}}` automaticamente.
3. Os demais endpoints herdam `Bearer {{token}}` — já vão autenticados.
4. **Peladas → Create** salva `{{pelada_id}}`, **Presence → Get current session** salva `{{session_id}}`, e assim por diante. Dá pra navegar a coleção em ordem e ir acumulando estado.

7 pastas organizadas por domínio:

- **Auth** (4) — register, login, forgot-password, logout
- **Me** (5) — get/update profile, notification prefs, device tokens
- **Peladas** (8) — list, create, show, join, invites, members, voting settings
- **Presence** (2) — current session, confirm
- **Draw** (3) — generate, publish, show published
- **Voting** (3) — get voting, cast vote, results
- **Ranking** (4) — por período e por usuário

---

## Endpoints da API

| Método | Rota | Auth | Propósito |
|---|---|---|---|
| POST | `/api/auth/register` | — (throttle) | Cadastro + retorna token Sanctum |
| POST | `/api/auth/login` | — (throttle) | Login + retorna token |
| POST | `/api/auth/forgot-password` | — (throttle) | Envia e-mail de reset |
| POST | `/api/auth/logout` | sanctum | Revoga token atual |
| GET | `/api/me` | sanctum | Perfil do usuário logado |
| PUT | `/api/me` | sanctum | Edita nome, skill, posições |
| PUT | `/api/me/notification-preferences` | sanctum | Silencia eventos específicos |
| POST | `/api/me/device-tokens` | sanctum | Registra FCM token |
| DELETE | `/api/me/device-tokens/{id}` | sanctum | Remove device |
| POST | `/api/peladas` | sanctum | Cria pelada (criador vira admin) |
| GET | `/api/peladas` | sanctum | Lista peladas do usuário |
| GET | `/api/peladas/{id}` | sanctum + policy | Detalhe + membros |
| POST | `/api/peladas/join` | sanctum | Entra via short_code ou invite_token |
| POST | `/api/peladas/{id}/invites` | admin | Gera token de convite |
| PUT | `/api/peladas/{id}/members/{userId}` | admin | Marca goleiro fixo, muda role |
| DELETE | `/api/peladas/{id}/members/{userId}` | admin | Remove membro |
| PUT | `/api/peladas/{id}/voting-settings` | admin | Habilita/desabilita categorias |
| GET | `/api/peladas/{id}/sessions/current` | member | Próxima sessão aberta |
| POST | `/api/sessions/{id}/presence` | member | Confirma "Vou" ou "Não vou" |
| POST | `/api/sessions/{id}/draw` | admin | Gera sorteio ("Cadê Os Times?") |
| GET | `/api/sessions/{id}/draw` | member | Sorteio publicado |
| POST | `/api/draws/{id}/publish` | admin | Publica o sorteio |
| GET | `/api/sessions/{id}/voting` | member | Status + categorias + meus votos |
| POST | `/api/voting-sessions/{id}/votes` | member confirmado | Registra voto |
| GET | `/api/voting-sessions/{id}/results` | member | Resultados (pós-fechamento) |
| GET | `/api/peladas/{id}/ranking?period=month\|year\|all\|last10` | member | Ranking ordenado |
| GET | `/api/peladas/{id}/ranking/{userId}` | member | Detalhe de um jogador |

---

## Eventos de push

Todos via FCM, respeitando [notification_preferences](backend/app/Models/NotificationPreference.php) do usuário (exceto `member_removed`, não-silenciável):

| Evento | Origem | Destinatário | Mensagem |
|---|---|---|---|
| `session_opened` | Scheduler diário | Todos os membros | "Pelada X está marcada!" |
| `presence_deadline_reminder` | Job (2h antes do deadline) | Quem não respondeu | "Cadê você? Confirma presença." |
| `draw_published` | Após admin publicar | Confirmados | "Cadê Os Times? Já saiu!" |
| `voting_opened` | Job (fim da pelada) | Confirmados | "Hora de eleger o craque da pelada" |
| `voting_deadline_reminder` | Job (2h antes de fechar) | Confirmados que não votaram | "Última chance de votar!" |
| `voting_closed` | Job (fechamento) | Confirmados | "Ranking atualizado!" |
| `member_removed` | Admin remove membro | Alvo da remoção | "Você saiu da pelada X." |

**Tap em push** (mobile) — o app rota automaticamente para a tela relevante:
- Presence push → abre tela de confirmação
- Draw push → abre tela de sorteio
- Voting push → abre tela de votação
- Voting closed push → abre ranking
- Member removed push → volta pra Home

---

## Decisões arquiteturais

### 1. Zustand no mobile (ao invés de Context API ou Redux)

**Escolha:** Zustand para estado global (status de auth, user, token). `useState` local em tudo que vive em uma tela só.

- **Context API** re-renderiza toda a árvore consumidora a cada mudança — custo silencioso que leva a `useMemo`/`useCallback` defensivos.
- **Redux** é over-engineering para o escopo: boilerplate (actions, reducers, middlewares) e store que vira despejo.
- **Zustand** tem API minúscula (`create`/`useStore`), seletores naturalmente evitam re-render, zero Provider. Stack compatível com React Native CLI sem gambiarra.
- Regra prática: **lista de peladas fica em hook local, não no store.** Só o que cruza telas (token, user) é global.

### 2. Queue driver `database` como default

**Escolha:** `QUEUE_CONNECTION=database` em produção v1.

- Carga esperada é baixíssima: jobs disparam em horários previsíveis (abrir sessão, lembretes, fechar votação). Não é processamento em tempo real.
- **Redis** exige provisionar + manter mais uma dependência. Custo operacional sem contrapartida técnica.
- **database** usa o MySQL que já está lá. Debug por SQL, confiabilidade transacional, setup zero.
- Código dos jobs é **agnóstico** ao driver. Migrar para Redis no futuro é trocar `.env` — zero linhas de código mudam.

### 3. Algoritmo de sorteio em domínio puro, atrás de interface

**Escolha:** `TeamDrawStrategyInterface` + `SnakeDraftStrategy` em `app/Domain/TeamDraw/`, sem nenhuma dependência de Laravel ou Eloquent.

- Testes unitários rodam em milissegundos (extend `\PHPUnit\Framework\TestCase` direto).
- Trocar algoritmo no futuro (ex.: genético, simulated annealing) é criar outra classe que implementa a interface e trocar o binding no `AppServiceProvider`.
- DTOs imutáveis (`readonly class`) garantem determinismo do cálculo.

### 4. Push: mesmo payload, dois destinatários

**Escolha:** Todo push carrega **sempre** `pelada_id` + `pelada_session_id` + ID específico do evento (ex.: `voting_session_id`).

- Mobile pode navegar direto para a tela certa sem round-trip adicional.
- Redundância de 2 campos no payload é barata e transforma "tap no push" em navegação instantânea.

### 5. Testes unitários focados nas regras críticas, feature tests no comportamento observável

- **`SnakeDraftStrategyTest` (10 cenários):** balance, goleiros, swap de posição, determinismo.
- **`RankingComputerTest` (8 cenários):** pontos positivos, bônus de vencedor, empates, humorísticos.
- **Feature tests (27 cenários):** fazem HTTP real contra SQLite in-memory, validam status codes + JSON + estado do banco. Zero mocks internos.

---

## Ondas de entrega

| Onda | Escopo | Doc |
|---|---|---|
| 1 | Backend: migrations, seeders, models, auth, CRUD de pelada, presença | [onda-1.md](docs/ondas/onda-1.md) |
| 2 | Backend: `TeamDrawService` + estratégia snake draft + testes | [onda-2.md](docs/ondas/onda-2.md) |
| 3 | Backend: votação, ranking, jobs e integração FCM | [onda-3.md](docs/ondas/onda-3.md) |
| 4 | Mobile: navegação, auth, peladas, presença | [onda-4.md](docs/ondas/onda-4.md) |
| 5 | Mobile: sorteio, votação, ranking, push | [onda-5.md](docs/ondas/onda-5.md) |
| 6 | Coleção Postman + feature tests + deep link de push + polimento | [onda-6.md](docs/ondas/onda-6.md) |

**Todas as ondas concluídas.** Projeto pronto para deploy após provisionamento de infra.

---

## Suporte

- Dúvidas sobre backend → [backend/README.md](backend/README.md)
- Dúvidas sobre mobile → [mobile/README.md](mobile/README.md)
- Coleção de requisições → [docs/api-collection.json](docs/api-collection.json)
- Detalhes de cada onda → [docs/ondas/](docs/ondas/)
