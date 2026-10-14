# Onda 4 — Mobile: navegação, auth, peladas e presença

**Status:** concluída
**Escopo:** scaffold do app React Native (CLI puro) + TypeScript, navegação, fluxo de autenticação, CRUD de peladas, confirmação de presença e perfil.

---

## Escopo detalhado

Levantar o app funcional end-to-end para o fluxo básico de um jogador:

1. **Entrar:** login, cadastro, recuperação de senha — token Sanctum guardado no Keychain iOS / Keystore Android.
2. **Ver minhas peladas:** lista com pull-to-refresh.
3. **Criar pelada** (como admin) ou **entrar em uma** (via código curto ou deep link).
4. **Ver detalhe da pelada:** membros, próxima sessão, gerar convite (admin), marcar goleiro fixo (admin).
5. **Confirmar presença:** "Vou" ou "Não vou", com lista bloqueada após o deadline.
6. **Editar perfil:** nome, nível (1-10), até 2 posições, preferências de notificação, logout.

As telas de sorteio "Cadê Os Times?", votação, ranking e registro FCM ficam para a Onda 5.

---

## O que foi entregue

### Bootstrap ([mobile/](../../mobile/))

- [package.json](../../mobile/package.json) — RN 0.76, React 18.3, TS 5.5, react-navigation v7, react-hook-form, Zod, Zustand, axios, react-native-keychain, @hookform/resolvers.
- [tsconfig.json](../../mobile/tsconfig.json) — `strict` + `noUncheckedIndexedAccess` + alias `@/*` → `src/*`.
- [babel.config.js](../../mobile/babel.config.js) + [metro.config.js](../../mobile/metro.config.js) — preset do RN + module-resolver para o alias.
- [.eslintrc.js](../../mobile/.eslintrc.js) + [.prettierrc.js](../../mobile/.prettierrc.js).
- [index.js](../../mobile/index.js) + [app.json](../../mobile/app.json) + [App.tsx](../../mobile/App.tsx) — entry, registro no AppRegistry, SafeAreaProvider + GestureHandlerRootView + StatusBar temática + hydrate do authStore no mount.
- [README.md](../../mobile/README.md) — passos de instalação (skeleton via `@react-native-community/cli init` + overlay), endpoint da API por plataforma, exemplo de teste de deep link com `adb shell`, decisões técnicas.

### Theme + i18n ([src/theme/](../../mobile/src/theme/), [src/i18n/](../../mobile/src/i18n/))

- `theme/index.ts` — paleta verde de grama (primary `#1B7F3A`), amarelo accent, espaçamentos e tipografia. Tudo `as const` para inferência TS.
- `i18n/pt-BR.ts` — strings em pt-BR com o tom da marca: *"Bora pra pelada"*, *"Cadê você?"*, *"Cadê Os Times? Já saiu!"*. Inclui labels das 5 posições e dos 7 eventos de notificação.

### Tipos ([src/types/index.ts](../../mobile/src/types/index.ts))

DTOs espelhando os Resources do backend (User, Pelada, PeladaMember, PeladaSession, PresenceConfirmation, etc.) + enums (Position, MemberRole, PresenceStatus, SessionStatus, NotificationEvent).

### Services

- [api/client.ts](../../mobile/src/services/api/client.ts) — instância axios com timeout, URL base por plataforma (`10.0.2.2` no Android emulado, `localhost` no iOS), interceptor que injeta `Authorization: Bearer ...` de requisição E handler de 401 que chama o callback global de logout. Helper `apiErrorMessage` extrai mensagens amigáveis das API Responses do Laravel.
- [api/auth.ts](../../mobile/src/services/api/auth.ts) — `register`, `login`, `logout`, `forgotPassword`, `me`, `updateMe`, `updateNotificationPreferences`.
- [api/peladas.ts](../../mobile/src/services/api/peladas.ts) — `list`, `show`, `create`, `join`, `updateMember`, `removeMember`, `createInvite`.
- [api/sessions.ts](../../mobile/src/services/api/sessions.ts) — `current` (pelada), `confirmPresence`.
- [storage/secureToken.ts](../../mobile/src/services/storage/secureToken.ts) — wrapper 3-métodos em `react-native-keychain` (save/load/clear).

