import React, {useState} from 'react';
import {Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text} from 'react-native';
import {Controller, useForm} from 'react-hook-form';
import {zodResolver} from '@hookform/resolvers/zod';
import {Button} from '@/components/ui/Button';
import {Input} from '@/components/ui/Input';
import {forgotPasswordSchema, type ForgotPasswordInput} from '@/schemas/auth';
import {authApi} from '@/services/api/auth';
import {apiErrorMessage} from '@/services/api/client';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';

export function ForgotPasswordScreen(): React.JSX.Element {
  const [submitting, setSubmitting] = useState(false);

  const {control, handleSubmit, formState: {errors}} = useForm<ForgotPasswordInput>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: {email: ''},
  });

  const onSubmit = async (values: ForgotPasswordInput) => {
    setSubmitting(true);
    try {
      await authApi.forgotPassword(values.email);
      Alert.alert('Cadê Os Times?', t.auth.forgotSuccess);
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
        <Text style={styles.title}>Recuperar senha</Text>

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

        <Button label={t.auth.submitForgot} onPress={handleSubmit(onSubmit)} loading={submitting} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.lg},
  title: {...theme.typography.heading, color: theme.colors.text, marginBottom: theme.spacing.md},
});
