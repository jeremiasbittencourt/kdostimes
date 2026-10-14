import React from 'react';
import {NavigationContainer} from '@react-navigation/native';
import {AuthStack} from './AuthStack';
import {AppStack} from './AppStack';
import {SplashScreen} from '@/screens/SplashScreen';
import {useAuthStore} from '@/state/authStore';
import {linking} from './linking';
import {navigationRef} from './navigationRef';

export function RootNavigator(): React.JSX.Element {
  const status = useAuthStore(s => s.status);

  if (status === 'idle' || status === 'hydrating') {
    return <SplashScreen />;
  }

  return (
    <NavigationContainer ref={navigationRef} linking={linking} fallback={<SplashScreen />}>
      {status === 'authenticated' ? <AppStack /> : <AuthStack />}
    </NavigationContainer>
  );
}
