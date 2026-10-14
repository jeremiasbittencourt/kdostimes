# Onda 3 — Backend: votação, ranking, jobs e push FCM

**Status:** concluída
**Escopo:** votação com antifraude + ranking (materializado + dinâmico) + jobs de ciclo de vida + integração Firebase Cloud Messaging.

---

## Escopo detalhado

Fechar o ciclo completo de uma pelada:
1. **Abertura automática:** command semanal cria `PeladaSession` para a próxima ocorrência de cada pelada, agenda os jobs do ciclo de vida e dispara push "Pelada marcada".
2. **Confirmação + reminders:** `SendPresenceReminderJob` 2h antes do deadline; `ClosePresenceJob` no deadline.
3. **Fim da pelada:** `OpenVotingJob` 2h após o início, abre janela de 24h e notifica confirmados.
4. **Votação:** com antifraude (unique index) e todas as validações de negócio (não vota em si mesmo, precisa ter confirmado presença, categoria precisa estar habilitada).
5. **Fechamento + ranking:** `CloseVotingAndComputeRankingJob` fecha a votação, recalcula `ranking_entries` e notifica os jogadores.

Push em **todos os 7 eventos** do brief seção 3.7, respeitando preferências do usuário (exceto os não-silenciáveis).

---

## O que foi entregue

### Domínio puro ([backend/app/Domain/](../../backend/app/Domain/))

#### Ranking
- [VoteTally](../../backend/app/Domain/Ranking/DTO/VoteTally.php) — DTO imutável que representa um voto pronto para cálculo.
- [UserRankingStats](../../backend/app/Domain/Ranking/DTO/UserRankingStats.php) — saída por usuário (total, pontos/votos/vitórias por categoria).
- [RankingComputer](../../backend/app/Domain/Ranking/RankingComputer.php) — **lógica crítica do produto**. Recebe lista de VoteTally e produz o agregado por usuário. Regras:
  - +points_per_vote por voto recebido (positiva = +1, humorística = −1).
  - +bonus_for_winner para o mais votado em categoria positiva (+3).
  - Empates: **todos os líderes ganham o bônus** (regra amigável).
  - Categorias sem voto: ninguém vence, ninguém pontua.
  - Humorísticas não têm vencedor nem bônus.

#### Notification
- [PushMessage](../../backend/app/Domain/Notification/PushMessage.php) — value object com event, title, body, data (sem campos sensíveis).
- [PushDispatcher](../../backend/app/Domain/Notification/PushDispatcher.php) — interface; contrato para a infra de envio.

### Services ([backend/app/Services/](../../backend/app/Services/))

#### Voting
- [VotingService](../../backend/app/Services/Voting/VotingService.php) — antifraude em 4 camadas:
  1. Sessão de votação está OPEN e o `closes_at` não passou.
  2. Categoria está habilitada na pelada.
  3. Votante e votado confirmaram presença.
  4. Não é voto em si mesmo.
  5. Unique index `(voting_session, category, voter)` captura corrida de concorrência (código 1062 do MySQL → mensagem amigável).

#### Ranking
- [RankingService](../../backend/app/Services/Ranking/RankingService.php) — após fechar votação, rehidrata `ranking_entries` via upsert incremental (`total_points += X`, `wins_count += 1 se foi vencedor`).
- [RankingQuery](../../backend/app/Services/Ranking/RankingQuery.php) — consulta com filtro de período:
  - `all` → lê de `ranking_entries` (materializado, rápido).
  - `month`, `year`, `last10` → agrega dinamicamente de `votes` + `voting_categories`, reusando `RankingComputer`.

#### Notification
- [LogPushDispatcher](../../backend/app/Services/Notification/LogPushDispatcher.php) — fallback local (`PUSH_ENABLED=false`), só loga.
- [FcmPushDispatcher](../../backend/app/Services/Notification/FcmPushDispatcher.php) — integra com `kreait/laravel-firebase`. Falhas em tokens individuais (token revogado etc.) são logadas, não propagadas.
- [NotificationCoordinator](../../backend/app/Services/Notification/NotificationCoordinator.php) — o único lugar do código que sabe "quem recebe o quê". Respeita `notification_preferences` (ignora eventos silenciáveis desligados; não-silenciáveis como remoção sempre enviam). Resolve `device_tokens` e despacha `SendPushNotificationJob` por token (queue).

