import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {LoginScreen} from '@/screens/auth/LoginScreen';
import {RegisterScreen} from '@/screens/auth/RegisterScreen';
import {ForgotPasswordScreen} from '@/screens/auth/ForgotPasswordScreen';
import {theme} from '@/theme';
import type {AuthStackParamList} from './types';

const Stack = createNativeStackNavigator<AuthStackParamList>();

export function AuthStack(): React.JSX.Element {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: {backgroundColor: theme.colors.primary},
        headerTintColor: '#fff',
        headerTitleStyle: {fontWeight: '700'},
      }}>
      <Stack.Screen name="Login" component={LoginScreen} options={{title: 'Cadê Os Times?'}} />
      <Stack.Screen name="Register" component={RegisterScreen} options={{title: 'Criar conta'}} />
      <Stack.Screen
        name="ForgotPassword"
        component={ForgotPasswordScreen}
        options={{title: 'Recuperar senha'}}
      />
    </Stack.Navigator>
  );
}
