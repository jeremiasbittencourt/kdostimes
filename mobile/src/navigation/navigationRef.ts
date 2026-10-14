import {createNavigationContainerRef} from '@react-navigation/native';
import type {AppStackParamList} from './types';

/**
 * Ref externa ao NavigationContainer para disparar navegação fora da árvore React
 * (ex.: quando o usuário toca em um push com o app em background).
 */
export const navigationRef = createNavigationContainerRef<AppStackParamList>();

export function navigateFromPush<K extends keyof AppStackParamList>(
  screen: K,
  params: AppStackParamList[K],
): void {
  if (!navigationRef.isReady()) {
    return;
  }
  navigationRef.navigate(screen as never, params as never);
}
