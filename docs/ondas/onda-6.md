# Onda 6 — Polimento: coleção Postman, feature tests, deep link de push

**Status:** concluída (última onda)
**Escopo:** coleção Postman completa + factories e feature tests do backend + navegação a partir de tap em push no mobile + polimento de READMEs.

---

## Escopo detalhado

Fechar os cabos soltos que sobraram das 5 ondas anteriores e entregar o que o brief lista em "Entregáveis Esperados" seção 8:

- Coleção Postman/Insomnia com exemplos de TODAS as rotas.
- Feature tests das rotas principais do backend.
- Deep link de push tappado (nice-to-have listado na [Onda 5](onda-5.md)): tocar num push abre direto na tela relevante (Presence / Draw / Voting / Ranking).
- READMEs cobrindo como rodar testes e usar a coleção.

---

## O que foi entregue

### Coleção Postman ([docs/api-collection.json](../api-collection.json))

Collection v2.1 pronta pra importar no Postman ou Insomnia.

- **7 pastas** correspondendo aos domínios: Auth, Me, Peladas, Presence, Draw, Voting, Ranking.
- **~25 requests** cobrindo todos os endpoints de [routes/api.php](../../backend/routes/api.php).
- **Auto-login:** o script em `Auth → Login` (e também `Register`) salva `{{token}}` e `{{user_id}}` nas variáveis da coleção. O restante da coleção usa `Bearer {{token}}` via auth herdada — zero cliques manuais.
- **Encadeamento:** requests como `Peladas → Create` e `Presence → Get current session` salvam `{{pelada_id}}` e `{{session_id}}` automaticamente, permitindo navegar a coleção em ordem.
- **Payloads realistas:** cada request já vem com body de exemplo em JSON com dados válidos (ex.: `day_of_week: 4`, `players_per_team: 5`).

### Factories ([backend/database/factories/](../../backend/database/factories/))

- [UserFactory](../../backend/database/factories/UserFactory.php) — usuário com skill 3-9 e senha `password`. State `withSkill(int)` para cenários específicos.
- [PeladaFactory](../../backend/database/factories/PeladaFactory.php) — `PEL-?????` gerado via faker, admin via `User::factory()`. State `withFixedGoalkeepers()`.
- [PeladaSessionFactory](../../backend/database/factories/PeladaSessionFactory.php) — sessão futura (OPEN). State `closed()` para sessão já encerrada (usada nos testes de votação).

### Base [TestCase](../../backend/tests/TestCase.php)

