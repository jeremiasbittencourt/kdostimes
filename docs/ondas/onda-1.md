# Onda 1 — Backend: fundação, auth e CRUD de pelada

**Status:** concluída
**Escopo:** migrations, seeders, models, autenticação Sanctum, CRUD de pelada, confirmação de presença.

---

## Escopo detalhado

Estabelecer o esqueleto do backend Laravel que sustenta o resto do produto:

- Schema completo do banco (todas as tabelas do brief seção 6), mesmo as que só serão escritas em ondas futuras, para garantir FKs consistentes desde o início.
- Autenticação de API (register/login/logout/forgot-password) com tokens Sanctum.
- Perfil do jogador: dados pessoais, posições (até 2), preferências de notificação, device tokens FCM.
- CRUD de peladas: criar, listar, ver detalhe, entrar via short_code ou invite token, gerenciar membros (incluindo marcar goleiro fixo), configurar categorias de votação.
- Confirmação de presença por sessão com janela travada pelo deadline.

---

## O que foi entregue

### Raiz
- [README.md](../../README.md) — visão do monorepo + **decisões arquiteturais** (Zustand no mobile, queue driver `database`) com justificativa.

### Backend — configuração e documentação
- [backend/README.md](../../backend/README.md) — passos de instalação (skeleton Laravel via Composer, `install:api` para Sanctum, `composer require kreait/laravel-firebase`, `migrate --seed`).
- [backend/.env.example](../../backend/.env.example) — envs de banco, Sanctum, FCM e da marca.
- [backend/config/cade-os-times.php](../../backend/config/cade-os-times.php) — tolerância de estrelas, ratio máximo de posição, TTL de convite, janela de votação, horas de reminder.

### Enums do domínio ([backend/app/Domain/Enums/](../../backend/app/Domain/Enums/))
`Position`, `PresenceStatus`, `SessionStatus`, `VoteCategoryType`, `NotificationEvent`, `MemberRole`, `DevicePlatform`, `VotingSessionStatus`. Usados por migrations, models e controllers para ter o mesmo vocabulário em toda a stack.

### Migrations ([backend/database/migrations/](../../backend/database/migrations/))
17 arquivos, ordem por FKs: `users`, `player_positions`, `notification_preferences`, `device_tokens`, `peladas`, `pelada_members`, `voting_categories`, `pelada_voting_settings`, `pelada_sessions`, `presence_confirmations`, `draws`, `draw_teams`, `draw_team_players`, `voting_sessions`, `votes`, `ranking_entries`, `pelada_invites`.

Destaques:
- **Unique antifraude em `votes`:** `(voting_session_id, voting_category_id, voter_user_id)` — impede votar 2x na mesma categoria.
- **Unique em `ranking_entries`:** `(pelada_id, user_id, voting_category_id)` — tabela materializada, uma linha por combinação.
- **Cascade deletes** em relações de propriedade.
- **`pelada_invites`** adicionado (não estava no schema da seção 6 do brief, mas é necessário para o "link de convite com token assinado e expirável" da seção 3.3).

### Seeders ([backend/database/seeders/](../../backend/database/seeders/))
- `VotingCategoriesSeeder` — popula as **8 categorias exatas** do brief com `points_per_vote` e `bonus_for_winner` batendo com a regra de negócio (positivas +1/+3, humorísticas −1/0).
- `DatabaseSeeder` — orquestra.

### Models Eloquent ([backend/app/Models/](../../backend/app/Models/))
16 models: `User`, `PlayerPosition`, `NotificationPreference`, `DeviceToken`, `Pelada`, `PeladaMember` (como Pivot incremental com timestamps), `PeladaSession`, `PresenceConfirmation`, `VotingCategory`, `PeladaVotingSetting`, `Draw`, `DrawTeam`, `DrawTeamPlayer`, `VotingSession`, `Vote`, `RankingEntry`, `PeladaInvite`.

Pontos:
- `User` usa `HasApiTokens` (Sanctum) e `hashed` cast na senha.
- Atributos sensíveis (`password`, `remember_token`, `DeviceToken.token`) em `$hidden`.
- Enums atribuídos via `casts()`.
- Métodos de conveniência em `Pelada`: `hasMember()`, `isAdmin()`.