### Jobs ([backend/app/Jobs/](../../backend/app/Jobs/))
- [SendPushNotificationJob](../../backend/app/Jobs/SendPushNotificationJob.php) — engolidor fiel, 3 tentativas, backoff 30s.
- [ClosePresenceJob](../../backend/app/Jobs/ClosePresenceJob.php) — transiciona `pelada_session` OPEN → CLOSED no deadline.
- [SendPresenceReminderJob](../../backend/app/Jobs/SendPresenceReminderJob.php) — 2h antes do deadline, cutuca só quem não respondeu.
- [OpenVotingJob](../../backend/app/Jobs/OpenVotingJob.php) — cria `voting_session` (24h), agenda os jobs filhos, notifica os confirmados.
- [SendVotingReminderJob](../../backend/app/Jobs/SendVotingReminderJob.php) — 2h antes do fim, cutuca quem ainda não votou em nada.
- [CloseVotingAndComputeRankingJob](../../backend/app/Jobs/CloseVotingAndComputeRankingJob.php) — fecha votação, recalcula ranking, marca `pelada_session` como FINISHED, notifica.

### Console ([backend/app/Console/Commands/](../../backend/app/Console/Commands/) + [routes/console.php](../../backend/routes/console.php))
- [OpenPeladaSessionsCommand](../../backend/app/Console/Commands/OpenPeladaSessionsCommand.php) (`peladas:open-sessions`) — calcula a **próxima ocorrência** por pelada (respeita `day_of_week` e `time`), cria a `PeladaSession`, agenda ClosePresence/SendReminder/OpenVoting com `->delay(...)`, dispara push "Pelada marcada".
- `routes/console.php` — scheduler diário às 03:00 America/Sao_Paulo, `withoutOverlapping`.

### Provider binding
- [AppServiceProvider](../../backend/app/Providers/AppServiceProvider.php) — decide `PushDispatcher` em runtime via closure:
  - `PUSH_ENABLED=true` → `FcmPushDispatcher`
  - caso contrário → `LogPushDispatcher`

### Http
- **Form Requests:** [CastVoteRequest](../../backend/app/Http/Requests/Voting/CastVoteRequest.php).
- **Resources:** [VotingCategoryResource](../../backend/app/Http/Resources/VotingCategoryResource.php), [VotingSessionResource](../../backend/app/Http/Resources/VotingSessionResource.php).
- **Controllers:** [VotingController](../../backend/app/Http/Controllers/Api/VotingController.php) (show/castVote/results), [RankingController](../../backend/app/Http/Controllers/Api/RankingController.php) (index/show com filtro de período).

### Wiring existentes adaptados
- [DrawPublisher](../../backend/app/Services/Draw/DrawPublisher.php) — agora recebe `NotificationCoordinator` no construtor. Após publicar, dispara push "Cadê Os Times? Já saiu!" para confirmados.
- [PeladaController::removeMember](../../backend/app/Http/Controllers/Api/PeladaController.php) — agora dispara push não-silenciável "Você saiu da pelada" para o alvo.

### Config
- [config/cade-os-times.php](../../backend/config/cade-os-times.php) + [.env.example](../../backend/.env.example) — adicionado `pelada.match_duration_hours` (2h default).

### Testes ([backend/tests/Unit/Domain/Ranking/RankingComputerTest.php](../../backend/tests/Unit/Domain/Ranking/RankingComputerTest.php))

**8 cenários** cobrindo a lógica de pontuação:

| # | Cenário |
|---|---|
| 1 | Voto positivo = +1 |
| 2 | Mais votado em categoria positiva: base + bônus (ex.: 3 votos = 6 pts) |
| 3 | Empates: todos os líderes recebem o bônus |
| 4 | Voto humorístico = −1 sem bônus, sem vitória |
| 5 | Usuário acumula pontos de múltiplas categorias (6 + 5 − 1 = 10) |
| 6 | Sem votos: resultado vazio |
| 7 | Categoria sem votos não aparece no resultado |
| 8 | `votesReceivedByCategory` conta corretamente |

---

