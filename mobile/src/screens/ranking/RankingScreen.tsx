import React, {useState} from 'react';
import {FlatList, Pressable, RefreshControl, StyleSheet, Text, View} from 'react-native';
import {useRoute, type RouteProp} from '@react-navigation/native';
import {RankingRow} from '@/components/ranking/RankingRow';
import {useRanking} from '@/hooks/useRanking';
import {theme} from '@/theme';
import type {AppStackParamList} from '@/navigation/types';
import type {RankingPeriod} from '@/types';

type Route = RouteProp<AppStackParamList, 'Ranking'>;

const PERIODS: Array<{value: RankingPeriod; label: string}> = [
  {value: 'month', label: 'Mês'},
  {value: 'year', label: 'Ano'},
  {value: 'last10', label: 'Últimas 10'},
  {value: 'all', label: 'Geral'},
];

export function RankingScreen(): React.JSX.Element {
  const route = useRoute<Route>();
  const {peladaId} = route.params;
  const [period, setPeriod] = useState<RankingPeriod>('month');
  const {rows, loading, error, reload} = useRanking(peladaId, period);

  return (
    <View style={styles.container}>
      <View style={styles.filters}>
        {PERIODS.map(p => {
          const active = p.value === period;
          return (
            <Pressable
              key={p.value}
              onPress={() => setPeriod(p.value)}
              style={[styles.pill, active && styles.pillActive]}>
              <Text style={[styles.pillText, active && styles.pillTextActive]}>{p.label}</Text>
            </Pressable>
          );
        })}
      </View>

      <FlatList
        data={rows}
        keyExtractor={item => String(item.user_id)}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={reload} />}
        renderItem={({item, index}) => <RankingRow position={index + 1} row={item} />}
        ListEmptyComponent={
          !loading ? (
            <Text style={styles.empty}>{error ?? 'Sem pontuação neste período ainda.'}</Text>
          ) : null
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  filters: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.xs,
    padding: theme.spacing.md,
  },
  pill: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    borderColor: theme.colors.border,
    backgroundColor: theme.colors.surface,
  },
  pillActive: {
    backgroundColor: theme.colors.primary,
    borderColor: theme.colors.primary,
  },
  pillText: {...theme.typography.body, color: theme.colors.text, fontWeight: '600'},
  pillTextActive: {color: '#fff'},
  list: {paddingHorizontal: theme.spacing.md, paddingBottom: theme.spacing.xl},
  empty: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
    textAlign: 'center',
    marginTop: theme.spacing.xl,
  },
});