### Schemas Zod ([src/schemas/](../../mobile/src/schemas/))

Um por domínio de form, casando 1:1 com as Form Requests do backend:
- [auth.ts](../../mobile/src/schemas/auth.ts) — login, register (com refine de senhas conferindo), forgotPassword.
- [pelada.ts](../../mobile/src/schemas/pelada.ts) — createPelada (validações de `HH:mm` regex, dia 0-6, 3-11 por time, 1-24h de deadline), joinPelada (refine exige código OU token).
- [profile.ts](../../mobile/src/schemas/profile.ts) — updateProfile com refines para não repetir prioridade ou posição.

### State ([src/state/authStore.ts](../../mobile/src/state/authStore.ts))

Zustand com 4 estados (`idle` / `hydrating` / `authenticated` / `unauthenticated`) e 4 actions (`hydrate`, `setSession`, `clearSession`, `refreshUser`). No próprio `create`, registra o callback de 401 do axios client — quando o token expira, o usuário é deslogado automaticamente. **Nenhum Provider necessário**; qualquer tela chama `useAuthStore(s => s.algumaCoisa)`.

### Hooks ([src/hooks/](../../mobile/src/hooks/))

- `usePeladas` — lista + reload com loading/error.
- `useCurrentSession(peladaId)` — próxima sessão em aberto da pelada.

Ambos seguem o mesmo pattern: local state, `load()` com `try/catch` usando `apiErrorMessage`.

### Navigation ([src/navigation/](../../mobile/src/navigation/))

- [types.ts](../../mobile/src/navigation/types.ts) — `AuthStackParamList` e `AppStackParamList` (rotas TS-safe).
- [linking.ts](../../mobile/src/navigation/linking.ts) — deep link `cadeostimes://peladas/join?token=<x>` (também `https://cadeostimes.app/...` para universal links).
- [AuthStack.tsx](../../mobile/src/navigation/AuthStack.tsx), [AppStack.tsx](../../mobile/src/navigation/AppStack.tsx) — native-stack configurado com header verde da marca.
- [RootNavigator.tsx](../../mobile/src/navigation/RootNavigator.tsx) — roteia entre AuthStack vs AppStack baseado em `authStore.status`, mostra SplashScreen durante hidratação.

### Componentes

UI ([src/components/ui/](../../mobile/src/components/ui/)):
- [Button](../../mobile/src/components/ui/Button.tsx) — 4 variantes (primary/secondary/ghost/danger), loading e disabled.
- [Input](../../mobile/src/components/ui/Input.tsx) — forwarded ref, label em caps, erro inline, uso com Controller do RHF.
- [Card](../../mobile/src/components/ui/Card.tsx) — container padronizado com border e padding.
- [StarRating](../../mobile/src/components/ui/StarRating.tsx) — 1-10 estrelas tocáveis (read-only opcional).

Pelada ([src/components/pelada/](../../mobile/src/components/pelada/)):
- [PeladaCard](../../mobile/src/components/pelada/PeladaCard.tsx) — card clicável na lista.
- [MemberRow](../../mobile/src/components/pelada/MemberRow.tsx) — linha com switch de goleiro fixo quando admin + pelada usa goleiros fixos.
- [PresenceToggle](../../mobile/src/components/pelada/PresenceToggle.tsx) — duplinha de botões Vou/Não vou.

### Telas (12)

