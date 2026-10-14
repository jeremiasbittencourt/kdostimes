import React, {useCallback, useState} from 'react';
import {Alert, RefreshControl, ScrollView, StyleSheet, Text, View} from 'react-native';
import {useFocusEffect, useRoute, type RouteProp} from '@react-navigation/native';
import {Card} from '@/components/ui/Card';
import {PresenceToggle} from '@/components/pelada/PresenceToggle';
import {sessionsApi} from '@/services/api/sessions';
import {apiErrorMessage} from '@/services/api/client';
import {useAuthStore} from '@/state/authStore';
import {useCurrentSession} from '@/hooks/useCurrentSession';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {AppStackParamList} from '@/navigation/types';
import type {PresenceStatus} from '@/types';

type Route = RouteProp<AppStackParamList, 'Presence'>;

export function PresenceScreen(): React.JSX.Element {
  const route = useRoute<Route>();
  const {peladaId, sessionId} = route.params;
  const userId = useAuthStore(s => s.user?.id);
  const {session, loading, reload} = useCurrentSession(peladaId);
  const [submitting, setSubmitting] = useState<PresenceStatus | null>(null);

  useFocusEffect(
    useCallback(() => {
      void reload();
    }, [reload]),
  );

  const deadlinePassed = session ? new Date(session.deadline_at).getTime() < Date.now() : false;
  const myPresence = session?.presences?.find(p => p.user_id === userId)?.status ?? null;

  async function setPresence(status: PresenceStatus) {
    setSubmitting(status);
    try {
      await sessionsApi.confirmPresence(sessionId, status);
      await reload();
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setSubmitting(null);
    }
  }

  if (!session) {
    return (
      <View style={styles.center}>
        <Text>{loading ? t.common.loading : t.presence.noSession}</Text>
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.scroll}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={reload} />}>
      <Card>
        <Text style={styles.meta}>
          {new Date(session.scheduled_at).toLocaleString('pt-BR')}
        </Text>
        <Text style={styles.meta}>
          {t.presence.deadlinePrefix}{' '}
          {new Date(session.deadline_at).toLocaleString('pt-BR')}
        </Text>
      </Card>

      {deadlinePassed ? (
        <Card>
          <Text style={[styles.meta, {color: theme.colors.warning}]}>{t.presence.locked}</Text>
        </Card>
      ) : (
        <Card>
          <Text style={styles.sectionTitle}>Sua resposta</Text>
          <PresenceToggle
            value={myPresence}
            disabled={submitting !== null}
            loading={submitting !== null}
            onChange={setPresence}
          />
        </Card>
      )}

      <Card>
        <Text style={styles.sectionTitle}>Confirmados</Text>
        {(session.presences ?? [])
          .filter(p => p.status === 'confirmed')
          .map(p => (
            <Text key={p.id} style={styles.presenceLine}>
              ✓ {p.user?.name ?? '—'}
            </Text>
          ))}
        {(session.presences ?? []).filter(p => p.status === 'confirmed').length === 0 ? (
          <Text style={styles.meta}>Ninguém confirmou ainda.</Text>
        ) : null}
      </Card>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.md},
  center: {flex: 1, alignItems: 'center', justifyContent: 'center'},
  meta: {...theme.typography.body, color: theme.colors.textMuted},
  sectionTitle: {
    ...theme.typography.title,
    color: theme.colors.text,
    marginBottom: theme.spacing.sm,
  },
  presenceLine: {
    ...theme.typography.body,
    color: theme.colors.text,
    paddingVertical: 4,
  },
});
