import React, {useState} from 'react';
import {Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View} from 'react-native';
import {Controller, useForm} from 'react-hook-form';
import {zodResolver} from '@hookform/resolvers/zod';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {Button} from '@/components/ui/Button';
import {Input} from '@/components/ui/Input';
import {loginSchema, type LoginInput} from '@/schemas/auth';
import {authApi} from '@/services/api/auth';
import {apiErrorMessage} from '@/services/api/client';
import {useAuthStore} from '@/state/authStore';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {AuthStackParamList} from '@/navigation/types';

type Nav = NativeStackNavigationProp<AuthStackParamList, 'Login'>;

export function LoginScreen(): React.JSX.Element {
  const navigation = useNavigation<Nav>();
  const setSession = useAuthStore(s => s.setSession);
  const [submitting, setSubmitting] = useState(false);

  const {control, handleSubmit, formState: {errors}} = useForm<LoginInput>({
    resolver: zodResolver(loginSchema),
    defaultValues: {email: '', password: ''},
  });

  const onSubmit = async (values: LoginInput) => {
    setSubmitting(true);
    try {
      const {user, token} = await authApi.login({...values, device_name: Platform.OS});
      await setSession({user, token});
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Text style={styles.title}>{t.auth.loginTitle}</Text>
        <Text style={styles.subtitle}>{t.auth.loginSubtitle}</Text>

        <Controller
          control={control}
          name="email"
          render={({field: {onChange, value, onBlur}}) => (
            <Input
              label={t.auth.email}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              autoCapitalize="none"
              keyboardType="email-address"
              error={errors.email?.message}
            />
          )}
        />

        <Controller
          control={control}
          name="password"
          render={({field: {onChange, value, onBlur}}) => (
            <Input
              label={t.auth.password}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              secureTextEntry
              error={errors.password?.message}
            />
          )}
        />

        <Button label={t.auth.submit} onPress={handleSubmit(onSubmit)} loading={submitting} />

        <View style={styles.links}>
          <Button
            label={t.auth.forgotPassword}
            variant="ghost"
            onPress={() => navigation.navigate('ForgotPassword')}
          />
          <Button
            label={t.auth.dontHaveAccount}
            variant="ghost"
            onPress={() => navigation.navigate('Register')}
          />
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.lg},
  title: {...theme.typography.heading, color: theme.colors.text, marginBottom: theme.spacing.xs},
  subtitle: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
    marginBottom: theme.spacing.lg,
  },
  links: {
    marginTop: theme.spacing.md,
    gap: theme.spacing.xs,
  },
});