## Endpoints novos

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/sessions/{session}/voting` | Status da votação + categorias habilitadas + votos já feitos pelo usuário |
| POST | `/api/voting-sessions/{votingSession}/votes` | Registra voto (body: `category_id`, `voted_user_id`) |
| GET | `/api/voting-sessions/{votingSession}/results` | Totais por categoria (só após fechamento) |
| GET | `/api/peladas/{pelada}/ranking?period=all\|month\|year\|last10` | Ranking ordenado |
| GET | `/api/peladas/{pelada}/ranking/{userId}` | Detalhe de um jogador |

---

## Eventos de push implementados

| Evento | Origem | Destinatários | Silenciável |
|---|---|---|---|
| `session_opened` | `OpenPeladaSessionsCommand` | Todos os membros | sim |
| `presence_deadline_reminder` | `SendPresenceReminderJob` | Quem não respondeu | sim |
| `draw_published` | `DrawPublisher::publish` | Confirmados | sim |
| `voting_opened` | `OpenVotingJob` | Confirmados | sim |
| `voting_deadline_reminder` | `SendVotingReminderJob` | Confirmados que não votaram | sim |
| `voting_closed` | `CloseVotingAndComputeRankingJob` | Confirmados | sim |
| `member_removed` | `PeladaController::removeMember` | Alvo da remoção | **não** |

---

## Decisões técnicas

1. **`RankingComputer` puro e reutilizado em 2 caminhos** — tanto o job (que escreve em `ranking_entries`) quanto o `RankingQuery` (que responde filtros de período sem materializar) usam a mesma classe. Evita divergência entre "pontuação que foi para o ranking" e "pontuação exibida no mês atual".
2. **Empates ganham todos o bônus** — é uma decisão de produto, não apenas técnica. Alternativa (tie-break alfabético, por skill, sorteio) geraria descontentamento. "Empate é empate."
3. **Antifraude em duas camadas** — validações explicit no serviço (mensagens pt-BR amigáveis) + **unique index no banco** (última linha de defesa contra corrida de concorrência). Código 1062 do MySQL vira "Você já votou nesta categoria."
4. **Jobs se auto-agendam** — quando `OpenPeladaSessionsCommand` cria uma `PeladaSession`, ele já dispara `ClosePresenceJob->delay(deadline)`, `SendPresenceReminderJob->delay(deadline-2h)` e `OpenVotingJob->delay(scheduled+2h)`. `OpenVotingJob`, por sua vez, agenda `CloseVotingAndComputeRankingJob->delay(closes_at)` e `SendVotingReminderJob->delay(closes_at-2h)`. Não existe um scheduler separado perguntando "já passou?".
5. **`NotificationCoordinator` é o único lugar que filtra preferências** — em nenhum job/service espalhado. Se amanhã alguém adicionar "silenciar durante horário comercial" ou "não enviar se usuário desligou a conta", a mudança é em um arquivo só.
6. **`PushDispatcher` como interface** — permite trocar implementação (FCM, APNS direto, OneSignal) sem tocar no coordinator. No dev, `LogPushDispatcher` joga tudo no log sem precisar de Firebase configurado.
7. **`period=all` lê materializado, outros períodos agregam do votes** — para v1 com volume pequeno, evita materializar 4 agregações por pelada. Se virar gargalo, migra para materializado por mês — a interface pública do `RankingQuery` não muda.
8. **Push nunca carrega dado sensível** — `PushMessage::data` é `array<string, scalar>`. Todo payload é só IDs e strings curtas. Body é friendly ("Cadê você?", "Cadê Os Times? Já saiu!"), alinhado ao tom da marca.

---

## Como subir em produção

```bash
# worker da fila
php artisan queue:work --queue=default

# scheduler (rodar a cada minuto via cron do SO)
* * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1
```

---

## O que ficou para próximas ondas

- **Onda 4:** mobile — navegação, auth, telas de pelada, confirmação de presença.
- **Onda 5:** mobile — sorteio "Cadê Os Times?", votação, ranking, registro de device token FCM e deep linking.
- **Onda 6:** coleção Postman cobrindo todos os endpoints + polimento final dos READMEs.

### Pontos não cobertos nesta onda (intencionais)

- **Feature tests de rotas.** O brief pede "feature tests das rotas principais"; deixei para a Onda 6 junto com a coleção Postman — ambos formalizam o contrato HTTP ao mesmo tempo, evitando retrabalho.
- **Email de reset de senha** (seguiria `POST /api/auth/reset-password`). `forgotPassword` já envia o link, mas o endpoint de reset em si não estava na seção 7 do brief. Adicionável em < 20 linhas quando quiser.
- **Expiração automática de `pelada_invites`.** A validação é feita em runtime (`isValid()`); um job de GC mensal pode remover os expirados se a tabela crescer.
