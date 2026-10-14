import React, {useCallback, useEffect, useState} from 'react';
import {
  Alert,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import {useNavigation, useRoute, useFocusEffect, type RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {Button} from '@/components/ui/Button';
import {Card} from '@/components/ui/Card';
import {MemberRow} from '@/components/pelada/MemberRow';
import {peladasApi} from '@/services/api/peladas';
import {apiErrorMessage} from '@/services/api/client';
import {useAuthStore} from '@/state/authStore';
import {useCurrentSession} from '@/hooks/useCurrentSession';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {AppStackParamList} from '@/navigation/types';
import type {Pelada} from '@/types';

type Nav = NativeStackNavigationProp<AppStackParamList, 'PeladaDetail'>;
type Route = RouteProp<AppStackParamList, 'PeladaDetail'>;

export function PeladaDetailScreen(): React.JSX.Element {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const peladaId = route.params.peladaId;
  const currentUserId = useAuthStore(s => s.user?.id);

  const [pelada, setPelada] = useState<Pelada | null>(null);
  const [loading, setLoading] = useState(true);
  const session = useCurrentSession(peladaId);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await peladasApi.show(peladaId);
      setPelada(data);
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setLoading(false);
    }
  }, [peladaId]);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  useEffect(() => {
    if (pelada) {
      navigation.setOptions({title: pelada.name});
    }
  }, [navigation, pelada]);

  if (!pelada) {
    return (
      <View style={styles.center}>
        <Text>{loading ? t.common.loading : t.common.error}</Text>
      </View>
    );
  }

  const isAdmin = pelada.admin_id === currentUserId;

  async function toggleGoalkeeper(userId: number, next: boolean) {
    try {
      await peladasApi.updateMember(peladaId, userId, {is_fixed_goalkeeper: next});
      await load();
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    }
  }

  async function generateInvite() {
    try {
      const invite = await peladasApi.createInvite(peladaId);
      Alert.alert(
        'Convite gerado',
        `Token: ${invite.token}\nExpira: ${new Date(invite.expires_at).toLocaleString('pt-BR')}`,
      );
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    }
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.scroll}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}>
      <Card>
        <Text style={styles.code}>{pelada.short_code}</Text>
        <Text style={styles.meta}>
          {t.days[pelada.day_of_week]} · {pelada.time}
        </Text>
        {pelada.location ? <Text style={styles.meta}>{pelada.location}</Text> : null}
        <Text style={styles.meta}>
          {pelada.players_per_team} por time
          {pelada.has_fixed_goalkeepers ? ' · goleiros fixos' : ''}
        </Text>
      </Card>

      {session.session ? (
        <Card>
          <Text style={styles.sectionTitle}>Próxima sessão</Text>
          <Text style={styles.meta}>
            {new Date(session.session.scheduled_at).toLocaleString('pt-BR')}
          </Text>
          <Text style={styles.meta}>
            {t.presence.deadlinePrefix}{' '}
            {new Date(session.session.deadline_at).toLocaleString('pt-BR')}
          </Text>
          <View style={styles.sessionActions}>
            <Button
              label={t.presence.title}
              onPress={() =>
                navigation.navigate('Presence', {
                  sessionId: session.session!.id,
                  peladaId,
                })
              }
            />
            <Button
              label={t.draw.title}
              variant="secondary"
              onPress={() =>
                navigation.navigate('Draw', {
                  sessionId: session.session!.id,
                  peladaId,
                })
              }
            />
            <Button
              label={t.voting.title}
              variant="secondary"
              onPress={() =>
                navigation.navigate('Voting', {
                  sessionId: session.session!.id,
                  peladaId,
                })
              }
            />
          </View>
        </Card>
      ) : (
        <Card>
          <Text style={styles.meta}>{t.presence.noSession}</Text>
        </Card>
      )}

      <Button
        label={t.ranking.title}
        variant="secondary"
        onPress={() => navigation.navigate('Ranking', {peladaId})}
      />

      <Card>
        <Text style={styles.sectionTitle}>{t.peladas.detailsMembers}</Text>
        {(pelada.members ?? []).map(member => (
          <MemberRow
            key={member.id}
            member={member}
            canManage={isAdmin && pelada.has_fixed_goalkeepers}
            onToggleGoalkeeper={
              isAdmin && pelada.has_fixed_goalkeepers
                ? next => toggleGoalkeeper(member.user_id, next)
                : undefined
            }
          />
        ))}
      </Card>

      {isAdmin ? (
        <Button label="Gerar convite" variant="secondary" onPress={generateInvite} />
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.md},
  center: {flex: 1, alignItems: 'center', justifyContent: 'center'},
  code: {
    ...theme.typography.caption,
    color: theme.colors.primary,
    fontWeight: '700',
    marginBottom: theme.spacing.xs,
  },
  meta: {...theme.typography.body, color: theme.colors.textMuted},
  sessionActions: {
    gap: 8,
    marginTop: 12,
  },
  sectionTitle: {
    ...theme.typography.title,
    color: theme.colors.text,
    marginBottom: theme.spacing.sm,
  },
});
