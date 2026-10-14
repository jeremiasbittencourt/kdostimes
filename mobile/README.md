# Mobile — Cadê Os Times?

App React Native (CLI puro) com TypeScript. Comunica com a API Laravel via Sanctum + axios. Push via FCM (entra na Onda 5).

---

## Pré-requisitos

- Node.js 18+
- **Android:** JDK 17, Android Studio, SDK 34+
- **iOS:** Xcode 15+, CocoaPods
- Uma instância local da API rodando (ver [backend/README.md](../backend/README.md))

---

## Instalação

> Assim como no backend, **apenas os arquivos customizados** estão versionados. O skeleton do React Native (pastas `android/` e `ios/`, `Gemfile`, `Podfile`, gradle, etc.) é gerado pelo CLI oficial.

```bash
# 1. Gere o skeleton do RN em um temporário e mova para este diretório preservando nossos arquivos
cd mobile
npx @react-native-community/cli@latest init CadeOsTimes --version 0.76.0 --skip-install --directory ../_rn-skel
cp -rn ../_rn-skel/. .
rm -rf ../_rn-skel

# 2. Instale as dependências (nosso package.json já inclui tudo)
npm install

# 3. iOS: instale os pods
cd ios && pod install && cd ..

# 4. Configure o endpoint da API copiando o exemplo
cp .env.example .env
# edite API_BASE_URL em .env (ex.: http://10.0.2.2:8000/api no emulador Android; http://localhost:8000/api no iOS)

# 5. Subir
npm start           # Metro bundler
npm run android     # ou npm run ios
```

---

## Endpoint da API

A URL base vem de [src/services/api/client.ts](src/services/api/client.ts), que lê de uma constante. Para dev local:

| Ambiente | URL |
|---|---|
| Emulador Android (AVD) | `http://10.0.2.2:8000/api` |
| Simulador iOS | `http://localhost:8000/api` |
| Device físico | IP da máquina de dev (ex.: `http://192.168.1.10:8000/api`) |

Em produção, ponta para seu domínio com HTTPS.

---

## Estrutura `src/`

```
src/
├── theme/            # cores, tipografia, espaçamento
├── i18n/             # strings em pt-BR (tom da marca: "craque", "muralha", "Cadê Os Times?")
├── types/            # DTOs espelhando as API Resources do backend
├── services/
│   ├── api/          # axios client + endpoints por domínio
│   └── storage/      # wrapper seguro de token (react-native-keychain)
├── schemas/          # Zod schemas usados pelo React Hook Form
├── state/            # Zustand stores (auth, peladas)
├── hooks/            # useAuth, useCurrentSession, usePeladas
├── navigation/       # RootNavigator + AuthStack + AppStack + linking (deep link de convite)
├── screens/
│   ├── auth/         # Login, Register, ForgotPassword
│   ├── peladas/      # Home, Create, Join, Detail, Presence
│   └── profile/      # Profile
└── components/
    ├── ui/           # Button, Input, StarRating, Avatar
    └── pelada/       # PeladaCard, MemberRow, PresenceToggle
```

---

## Firebase Cloud Messaging (push)

A integração usa `@react-native-firebase/app` + `@react-native-firebase/messaging`. Entra em ação quando o usuário está autenticado: [usePushRegistration](src/hooks/usePushRegistration.ts) pede permissão, pega o token FCM e registra via `POST /api/me/device-tokens`.

Para funcionar em dispositivos reais:

1. **Criar projeto no Firebase** (se ainda não existe, o mesmo do backend).
2. **Android:** baixe `google-services.json` e coloque em `android/app/`.
   - Em `android/build.gradle`: `classpath 'com.google.gms:google-services:4.4.2'`.
   - Em `android/app/build.gradle`: `apply plugin: 'com.google.gms.google-services'`.
3. **iOS:** baixe `GoogleService-Info.plist` e coloque em `ios/CadeOsTimes/`. Abra o `.xcworkspace` e marque `Push Notifications` + `Background Modes → Remote notifications` nas capabilities.
   - `cd ios && pod install`.
4. Rode o app. Na primeira execução pós-login, o SO pede permissão (iOS sempre; Android só a partir do 13).

No dev, você pode manter o FCM desligado no backend com `PUSH_ENABLED=false` — os jobs continuam rodando e logando o que seria enviado.

---

## Deep link de convite

Esquema `cadeostimes://peladas/join?token=<invite_token>`. Abre direto a tela de Join com o token pré-preenchido.

Para testar no Android emulado:

```bash
adb shell am start -W -a android.intent.action.VIEW -d "cadeostimes://peladas/join?token=abc123"
```

---

## Decisões técnicas

### Zustand, não Context API ou Redux

Escolhido para estado **global** (token de auth, usuário logado). Nos detalhes espalhados pela UI uso `useState` local.

- **Context API** re-renderiza toda árvore consumidora a cada mudança — um custo silencioso que leva o time a encher o código de `useMemo`/`useCallback` defensivos.
- **Redux** é over-engineering para o escopo: boilerplate (actions/reducers/middlewares) e store que vira despejo de tudo.
- **Zustand** tem API minúscula (`create`/`useStore`), seletores naturalmente evitam re-render, zero Provider.
- Regra aqui: **se o dado vive em uma só tela, é `useState`**. Só entra no store o que cruza fronteiras de tela. O store não é despensa.

### React Native CLI puro, não Expo

- FCM real com `@react-native-firebase/messaging` funciona melhor fora do Expo Go.
- `react-native-keychain` (Keychain iOS real) > `expo-secure-store` para um app que guarda token Sanctum.
- Deep link com esquema custom (`cadeostimes://`) funciona idêntico mas sem as camadas do Expo.

### React Hook Form + Zod

- Validação declarativa no TS que casa 1:1 com as Form Requests do backend.
- Re-render mínimo no form (HF não re-renderiza o form inteiro a cada tecla).
- Mesmos schemas podem ser exportados para outros lugares (ex.: tipar payloads das chamadas de API).

### Axios com interceptor de auth + 401 → logout

Interceptor adiciona `Authorization: Bearer ...` em toda requisição. Se o backend responder 401, limpamos o token e navegamos para login. Sem try/catch espalhado.

---

## Scripts

| Comando | O que faz |
|---|---|
| `npm start` | Sobe o Metro bundler |
| `npm run android` | Build + instalação no emulador/device Android |
| `npm run ios` | Build + execução no simulador iOS |
| `npm run lint` | ESLint |
| `npm run typecheck` | `tsc --noEmit` (não gera JS, só checa tipos) |
| `npm test` | Jest |
