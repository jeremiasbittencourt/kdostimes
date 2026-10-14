# Onda 5 — Mobile: sorteio, votação, ranking e push FCM

**Status:** concluída
**Escopo:** as três telas que faltavam (Sorteio "Cadê Os Times?", Votação, Ranking) + integração completa com Firebase Cloud Messaging.

---

## Escopo detalhado

Fechar o ciclo completo do jogador no mobile:

1. **Sorteio:** admin dispara o "Cadê Os Times?"; time balanceado aparece imediatamente como rascunho (com warnings se houver); admin decide publicar ou refazer. Membros veem só o publicado.
2. **Votação:** 24h após a pelada, com categorias habilitadas; UX em cartões — cada categoria lista os jogadores confirmados como candidatos; um toque vota, UI colapsa mostrando "Seu voto: Fulano".
3. **Ranking:** lista ordenada com filtros de período (Mês / Ano / Últimas 10 / Geral), medalhas para top 3.
4. **Push:** app autenticado pede permissão (iOS + Android 13+), registra o FCM token no backend, escuta refresh e mensagens em foreground.

---

## O que foi entregue

### Tipos estendidos ([src/types/index.ts](../../mobile/src/types/index.ts))

Adicionados: `Draw`, `DrawTeam`, `DrawTeamPlayer`, `VoteCategoryType`, `VotingCategory`, `VotingSession`, `MyVote`, `RankingPeriod`, `RankingRow`, `RankingCategoryStat` — espelham os Resources do backend gerados nas Ondas 2 e 3.

### Services API ([src/services/api/](../../mobile/src/services/api/))

- [draws.ts](../../mobile/src/services/api/draws.ts) — `generate`, `getPublished` (convertendo 404 em `null`), `publish`.
- [voting.ts](../../mobile/src/services/api/voting.ts) — `forSession`, `castVote`, `results`.
- [ranking.ts](../../mobile/src/services/api/ranking.ts) — `forPelada(period)`, `forUser`.
- [devices.ts](../../mobile/src/services/api/devices.ts) — `register`, `unregister`.

### Push FCM

- [src/services/push/index.ts](../../mobile/src/services/push/index.ts) — wrapper do `@react-native-firebase/messaging`. Métodos: `requestPermission()` (iOS via APNs / Android 13+ via `POST_NOTIFICATIONS`), `registerWithBackend()` (obtém token + chama `devicesApi.register`), `onTokenRefresh()`, `onForegroundMessage()`, `defaultForegroundHandler()` (Alert nativo). Silencia falhas de infra para não derrubar o app.
- [src/hooks/usePushRegistration.ts](../../mobile/src/hooks/usePushRegistration.ts) — roda pós-autenticação: pede permissão uma vez, registra token, re-registra em refresh, mostra Alert em foreground. Dispose no unmount.
- Integrado no [App.tsx](../../mobile/App.tsx) via `AppInner` (para rodar hook dentro da árvore).

### Hooks de domínio

- [useDraw](../../mobile/src/hooks/useDraw.ts) — carrega o publicado; expõe `generate` e `publish` como actions; mantém o state do rascunho localmente até ser publicado.
- [useVotingSession](../../mobile/src/hooks/useVotingSession.ts) — carrega status + categorias habilitadas + votos do usuário.
- [useRanking](../../mobile/src/hooks/useRanking.ts) — re-busca quando o período muda (dependência do `useCallback`).

### Components

- [CadeOsTimesButton](../../mobile/src/components/ui/CadeOsTimesButton.tsx) — botão-mantra da marca, grande e verde, com shadow/elevation; usado como CTA principal do sorteio.
- [TeamCard](../../mobile/src/components/draw/TeamCard.tsx) — cartão de time com nome, total de estrelas e lista de jogadores (goleiro primeiro).
- [CategoryCard](../../mobile/src/components/voting/CategoryCard.tsx) — categoria de votação com candidatos em pills; colapsa quando já votado; estilo diferente para categorias humorísticas (laranja vs verde).
- [RankingRow](../../mobile/src/components/ranking/RankingRow.tsx) — linha do ranking com medalha emoji (🥇🥈🥉) nos top 3 e posição numérica no resto.

### Telas

| Tela | Arquivo | Comportamento |
|---|---|---|
| Sorteio | [DrawScreen](../../mobile/src/screens/draw/DrawScreen.tsx) | Admin vê CadêOsTimesButton quando não há sorteio; após gerar, times aparecem com selo "Rascunho" + botões Publicar/Refazer. Membros veem o publicado ou mensagem "aguardando". Warnings do algoritmo aparecem em Alert. |
| Votação | [VotingScreen](../../mobile/src/screens/voting/VotingScreen.tsx) | Cabeçalho com deadline; lista vertical de CategoryCards; cada card mostra candidatos (confirmados exceto o próprio usuário). Bloqueia interação quando `status=closed` ou `closes_at` já passou. |
| Ranking | [RankingScreen](../../mobile/src/screens/ranking/RankingScreen.tsx) | Pills horizontais com 4 períodos; mudança re-busca automaticamente; FlatList com pull-to-refresh. |

### Navigation

