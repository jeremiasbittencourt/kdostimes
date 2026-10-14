import React from 'react';
import {Pressable, StyleSheet, Text, View} from 'react-native';
import {Card} from '@/components/ui/Card';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {Pelada} from '@/types';

type Props = {
  pelada: Pelada;
  onPress: () => void;
};

export function PeladaCard({pelada, onPress}: Props): React.JSX.Element {
  return (
    <Pressable onPress={onPress}>
      <Card style={styles.card}>
        <View style={styles.headerRow}>
          <Text style={styles.name}>{pelada.name}</Text>
          <Text style={styles.code}>{pelada.short_code}</Text>
        </View>
        <Text style={styles.meta}>
          {t.days[pelada.day_of_week]} · {pelada.time}
        </Text>
        {pelada.location ? <Text style={styles.location}>{pelada.location}</Text> : null}
        <Text style={styles.format}>
          {pelada.players_per_team} por time
          {pelada.has_fixed_goalkeepers ? ' · goleiros fixos' : ''}
        </Text>
      </Card>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    gap: 4,
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  name: {
    ...theme.typography.title,
    color: theme.colors.text,
  },
  code: {
    ...theme.typography.caption,
    color: theme.colors.primary,
    fontWeight: '700',
  },
  meta: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
  },
  location: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
  },
  format: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
    marginTop: theme.spacing.xs,
  },
});
