import React, {useEffect} from 'react';
import {StatusBar} from 'react-native';
import {SafeAreaProvider} from 'react-native-safe-area-context';
import {GestureHandlerRootView} from 'react-native-gesture-handler';
import {RootNavigator} from '@/navigation/RootNavigator';
import {useAuthStore} from '@/state/authStore';
import {usePushRegistration} from '@/hooks/usePushRegistration';
import {theme} from '@/theme';

function AppInner(): React.JSX.Element {
  usePushRegistration();
  return <RootNavigator />;
}

export default function App(): React.JSX.Element {
  const hydrate = useAuthStore(s => s.hydrate);

  useEffect(() => {
    hydrate();
  }, [hydrate]);

  return (
    <GestureHandlerRootView style={{flex: 1}}>
      <SafeAreaProvider>
        <StatusBar barStyle="light-content" backgroundColor={theme.colors.primary} />
        <AppInner />
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
