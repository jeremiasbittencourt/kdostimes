import React from 'react';
import {StyleSheet, Switch, Text, View} from 'react-native';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {PeladaMember} from '@/types';

type Props = {
  member: PeladaMember;
  canManage: boolean;
  onToggleGoalkeeper?: (next: boolean) => void;
};

export function MemberRow({member, canManage, onToggleGoalkeeper}: Props): React.JSX.Element {
  return (
    <View style={styles.row}>
      <View style={styles.info}>
        <Text style={styles.name}>{member.user?.name ?? '—'}</Text>
        <Text style={styles.meta}>
          {member.role === 'admin' ? 'Admin' : 'Membro'}
          {member.user?.skill_rating ? ` · ${member.user.skill_rating}★` : ''}
        </Text>
      </View>
      {canManage && onToggleGoalkeeper ? (
        <View style={styles.toggle}>
          <Text style={styles.toggleLabel}>{t.peladas.fixedGk}</Text>
          <Switch
            value={member.is_fixed_goalkeeper}
            onValueChange={onToggleGoalkeeper}
            trackColor={{true: theme.colors.primaryLight, false: theme.colors.border}}
            thumbColor={member.is_fixed_goalkeeper ? theme.colors.primary : theme.colors.surface}
          />
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: theme.spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: theme.colors.border,
  },
  info: {
    flex: 1,
  },
  name: {
    ...theme.typography.body,
    color: theme.colors.text,
  },
  meta: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
  },
  toggle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: theme.spacing.sm,
  },
  toggleLabel: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
  },
});
