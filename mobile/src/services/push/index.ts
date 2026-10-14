import {Platform, PermissionsAndroid, Alert} from 'react-native';
import messaging, {
  type FirebaseMessagingTypes,
} from '@react-native-firebase/messaging';
import {devicesApi} from '@/services/api/devices';

/**
 * Wrapper do FCM. Responsabilidades:
 *  - Pedir permissão (iOS via APNs, Android 13+ via POST_NOTIFICATIONS).
 *  - Obter o token do dispositivo.
 *  - Registrar no backend via POST /me/device-tokens.
 *  - Escutar refresh do token (reenvia pro backend).
 *  - Escutar mensagens em foreground (mostra Alert simples — UX rica fica pra iterações).
 *
 * Se o dispositivo não tem Google Play Services (raro, mas existe), messaging() lança.
 * Tratamos erros localmente para não derrubar o app — push é best-effort.
 */
export const pushService = {
  async requestPermission(): Promise<boolean> {
    try {
      if (Platform.OS === 'ios') {
        const status = await messaging().requestPermission();
        return (
          status === messaging.AuthorizationStatus.AUTHORIZED ||
          status === messaging.AuthorizationStatus.PROVISIONAL
        );
      }
      if (Platform.OS === 'android' && Platform.Version >= 33) {
        const result = await PermissionsAndroid.request(
          PermissionsAndroid.PERMISSIONS.POST_NOTIFICATIONS,
        );
        return result === PermissionsAndroid.RESULTS.GRANTED;
      }
      return true;
    } catch {
      return false;
    }
  },

  async registerWithBackend(): Promise<number | null> {
    try {
      const token = await messaging().getToken();
      if (!token) {
        return null;
      }
      const device = await devicesApi.register(
        token,
        Platform.OS === 'ios' ? 'ios' : 'android',
      );
      return device.id;
    } catch {
      return null;
    }
  },

  onTokenRefresh(cb: (token: string) => void): () => void {
    return messaging().onTokenRefresh(cb);
  },

  onForegroundMessage(
    handler: (msg: FirebaseMessagingTypes.RemoteMessage) => void,
  ): () => void {
    return messaging().onMessage(async msg => {
      handler(msg);
    });
  },

  defaultForegroundHandler(msg: FirebaseMessagingTypes.RemoteMessage): void {
    const title = msg.notification?.title ?? 'Cadê Os Times?';
    const body = msg.notification?.body ?? '';
    if (body) {
      Alert.alert(title, body);
    }
  },

  /**
   * Dispara quando o usuário toca em um push com o app em background.
   */
  onNotificationOpenedApp(
    handler: (msg: FirebaseMessagingTypes.RemoteMessage) => void,
  ): () => void {
    return messaging().onNotificationOpenedApp(handler);
  },

  /**
   * Se o app foi iniciado pelo tap em um push (estava encerrado), retorna essa mensagem.
   */
  getInitialNotification(): Promise<FirebaseMessagingTypes.RemoteMessage | null> {
    return messaging().getInitialNotification();
  },
};
