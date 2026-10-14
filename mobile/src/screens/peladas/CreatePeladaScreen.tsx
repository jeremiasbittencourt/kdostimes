import React, {useState} from 'react';
import {Alert, Pressable, ScrollView, StyleSheet, Switch, Text, View} from 'react-native';
import {Controller, useForm} from 'react-hook-form';
import {zodResolver} from '@hookform/resolvers/zod';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {Button} from '@/components/ui/Button';
import {Input} from '@/components/ui/Input';
import {createPeladaSchema, type CreatePeladaInput} from '@/schemas/pelada';
import {peladasApi} from '@/services/api/peladas';
import {apiErrorMessage} from '@/services/api/client';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {AppStackParamList} from '@/navigation/types';

type Nav = NativeStackNavigationProp<AppStackParamList, 'CreatePelada'>;

export function CreatePeladaScreen(): React.JSX.Element {
  const navigation = useNavigation<Nav>();
  const [submitting, setSubmitting] = useState(false);

  const {control, handleSubmit, setValue, watch, formState: {errors}} = useForm<CreatePeladaInput>({
    resolver: zodResolver(createPeladaSchema),
    defaultValues: {
      name: '',
      location: '',
      day_of_week: 4,
      time: '20:00',
      players_per_team: 5,
      has_fixed_goalkeepers: false,
      confirmation_deadline_hours: 2,
    },
  });

  const dayOfWeek = watch('day_of_week');
  const hasGk = watch('has_fixed_goalkeepers');

  const onSubmit = async (values: CreatePeladaInput) => {
    setSubmitting(true);
    try {
      const created = await peladasApi.create({...values, location: values.location || null});
      navigation.replace('PeladaDetail', {peladaId: created.id});
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.scroll}>
      <Controller
        control={control}
        name="name"
        render={({field}) => (
          <Input
            label={t.peladas.name}
            value={field.value}
            onChangeText={field.onChange}
            error={errors.name?.message}
          />
        )}
      />
      <Controller
        control={control}
        name="location"
        render={({field}) => (
          <Input
            label={t.peladas.location}
            value={field.value ?? ''}
            onChangeText={field.onChange}
            error={errors.location?.message}
          />
        )}
      />

      <Text style={styles.sectionLabel}>{t.peladas.dayOfWeek}</Text>
      <View style={styles.daysRow}>
        {[0, 1, 2, 3, 4, 5, 6].map(day => (
          <Pressable
            key={day}
            onPress={() => setValue('day_of_week', day)}
            style={[styles.dayPill, dayOfWeek === day && styles.dayPillActive]}>
            <Text
              style={[
                styles.dayText,
                dayOfWeek === day && styles.dayTextActive,
              ]}>
              {t.days[day]?.slice(0, 3)}
            </Text>
          </Pressable>
        ))}
      </View>

      <Controller
        control={control}
        name="time"
        render={({field}) => (
          <Input
            label={t.peladas.time}
            value={field.value}
            onChangeText={field.onChange}
            placeholder="HH:mm"
            error={errors.time?.message}
          />
        )}
      />
      <Controller
        control={control}
        name="players_per_team"
        render={({field}) => (
          <Input
            label={t.peladas.playersPerTeam}
            value={String(field.value)}
            onChangeText={text => field.onChange(Number.parseInt(text, 10) || 0)}
            keyboardType="number-pad"
            error={errors.players_per_team?.message}
          />
        )}
      />

      <View style={styles.switchRow}>
        <Text style={styles.sectionLabel}>{t.peladas.hasFixedGoalkeepers}</Text>
        <Switch
          value={hasGk}
          onValueChange={v => setValue('has_fixed_goalkeepers', v)}
          trackColor={{true: theme.colors.primaryLight, false: theme.colors.border}}
          thumbColor={hasGk ? theme.colors.primary : theme.colors.surface}
        />
      </View>

      <Controller
        control={control}
        name="confirmation_deadline_hours"
        render={({field}) => (
          <Input
            label={t.peladas.deadlineHours}
            value={String(field.value)}
            onChangeText={text => field.onChange(Number.parseInt(text, 10) || 0)}
            keyboardType="number-pad"
            error={errors.confirmation_deadline_hours?.message}
          />
        )}
      />

      <Button label={t.peladas.createSubmit} onPress={handleSubmit(onSubmit)} loading={submitting} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.lg},
  sectionLabel: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
    textTransform: 'uppercase',
    marginBottom: theme.spacing.xs,
  },
  daysRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.xs,
    marginBottom: theme.spacing.md,
  },
  dayPill: {
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: theme.radius.pill,
    backgroundColor: theme.colors.surface,
    borderWidth: 1,
    borderColor: theme.colors.border,
  },
  dayPillActive: {
    backgroundColor: theme.colors.primary,
    borderColor: theme.colors.primary,
  },
  dayText: {...theme.typography.body, color: theme.colors.text},
  dayTextActive: {color: '#fff', fontWeight: '700'},
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginVertical: theme.spacing.md,
  },
});