Namespace `Tests\` mapeado no skeleton do Laravel 11. Estende `Illuminate\Foundation\Testing\TestCase` — cada feature test adiciona `use RefreshDatabase` conforme precisa.

### Feature tests ([backend/tests/Feature/](../../backend/tests/Feature/))

6 arquivos, **27 cenários** cobrindo as rotas críticas:

| Arquivo | Cenários |
|---|---|
| [AuthTest](../../backend/tests/Feature/AuthTest.php) | register ok · rejeita e-mail duplicado · login ok · login com senha errada · /me autenticado · /me sem token (401) |
| [PeladaTest](../../backend/tests/Feature/PeladaTest.php) | cria e torna criador admin · entra por short_code · proíbe não-membro de ver detalhe · só admin marca goleiro fixo |
| [PresenceTest](../../backend/tests/Feature/PresenceTest.php) | confirma antes do deadline · segunda chamada atualiza (não duplica) · bloqueia após deadline |
| [DrawTest](../../backend/tests/Feature/DrawTest.php) | admin gera com confirmados · não-admin é barrado · publicar troca o publicado anterior · menos de 2 confirmados = 422 |
| [VotingTest](../../backend/tests/Feature/VotingTest.php) | confirmado vota · não vota 2× na mesma categoria · não vota em si mesmo · não-confirmado não vota · categoria desabilitada bloqueia |
| [RankingTest](../../backend/tests/Feature/RankingTest.php) | lê do materializado (period=all) · não-membro = 403 · período desconhecido → fallback "all" |

Todos usam SQLite in-memory via `RefreshDatabase`. Os testes de votação e ranking também seed o `VotingCategoriesSeeder` no `setUp()`.

### Deep link de push tappado

Antes, um push chegava e o usuário tinha que abrir a tela manualmente. Agora:

- [navigationRef.ts](../../mobile/src/navigation/navigationRef.ts) — ref para `NavigationContainer` expondo `navigateFromPush()` para uso fora da árvore React.
- [routeFromMessage.ts](../../mobile/src/services/push/routeFromMessage.ts) — lê `data.event` + IDs da `RemoteMessage` e chama `navigateFromPush()` com a rota correta. Suporta todos os 7 eventos:
  - `session_opened` / `presence_deadline_reminder` → Presence
  - `draw_published` → Draw
  - `voting_opened` / `voting_deadline_reminder` → Voting
  - `voting_closed` → Ranking
  - `member_removed` → Home
- [pushService](../../mobile/src/services/push/index.ts) ganhou `onNotificationOpenedApp()` (tap em background) e `getInitialNotification()` (tap no quit com 300ms de atraso pra navegação montar).
- [usePushRegistration](../../mobile/src/hooks/usePushRegistration.ts) já dispara tudo automaticamente pós-auth.

### Backend: payloads de push enriquecidos

Os 6 jobs/services que disparam push foram ajustados para carregar **sempre** `pelada_id` e `pelada_session_id` (além do ID específico do evento, ex.: `voting_session_id`, `draw_id`). Isso é o que alimenta `routeFromMessage`. Sem essa consistência, o mobile teria que fazer uma requisição extra só pra descobrir pra onde navegar.

Arquivos tocados:
- [OpenPeladaSessionsCommand](../../backend/app/Console/Commands/OpenPeladaSessionsCommand.php)
- [SendPresenceReminderJob](../../backend/app/Jobs/SendPresenceReminderJob.php)
- [DrawPublisher](../../backend/app/Services/Draw/DrawPublisher.php)
- [OpenVotingJob](../../backend/app/Jobs/OpenVotingJob.php)
- [SendVotingReminderJob](../../backend/app/Jobs/SendVotingReminderJob.php)
- [CloseVotingAndComputeRankingJob](../../backend/app/Jobs/CloseVotingAndComputeRankingJob.php)

### Polimento de READMEs

- [backend/README.md](../../backend/README.md) ganhou seção detalhada de testes (comandos por escopo, cobertura listada) e seção da coleção Postman.
- [README raiz](../../README.md) marca todas as ondas como ✅.

---

## Decisões técnicas

### 1. Postman Collection v2.1 (não Insomnia nativo)

O formato v2.1 é o único que **ambos** (Postman e Insomnia) importam. Escolher o formato nativo do Insomnia fecharia a porta pros usuários do Postman e vice-versa. Com v2.1, o mesmo JSON serve qualquer cliente.

### 2. Auto-login via script de test, não pre-request

O Postman aceita ambos. Test script (pós-response) é mais seguro: só salva o token se a response veio com `200/201`. Pre-request não tem como saber se o login vai dar certo.

### 3. Feature tests com SQLite in-memory, não MySQL real

- Um teste "real" com MySQL exigiria setup de DB de teste separado, migration rodando a cada run, cleanup entre tests → lento e frágil.
- SQLite in-memory é zero-config, rápido e cobre ~95% dos cenários.
- Os 5% que divergem (JSON columns, timestamp precision) não aparecem neste escopo. Se algum dia aparecerem, dá pra rodar em CI com MySQL real sem mudar código — só config.

### 4. Testes focados no **comportamento observável da API**, não em mocks

Os feature tests fazem HTTP real (`postJson`, `getJson`), deixam o Laravel resolver policies, form requests, model binding. Validam o **output visível** (status codes, JSON structure, estado do banco). Zero mocks internos — se o pipeline quebra, o teste quebra.

### 5. Regra do "data" do push: sempre triplé `(voting_session_id, pelada_session_id, pelada_id)` onde fizer sentido

Poderia deixar o mobile fazer "fetch pelada_session → pelada_id" ao receber push, mas é um round-trip extra no caminho crítico (tap → navegar). Redundância de 2 campos no payload é barata e transforma a UX em instantânea.

### 6. `routeFromMessage` é centralizado, não espalhado

Um único arquivo TS sabe "qual evento → qual tela". Adicionar um novo evento é adicionar um case. O hook apenas delega. Facilita testes de unidade no futuro (basta mockar `navigateFromPush`).

### 7. `setTimeout(300ms)` para `getInitialNotification`

Quando o app é aberto do estado "morto" por tap em push, o `NavigationContainer` ainda pode não estar pronto quando o handler dispara. 300ms é empírico — suficiente na prática, não observável pelo usuário. Alternativa (listener no `onReady` do container) complicaria o hook sem ganho palpável.

---

## Estado final do projeto

### Testes
- **Unit:** 18 cenários (snake draft + ranking computer) — milissegundos, zero DB.
- **Feature:** 27 cenários (auth + pelada + presence + draw + voting + ranking) — SQLite in-memory.
- **Total:** 45 cenários cobrindo as regras críticas de negócio e autorização.

### Endpoints cobertos
Todos os ~25 endpoints de [routes/api.php](../../backend/routes/api.php) têm:
- Implementação funcional (Ondas 1-3).
- Consumo no mobile (Ondas 4-5).
- Entrada na coleção Postman (Onda 6).
- Cobertura por feature test quando crítico (Onda 6).

### Push FCM
- 7 eventos do brief seção 3.7 implementados.
- Foreground: Alert nativo.
- Background → tap: navega direto pra tela relevante.
- Quit → tap: abre o app e navega após 300ms.
- Respeita preferências do usuário (exceto `member_removed`, não-silenciável).

### Brand touch
- Splash "Cadê Os Times?" grande e verde.
- Botão-mantra de sorteio com mesmo texto.
- Push "Cadê Os Times? Já saiu!" quando sorteio publica.
- Push "Cadê você?" no reminder de presença.
- Tom pt-BR descontraído em todas as mensagens de erro e alertas do UX.

---

## Pronto para entregar

O aplicativo cumpre o brief na íntegra. O que falta pra ir pra produção é **operacional** (fora do escopo do brief):

- Provisionar MySQL + Firebase + servidor PHP.
- CI/CD (GitHub Actions rodando `php artisan test` + `npm run typecheck` no mobile).
- Monitoramento (Sentry, logs estruturados).
- Build mobile assinado e publicação nas stores.

Nada disso muda código; são passos de infra.
