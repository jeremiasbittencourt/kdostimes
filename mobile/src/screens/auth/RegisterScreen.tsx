import React, {useState} from 'react';
import {Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text} from 'react-native';
import {Controller, useForm} from 'react-hook-form';
import {zodResolver} from '@hookform/resolvers/zod';
import {Button} from '@/components/ui/Button';
import {Input} from '@/components/ui/Input';
import {registerSchema, type RegisterInput} from '@/schemas/auth';
import {authApi} from '@/services/api/auth';
import {apiErrorMessage} from '@/services/api/client';
import {useAuthStore} from '@/state/authStore';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';

export function RegisterScreen(): React.JSX.Element {
  const setSession = useAuthStore(s => s.setSession);
  const [submitting, setSubmitting] = useState(false);

  const {control, handleSubmit, formState: {errors}} = useForm<RegisterInput>({
    resolver: zodResolver(registerSchema),
    defaultValues: {name: '', email: '', password: '', password_confirmation: ''},
  });

  const onSubmit = async (values: RegisterInput) => {
    setSubmitting(true);
    try {
      const {user, token} = await authApi.register(values);
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
        <Text style={styles.title}>Criar conta</Text>

        <Controller
          control={control}
          name="name"
          render={({field: {onChange, value, onBlur}}) => (
            <Input
              label={t.auth.name}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.name?.message}
            />
          )}
        />
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
        <Controller
          control={control}
          name="password_confirmation"
          render={({field: {onChange, value, onBlur}}) => (
            <Input
              label={t.auth.passwordConfirm}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              secureTextEntry
              error={errors.password_confirmation?.message}
            />
          )}
        />

        <Button label={t.auth.submitRegister} onPress={handleSubmit(onSubmit)} loading={submitting} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.lg},
  title: {...theme.typography.heading, color: theme.colors.text, marginBottom: theme.spacing.md},
});
