import type {FirebaseMessagingTypes} from '@react-native-firebase/messaging';
import {navigateFromPush} from '@/navigation/navigationRef';
import type {AppStackParamList} from '@/navigation/types';
import type {NotificationEvent} from '@/types';

type PushPayload = Partial<Record<string, string>>;

/**
 * Traduz `message.data` (todos os campos são strings no FCM) para uma navegação.
 * Retorna `true` se navegou, `false` caso contrário (não rota conhecida ou dados faltando).
 */
export function routeFromMessage(
  message: FirebaseMessagingTypes.RemoteMessage | null | undefined,
): boolean {
  if (!message?.data) {
    return false;
  }
  const data = message.data as PushPayload;
  const event = data.event as NotificationEvent | undefined;
  if (!event) {
    return false;
  }

  const target = screenForEvent(event, data);
  if (!target) {
    return false;
  }

  navigateFromPush(target.screen, target.params);
  return true;
}

type RouteTarget =
  | {screen: 'Presence'; params: AppStackParamList['Presence']}
  | {screen: 'Draw'; params: AppStackParamList['Draw']}
  | {screen: 'Voting'; params: AppStackParamList['Voting']}
  | {screen: 'Ranking'; params: AppStackParamList['Ranking']}
  | {screen: 'PeladaDetail'; params: AppStackParamList['PeladaDetail']}
  | {screen: 'Home'; params: undefined};

function screenForEvent(event: NotificationEvent, data: PushPayload): RouteTarget | null {
  const peladaSessionId = Number(data.pelada_session_id);
  const peladaId = Number(data.pelada_id);

  switch (event) {
    case 'session_opened':
    case 'presence_deadline_reminder':
      if (peladaSessionId && peladaId) {
        return {screen: 'Presence', params: {sessionId: peladaSessionId, peladaId}};
      }
      return null;

    case 'draw_published':
      if (peladaSessionId && peladaId) {
        return {screen: 'Draw', params: {sessionId: peladaSessionId, peladaId}};
      }
      return null;

    case 'voting_opened':
    case 'voting_deadline_reminder':
      if (peladaSessionId && peladaId) {
        return {screen: 'Voting', params: {sessionId: peladaSessionId, peladaId}};
      }
      return null;

    case 'voting_closed':
      if (peladaId) {
        return {screen: 'Ranking', params: {peladaId}};
      }
      return null;

    case 'member_removed':
      return {screen: 'Home', params: undefined};

    default:
      return null;
  }
}
