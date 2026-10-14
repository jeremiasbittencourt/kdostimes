import React, {useEffect, useState} from 'react';
import {Alert, ScrollView, StyleSheet, Text} from 'react-native';
import {Controller, useForm} from 'react-hook-form';
import {zodResolver} from '@hookform/resolvers/zod';
import {useNavigation, useRoute, type RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {Button} from '@/components/ui/Button';
import {Input} from '@/components/ui/Input';
import {joinPeladaSchema, type JoinPeladaInput} from '@/schemas/pelada';
import {peladasApi} from '@/services/api/peladas';
import {apiErrorMessage} from '@/services/api/client';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {AppStackParamList} from '@/navigation/types';

type Nav = NativeStackNavigationProp<AppStackParamList, 'JoinPelada'>;
type Route = RouteProp<AppStackParamList, 'JoinPelada'>;

export function JoinPeladaScreen(): React.JSX.Element {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const [submitting, setSubmitting] = useState(false);

  const inviteToken = route.params?.inviteToken;

  const {control, handleSubmit, setValue, formState: {errors}} = useForm<JoinPeladaInput>({
    resolver: zodResolver(joinPeladaSchema),
    defaultValues: {short_code: '', invite_token: inviteToken ?? ''},
  });

  useEffect(() => {
    if (inviteToken) {
      setValue('invite_token', inviteToken);
    }
  }, [inviteToken, setValue]);

  const onSubmit = async (values: JoinPeladaInput) => {
    setSubmitting(true);
    try {
      const pelada = await peladasApi.join({
        short_code: values.short_code || undefined,
        invite_token: values.invite_token || undefined,
      });
      navigation.replace('PeladaDetail', {peladaId: pelada.id});
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.scroll}>
      <Text style={styles.title}>{t.peladas.joinByCode}</Text>
      <Controller
        control={control}
        name="short_code"
        render={({field}) => (
          <Input
            label="Código"
            value={field.value ?? ''}
            onChangeText={field.onChange}
            autoCapitalize="characters"
            placeholder="PEL-XXXXX"
            error={errors.short_code?.message}
          />
        )}
      />

      <Text style={styles.or}>— OU —</Text>

      <Text style={styles.title}>{t.peladas.joinByLink}</Text>
      <Controller
        control={control}
        name="invite_token"
        render={({field}) => (
          <Input
            label="Token de convite"
            value={field.value ?? ''}
            onChangeText={field.onChange}
            autoCapitalize="none"
            error={errors.invite_token?.message}
          />
        )}
      />

      <Button label={t.peladas.joinSubmit} onPress={handleSubmit(onSubmit)} loading={submitting} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.lg},
  title: {...theme.typography.title, color: theme.colors.text, marginBottom: theme.spacing.sm},
  or: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
    textAlign: 'center',
    marginVertical: theme.spacing.md,
  },
});