- [types.ts](../../mobile/src/navigation/types.ts) — `AppStackParamList` ganhou `Draw`, `Voting`, `Ranking`.
- [AppStack.tsx](../../mobile/src/navigation/AppStack.tsx) — 3 `<Stack.Screen>` novos.
- [linking.ts](../../mobile/src/navigation/linking.ts) — deep link patterns para as 3 novas rotas (ex.: `cadeostimes://sessions/42/draw`).

### [PeladaDetailScreen](../../mobile/src/screens/peladas/PeladaDetailScreen.tsx) atualizada

Cartão de "Próxima sessão" agora tem 3 botões em coluna: Confirmar presença, Cadê Os Times?, Votação. Fora do card, um botão separado leva ao Ranking da pelada.

### I18n

Novas seções em [pt-BR.ts](../../mobile/src/i18n/pt-BR.ts): `draw`, `voting`, `ranking` — mantendo o tom descontraído da marca.

### [mobile/README.md](../../mobile/README.md)

Adicionada seção "Firebase Cloud Messaging (push)" com passos específicos de Android (google-services.json + gradle plugin) e iOS (GoogleService-Info.plist + capabilities + pod install). Menção ao `PUSH_ENABLED=false` do backend para dev local.

---

## Decisões técnicas

### 1. Rascunho de sorteio só no state local, nunca persistido "para os outros verem"

O backend retorna o sorteio recém-gerado no POST do `/draw`, e só substitui no GET após publicação. O mobile guarda o rascunho em `useState` dentro do hook. Admin refaz quantas vezes quiser sem nada ir para o banco nem push ser disparado. Alinha com a regra do brief ("pode ser refeito enquanto não for publicado") sem adicionar complexidade.

### 2. Warnings do algoritmo em `Alert`, não em UI persistente

Sorteios com avisos ("time sem goleiro fixo", "pool forçou violação") são raros. Fazer um painel dedicado na UI seria over-engineering. Um `Alert.alert` ao receber o draw resolve — admin lê, toma decisão de publicar ou refazer. Warnings não persistem após publicação (backend também não persiste).

### 3. UX de votação otimizada para **poucas categorias habilitadas**

Peladas habilitam tipicamente 2–5 categorias. Por isso a lista é vertical + pills de candidato, não um wizard com uma categoria por tela. Um scroll, vota em tudo, fechou. Se a pelada habilitar todas as 8 categorias, ainda cabe confortavelmente (rolagem normal).

### 4. Cores diferentes para categoria humorística

Card de "Pior da Pelada" / "Pernas de Pau" tem borda lateral laranja (`theme.colors.warning`) + tag "humor" em fundo creme. Deixa claro visualmente que votar ali **tira ponto** (não é um badge de honra).

### 5. Candidates da votação = confirmados exceto eu

`VotingScreen` usa `useCurrentSession` para obter presenças confirmadas. Limitação conhecida: se uma sessão futura já foi aberta enquanto a votação da anterior está em andamento, `useCurrentSession` retorna a nova e a lista de candidatos pode divergir. **Mitigação:** o backend rejeita votos em jogadores que não confirmaram presença na sessão correta, com mensagem amigável que aparece via `apiErrorMessage`. Para uma v2, expor `GET /sessions/{id}` ou incluir `presences` no voting response resolve de vez.

### 6. Medalhas emoji para top 3

Barato (zero assets), universal, funciona em qualquer device. Se amanhã virarem ícones SVG da marca, troca só no `RankingRow`.

### 7. Período default do ranking = "Mês"

Ranking "Geral" rapidamente fica repetitivo (mesmo craque mês após mês). "Mês" mantém a tela viva — novos destaques aparecem com frequência. Usuário troca pra Geral/Ano quando quer ver histórico.

### 8. Push: best-effort, nunca bloqueia

Todas as funções do `pushService` engolem erros. Se o dispositivo não tem Google Play Services, se a permissão foi negada, se o token expirou — nada disso derruba o app nem impede o usuário de usar. O usuário simplesmente não recebe push. Logs ficam para debug.

### 9. Sem deep link de push tappado (ainda)

Quando o usuário toca num push (`notification_opened_app`), o RN Firebase pode passar dados para navegar. Não implementei agora — precisaria fiar os payloads do backend com os `screen: 'Voting'` etc., testar os 3 estados (foreground, background, quit) e cobrir iOS vs Android. Vai para a Onda 6 (polimento) ou depois. Por ora: o push chega, o usuário abre o app manualmente, vê a pelada na Home.

---

## Novos endpoints consumidos (todos criados nas Ondas 2 e 3)

```
POST   /api/sessions/{id}/draw
GET    /api/sessions/{id}/draw
POST   /api/draws/{id}/publish

GET    /api/sessions/{id}/voting
POST   /api/voting-sessions/{id}/votes
GET    /api/voting-sessions/{id}/results

GET    /api/peladas/{id}/ranking?period=month|year|all|last10
GET    /api/peladas/{id}/ranking/{userId}

POST   /api/me/device-tokens
DELETE /api/me/device-tokens/{id}
```

---

## O que fica para a Onda 6 (última)

- **Coleção Postman/Insomnia** cobrindo TODA a API (auth, perfil, pelada, presença, sorteio, votação, ranking, devices). Vai em [docs/api-collection.json](../api-collection.json).
- **Feature tests** das rotas críticas do backend (deixados pro final porque a superfície HTTP está estável agora).
- **Deep link de tap em push** (nice-to-have listado acima).
- **Polimento de READMEs** com screenshots/gifs se fizer sentido.
