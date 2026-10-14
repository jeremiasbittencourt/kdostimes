# Onda 2 — Backend: sorteio balanceado de times

**Status:** concluída
**Escopo:** `TeamDrawService` + `SnakeDraftStrategy` + testes unitários + endpoints de sorteio.

---

## Escopo detalhado

Implementar o coração do produto: **o sorteio "Cadê Os Times?"**. Seguindo o pseudocódigo da seção 4 do brief:

1. Separar goleiros fixos, distribuir 1 por time.
2. Ordenar jogadores de linha por estrelas (desc).
3. Snake draft (serpentina).
4. Verificar desequilíbrio de posições; executar swaps que preservem a tolerância de estrelas.
5. Retornar composição + avisos quando o pool forçar exceções.

Tudo isolado em domínio puro, atrás de uma interface, com testes que rodam em milissegundos.

---

## O que foi entregue

### Domínio ([backend/app/Domain/TeamDraw/](../../backend/app/Domain/TeamDraw/))

**DTOs imutáveis** (`readonly class`, zero dependência de Laravel):
- [PlayerInput](../../backend/app/Domain/TeamDraw/DTO/PlayerInput.php) — id, skill, posições, flag de goleiro fixo. Método `effectiveLinePosition()` resolve a regra "GOL sem ser goleiro fixo → usa 2ª posição".
- [DrawInput](../../backend/app/Domain/TeamDraw/DTO/DrawInput.php) — lista de jogadores + parâmetros (players_per_team, has_fixed_goalkeepers, star_tolerance, position_max_ratio).
- [AssignedPlayer](../../backend/app/Domain/TeamDraw/DTO/AssignedPlayer.php) — resultado por jogador (user_id, skill, posição atribuída, is_goalkeeper).
- [TeamComposition](../../backend/app/Domain/TeamDraw/DTO/TeamComposition.php) — time formado; `totalRating()` exclui goleiros da soma (alinhado à regra 4.2: "goleiros fixos NÃO entram no cálculo de equilíbrio").
- [DrawResult](../../backend/app/Domain/TeamDraw/DTO/DrawResult.php) — `teams` + `warnings` (lista de strings em pt-BR).

**Interface:**
- [TeamDrawStrategyInterface](../../backend/app/Domain/TeamDraw/TeamDrawStrategyInterface.php) — um único método `draw(DrawInput): DrawResult`.

**Estratégia concreta:**
- [SnakeDraftStrategy](../../backend/app/Domain/TeamDraw/Strategies/SnakeDraftStrategy.php) — implementa o pseudocódigo completo.

### Service Layer ([backend/app/Services/Draw/](../../backend/app/Services/Draw/))

- [TeamDrawService](../../backend/app/Services/Draw/TeamDrawService.php) — orquestra:
  1. Lê presenças confirmadas (status=CONFIRMED) com eager load de `user.positions`.
  2. Busca flag `is_fixed_goalkeeper` dos `pelada_members`.
  3. Monta `PlayerInput[]` e chama a strategy.
  4. Em transação: **apaga os sorteios não publicados** da mesma sessão e cria um novo `Draw` + `DrawTeam`s + `DrawTeamPlayer`s.
  5. Warnings não vão ao banco — só voltam na resposta HTTP via `setAttribute`.
- [DrawPublisher](../../backend/app/Services/Draw/DrawPublisher.php) — marca `published=true`, despublica os demais sorteios da mesma sessão em transação, registra `published_at`.

### Infraestrutura

- [AppServiceProvider](../../backend/app/Providers/AppServiceProvider.php) — binding `TeamDrawStrategyInterface` → `SnakeDraftStrategy`.

### Resources ([backend/app/Http/Resources/](../../backend/app/Http/Resources/))
- `DrawResource`, `DrawTeamResource`, `DrawTeamPlayerResource`. `DrawResource` expõe `warnings` quando o atributo runtime está presente.

