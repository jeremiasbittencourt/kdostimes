import {useEffect} from 'react';
import {useAuthStore} from '@/state/authStore';
import {pushService} from '@/services/push';
import {routeFromMessage} from '@/services/push/routeFromMessage';

/**
 * Uma vez autenticado:
 *  - Pede permissão e registra o device token no backend.
 *  - Reenvia o token quando o FCM rotaciona.
 *  - Em foreground, mostra Alert nativo com o corpo da mensagem.
 *  - Em background/quit → tap, navega para a tela relevante ao evento.
 */
export function usePushRegistration(): void {
  const status = useAuthStore(s => s.status);

  useEffect(() => {
    if (status !== 'authenticated') {
      return;
    }

    const disposers: Array<() => void> = [];
    let cancelled = false;

    (async () => {
      const allowed = await pushService.requestPermission();
      if (!allowed || cancelled) {
        return;
      }
      await pushService.registerWithBackend();

      disposers.push(
        pushService.onTokenRefresh(async () => {
          await pushService.registerWithBackend();
        }),
      );

      disposers.push(
        pushService.onForegroundMessage(pushService.defaultForegroundHandler),
      );

      disposers.push(
        pushService.onNotificationOpenedApp(message => {
          routeFromMessage(message);
        }),
      );

      const initial = await pushService.getInitialNotification();
      if (initial && !cancelled) {
        // Deixa a navegação terminar de montar antes de empurrar a rota.
        setTimeout(() => routeFromMessage(initial), 300);
      }
    })();

    return () => {
      cancelled = true;
      disposers.forEach(fn => fn());
    };
  }, [status]);
}
