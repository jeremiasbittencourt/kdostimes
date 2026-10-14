import React from 'react';
import {StyleSheet, Text, View} from 'react-native';
import {theme} from '@/theme';
import type {RankingRow as RankingRowData} from '@/types';

type Props = {
  position: number;
  row: RankingRowData;
};

export function RankingRow({position, row}: Props): React.JSX.Element {
  const medal = position === 1 ? '🥇' : position === 2 ? '🥈' : position === 3 ? '🥉' : null;

  return (
    <View style={styles.row}>
      <View style={styles.rank}>
        {medal ? (
          <Text style={styles.medal}>{medal}</Text>
        ) : (
          <Text style={styles.position}>{position}º</Text>
        )}
      </View>
      <View style={styles.info}>
        <Text style={styles.name}>{row.name ?? '—'}</Text>
        {row.wins_count > 0 ? (
          <Text style={styles.meta}>{row.wins_count} título(s)</Text>
        ) : null}
      </View>
      <Text style={styles.points}>
        {row.total_points > 0 ? `+${row.total_points}` : row.total_points}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: theme.spacing.sm,
    paddingHorizontal: theme.spacing.md,
    backgroundColor: theme.colors.surface,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    borderColor: theme.colors.border,
    marginBottom: theme.spacing.sm,
  },
  rank: {
    width: 40,
    alignItems: 'center',
  },
  position: {
    ...theme.typography.title,
    color: theme.colors.textMuted,
  },
  medal: {
    fontSize: 26,
  },
  info: {flex: 1, paddingHorizontal: theme.spacing.sm},
  name: {
    ...theme.typography.body,
    color: theme.colors.text,
    fontWeight: '600',
  },
  meta: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
  },
  points: {
    ...theme.typography.title,
    color: theme.colors.primary,
    fontWeight: '800',
  },
});