### Controller
- [DrawController](../../backend/app/Http/Controllers/Api/DrawController.php) — `store` (gera), `publish` (publica), `showPublished` (consulta). Todos protegidos por `PeladaPolicy::drawTeams` ou `view`.

### Testes ([backend/tests/Unit/Domain/TeamDraw/SnakeDraftStrategyTest.php](../../backend/tests/Unit/Domain/TeamDraw/SnakeDraftStrategyTest.php))

**10 cenários** cobrindo o coração da lógica:

| # | Cenário |
|---|---|
| 1 | 10 jogadores de linha formam 2 times com delta ≤ ±2 estrelas |
| 2 | Goleiros fixos = N: 1 por time, excluídos do cálculo de rating |
| 3 | Goleiros fixos < N: distribui o que há e emite aviso de rodízio |
| 4 | Goleiros fixos > N: excedentes viram linha usando 2ª posição |
| 5 | Excedente sem 2ª posição: emite aviso e fica de fora |
| 6 | Determinismo: mesmo input produz o mesmo output |
| 7 | Balanceamento alterna picks de alta/baixa skill |
| 8 | Pool insuficiente (< 2 times viáveis): recusa e avisa |
| 9 | Pool força violação de posição: tenta swap e, se inviável, avisa |
| 10 | Jogador com primária GOL (mas não goleiro fixo): joga na 2ª posição |

Os testes estendem `\PHPUnit\Framework\TestCase` direto (não o TestCase do Laravel). Rodam em milissegundos sem bootstrap de DB.

---

## Endpoints

| Método | Rota | Autorização |
|---|---|---|
| POST | `/api/sessions/{session}/draw` | admin — `PeladaPolicy::drawTeams` |
| POST | `/api/draws/{draw}/publish` | admin — `PeladaPolicy::drawTeams` |
| GET | `/api/sessions/{session}/draw` | membro — `PeladaPolicy::view` |

---

## Decisões técnicas

1. **Estratégia atrás de uma interface** — permite substituir o algoritmo no futuro (ex.: genético, simulated annealing) sem tocar no service/controller. Atende OCP da seção 5.1 do brief.
2. **DTOs `readonly`** — garante imutabilidade de entrada/saída do algoritmo. Teste pode comparar fingerprints por `json_encode` com segurança.
3. **Tiebreak determinístico por `userId`** — quando skills empatam, a ordenação usa o userId como critério secundário. Sem isso, dois runs com o mesmo input poderiam produzir sorteios diferentes (bug sutil, observável só em produção quando as pessoas reclamam).
4. **Swap respeita TODAS as restrições pós-troca** — valida que (a) tolerância de estrelas continua ok E (b) nenhum outro time passou a violar o ratio de posição. Evita o anti-padrão "resolve aqui, quebra ali".
5. **Warnings em runtime, não no banco** — a lista `warnings` volta na resposta do POST `/draw` e se perde após publicar. É informação para o admin decidir se refaz — não faz sentido persistir, e publicar é o ato de aceitar o sorteio como está.
6. **Delete de sorteios não publicados antes de criar novo** — implementa a regra "O sorteio pode ser refeito enquanto não for publicado" sem acumular rascunhos. `ON DELETE CASCADE` nas FKs limpa `draw_teams` e `draw_team_players` automaticamente.
7. **`TotalRating` exclui goleiros** — alinhado à regra 4.2 do brief ("goleiros fixos NÃO entram no cálculo de equilíbrio de estrelas"). O frontend mostra a soma corretamente sem calcular no cliente.

---

## O que ficou para próximas ondas

- **Onda 3:** votação (com antifraude), ranking (pontuação positiva/humorística/bônus), jobs em background (abrir/fechar sessão e votação), integração FCM.
- **Push de "sorteio publicado"** (brief seção 3.7) entra na Onda 3 quando a integração FCM subir. Hoje o `DrawPublisher::publish` só persiste; vamos plugá-lo no dispatcher da Onda 3.
