import React from 'react';
import {StyleSheet, Text, View} from 'react-native';
import {Card} from '@/components/ui/Card';
import {theme} from '@/theme';
import type {DrawTeam} from '@/types';

type Props = {
  team: DrawTeam;
};

export function TeamCard({team}: Props): React.JSX.Element {
  const players = team.players ?? [];
  return (
    <Card style={styles.card}>
      <View style={styles.header}>
        <Text style={styles.name}>{team.name}</Text>
        <Text style={styles.rating}>{team.total_rating} ★</Text>
      </View>
      {players.map(player => (
        <View key={player.id} style={styles.playerRow}>
          <Text style={styles.playerName}>{player.user?.name ?? '—'}</Text>
          <Text style={styles.playerMeta}>
            {player.is_goalkeeper ? 'GOL' : player.assigned_position ?? '—'}
            {player.user?.skill_rating ? ` · ${player.user.skill_rating}★` : ''}
          </Text>
        </View>
      ))}
    </Card>
  );
}

const styles = StyleSheet.create({
  card: {
    borderLeftWidth: 4,
    borderLeftColor: theme.colors.primary,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'baseline',
    marginBottom: theme.spacing.sm,
  },
  name: {
    ...theme.typography.title,
    color: theme.colors.text,
  },
  rating: {
    ...theme.typography.caption,
    color: theme.colors.accent,
    fontWeight: '700',
  },
  playerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
    borderBottomWidth: 1,
    borderBottomColor: theme.colors.border,
  },
  playerName: {
    ...theme.typography.body,
    color: theme.colors.text,
    flex: 1,
  },
  playerMeta: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
  },
});