| Tela | Arquivo | Uso |
|---|---|---|
| Splash | [SplashScreen](../../mobile/src/screens/SplashScreen.tsx) | Enquanto hydrate do token roda |
| Login | [LoginScreen](../../mobile/src/screens/auth/LoginScreen.tsx) | E-mail + senha; chamadas ao `authStore.setSession` |
| Register | [RegisterScreen](../../mobile/src/screens/auth/RegisterScreen.tsx) | Nome + e-mail + senha + confirmação |
| ForgotPassword | [ForgotPasswordScreen](../../mobile/src/screens/auth/ForgotPasswordScreen.tsx) | Dispara e-mail de reset |
| Home | [HomeScreen](../../mobile/src/screens/peladas/HomeScreen.tsx) | Lista com pull-to-refresh + atalhos Criar/Entrar/Perfil |
| CreatePelada | [CreatePeladaScreen](../../mobile/src/screens/peladas/CreatePeladaScreen.tsx) | Form completo com seletor de dia em pills |
| JoinPelada | [JoinPeladaScreen](../../mobile/src/screens/peladas/JoinPeladaScreen.tsx) | Código curto OU token de convite (auto-preenchido via deep link) |
| PeladaDetail | [PeladaDetailScreen](../../mobile/src/screens/peladas/PeladaDetailScreen.tsx) | Info da pelada, próxima sessão, membros, gerar convite |
| Presence | [PresenceScreen](../../mobile/src/screens/peladas/PresenceScreen.tsx) | Vou/Não vou, lista de confirmados, lock após deadline |
| Profile | [ProfileScreen](../../mobile/src/screens/profile/ProfileScreen.tsx) | Nome, skill (★ 1-10), até 2 posições, preferências de push, logout |

---

## Decisões técnicas

### 1. Zustand como único store global

Decisão explicada em [onda-1.md](onda-1.md) e no [README raiz](../../README.md). Aqui vale o lembrete prático: **só estão no store `status`, `user` e `token`**. Lista de peladas não entra (é do hook `usePeladas` local da Home). Isso evita que o store vire dump e mantém a regra "se vive em uma tela só, é `useState`".

### 2. 401 → logout sem try/catch espalhado

O axios client expõe `setUnauthorizedHandler(fn)` — o `authStore` registra esse handler no próprio `create()`. Quando qualquer request recebe 401, o handler limpa o Keychain e zera o estado; o `RootNavigator` automaticamente passa a renderizar o AuthStack. Nenhuma tela precisa tratar 401 manualmente.

### 3. React Hook Form com resolver Zod

Schemas Zod ficam em `src/schemas/`. Nos forms, o padrão é `useForm<T>({resolver: zodResolver(schema), defaultValues: ...})` e `<Controller name="..." render={({field}) => <Input ... />} />`. Permite reutilizar o tipo inferido (`LoginInput = z.infer<...>`) como payload do serviço — zero duplicação.

### 4. Mensagens amigáveis do Laravel sem boilerplate

`apiErrorMessage(err)` colapsa a estrutura do Laravel (`{message, errors: {field: [messages]}}`) em uma string. Nas telas, `Alert.alert('Ops', apiErrorMessage(err))` é o padrão. Sem parser em cada tela.

### 5. Deep link pré-preenche a tela de Join

`JoinPeladaScreen` aceita `route.params.inviteToken` — que vem via `linking` quando o usuário clica em `cadeostimes://peladas/join?token=<x>`. O `useEffect` seta o valor do form. Se o usuário abriu pelo menu em vez do link, o campo começa vazio.

### 6. Goleiro fixo só aparece quando a pelada usa

No `MemberRow`, o switch de "goleiro fixo" só renderiza se `canManage && pelada.has_fixed_goalkeepers`. O backend também rejeita com 403 se tentar modificar sem permissão — é proteção em 2 camadas, não só UI.

### 7. `noUncheckedIndexedAccess` ligado

Qualquer `array[i]` é tratado como `T | undefined`. Força tratamento explícito (ex.: `t.days[dow] ?? 'Dia'`) em vez de crashar em runtime. Vale os poucos `?? ''` espalhados.

---

## O que fica para próximas ondas

- **Onda 5:** sorteio "Cadê Os Times?", votação, ranking (com filtros de período), registro de device token FCM, recebimento de push, reação aos deep links de notificação (ex.: tap no push abre a tela de votação).
- **Onda 6:** coleção Postman + polimento final.

### Pontos intencionalmente fora desta onda

- **Registro de device token / pedido de permissão de push.** Acoplado à integração `@react-native-firebase/messaging`; entra na Onda 5 junto com o recebimento de pushes em background/foreground, para não deixar cabo solto.
- **Telas de sorteio, votação e ranking.** Dependem dos endpoints que já existem (Ondas 2 e 3); ficam para a Onda 5 como bloco coeso.
- **Testes de componente.** Jest + RNTL viáveis, mas o escopo da Onda 4 era levantar o app funcional. Testes de UI entram em iterações de manutenção quando a superfície se estabilizar.