### Services ([backend/app/Services/Pelada/](../../backend/app/Services/Pelada/))
- `PeladaCreator` — cria pelada com `short_code` único (ex.: `PEL-7K2X9`, alfabeto sem caracteres ambíguos 0/O/1/I), vincula o criador como admin e bootstrapa `pelada_voting_settings` para todas as categorias.
- `PeladaJoiner` — entrada por short_code ou invite token, `lockForUpdate` para contabilizar uso atômico do convite.

### Http ([backend/app/Http/](../../backend/app/Http/))
- **Controllers:** `AuthController`, `MeController`, `PeladaController`, `PresenceController` + `Controller` base com `AuthorizesRequests`.
- **Form Requests:** 10 arquivos; validação estrita (até 2 posições sem repetir, goleiro fixo só quando a pelada permite).
- **Resources:** 9 arquivos; serialização controlada com `whenLoaded` nas relações.

### Policies ([backend/app/Policies/PeladaPolicy.php](../../backend/app/Policies/PeladaPolicy.php))
`view`, `update`, `manageMembers`, `removeMember` (impede remover o admin), `configureVoting`, `drawTeams` (já pronta para onda 2).

### Rotas ([backend/routes/api.php](../../backend/routes/api.php))
Agrupadas por middleware — `throttle:5,1` em auth, `auth:sanctum` no resto.

---

## Endpoints

| Método | Rota | Proteção |
|---|---|---|
| POST | `/api/auth/register` | throttle |
| POST | `/api/auth/login` | throttle |
| POST | `/api/auth/forgot-password` | throttle |
| POST | `/api/auth/logout` | sanctum |
| GET | `/api/me` | sanctum |
| PUT | `/api/me` | sanctum |
| PUT | `/api/me/notification-preferences` | sanctum |
| POST | `/api/me/device-tokens` | sanctum |
| DELETE | `/api/me/device-tokens/{id}` | sanctum |
| POST | `/api/peladas` | sanctum |
| GET | `/api/peladas` | sanctum |
| POST | `/api/peladas/join` | sanctum |
| GET | `/api/peladas/{pelada}` | sanctum + policy |
| POST | `/api/peladas/{pelada}/invites` | sanctum + policy |
| PUT | `/api/peladas/{pelada}/members/{userId}` | sanctum + policy |
| DELETE | `/api/peladas/{pelada}/members/{userId}` | sanctum + policy |
| PUT | `/api/peladas/{pelada}/voting-settings` | sanctum + policy |
| GET | `/api/peladas/{pelada}/sessions/current` | sanctum + policy |
| POST | `/api/sessions/{session}/presence` | sanctum + policy |

---

## Decisões técnicas

1. **Schema completo logo de cara** — criamos todas as tabelas (incluindo votes, ranking_entries etc.) mesmo que o código que as escreve só venha nas próximas ondas. Evita migrations encadeadas e torna o FK layout estável.
2. **`PeladaMember` como Pivot incremental** — tem `id`, `timestamps`, atributos fillable. Dá pra fazer `member->update([...])` normalmente, simplificando os controllers vs uma attach/detach table pura.
3. **Instalação via skeleton + overlay** — README orienta o dev a rodar `composer create-project` primeiro e depois sobrepor nossos arquivos customizados. Evita versionar boilerplate que muda com cada release do Laravel.
4. **`password_reset_tokens` incluído na migration de users** — simplifica; substitui a migration padrão do Laravel.
5. **Alfabeto sem ambiguidade para `short_code`** — `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (sem 0, O, 1, I, L). Reduz erro de digitação quando o usuário fala "meu código é PEL-O0LI1".

---

## O que ficou para próximas ondas

- **Onda 2:** sorteio balanceado (`TeamDrawService` + `SnakeDraftStrategy`) e endpoints de sorteio.
- **Onda 3:** votação, ranking, jobs em background, push FCM.
- **Onda 4+:** mobile React Native.
