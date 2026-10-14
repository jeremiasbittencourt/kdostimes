import React, {useCallback, useMemo} from 'react';
import {Alert, RefreshControl, ScrollView, StyleSheet, Text, View} from 'react-native';
import {useFocusEffect, useRoute, type RouteProp} from '@react-navigation/native';
import {Card} from '@/components/ui/Card';
import {CategoryCard} from '@/components/voting/CategoryCard';
import {useVotingSession} from '@/hooks/useVotingSession';
import {useCurrentSession} from '@/hooks/useCurrentSession';
import {votingApi} from '@/services/api/voting';
import {apiErrorMessage} from '@/services/api/client';
import {useAuthStore} from '@/state/authStore';
import {theme} from '@/theme';
import type {AppStackParamList} from '@/navigation/types';
import type {User} from '@/types';

type Route = RouteProp<AppStackParamList, 'Voting'>;

export function VotingScreen(): React.JSX.Element {
  const route = useRoute<Route>();
  const {sessionId, peladaId} = route.params;
  const currentUserId = useAuthStore(s => s.user?.id);

  const voting = useVotingSession(sessionId);
  const session = useCurrentSession(peladaId);

  useFocusEffect(
    useCallback(() => {
      void voting.reload();
      void session.reload();
    }, [voting, session]),
  );

  const confirmedOthers: User[] = useMemo(() => {
    return (session.session?.presences ?? [])
      .filter(p => p.status === 'confirmed' && p.user && p.user_id !== currentUserId)
      .map(p => p.user!)
      .sort((a, b) => a.name.localeCompare(b.name));
  }, [session.session, currentUserId]);

  const votedMap = useMemo(() => {
    const map = new Map<number, number>();
    (voting.voting?.my_votes ?? []).forEach(v => map.set(v.category_id, v.voted_user_id));
    return map;
  }, [voting.voting]);

  const isClosed = voting.voting
    ? voting.voting.status === 'closed' || new Date(voting.voting.closes_at).getTime() < Date.now()
    : false;

  async function submitVote(categoryId: number, votedUserId: number): Promise<void> {
    if (!voting.voting) return;
    try {
      await votingApi.castVote(voting.voting.id, {category_id: categoryId, voted_user_id: votedUserId});
      await voting.reload();
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    }
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.scroll}
      refreshControl={
        <RefreshControl
          refreshing={voting.loading || session.loading}
          onRefresh={() => {
            void voting.reload();
            void session.reload();
          }}
        />
      }>
      {!voting.voting ? (
        <Card>
          <Text style={styles.empty}>
            {voting.loading ? 'Carregando…' : 'A votação ainda não abriu para esta sessão.'}
          </Text>
        </Card>
      ) : (
        <>
          <Card>
            <Text style={styles.title}>Eleja os destaques</Text>
            <Text style={styles.meta}>
              Fecha em {new Date(voting.voting.closes_at).toLocaleString('pt-BR')}
            </Text>
            {isClosed ? (
              <Text style={styles.closedTag}>Votação encerrada.</Text>
            ) : null}
          </Card>

          {(voting.voting.categories ?? []).map(category => (
            <CategoryCard
              key={category.id}
              category={category}
              candidates={confirmedOthers}
              votedUserId={votedMap.get(category.id) ?? null}
              disabled={isClosed}
              onVote={userId => submitVote(category.id, userId)}
            />
          ))}
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.md},
  title: {
    ...theme.typography.title,
    color: theme.colors.text,
    marginBottom: theme.spacing.xs,
  },
  meta: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
  },
  closedTag: {
    ...theme.typography.caption,
    color: theme.colors.warning,
    marginTop: theme.spacing.xs,
    fontWeight: '700',
  },
  empty: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
    textAlign: 'center',
  },
});
