import React, {useCallback, useEffect, useState} from 'react';
import {Alert, RefreshControl, ScrollView, StyleSheet, Text, View} from 'react-native';
import {useFocusEffect, useRoute, type RouteProp} from '@react-navigation/native';
import {Button} from '@/components/ui/Button';
import {Card} from '@/components/ui/Card';
import {CadeOsTimesButton} from '@/components/ui/CadeOsTimesButton';
import {TeamCard} from '@/components/draw/TeamCard';
import {useDraw} from '@/hooks/useDraw';
import {peladasApi} from '@/services/api/peladas';
import {apiErrorMessage} from '@/services/api/client';
import {useAuthStore} from '@/state/authStore';
import {theme} from '@/theme';
import type {AppStackParamList} from '@/navigation/types';
import type {Pelada} from '@/types';

type Route = RouteProp<AppStackParamList, 'Draw'>;

export function DrawScreen(): React.JSX.Element {
  const route = useRoute<Route>();
  const {sessionId, peladaId} = route.params;
  const userId = useAuthStore(s => s.user?.id);

  const {draw, loading, error, reload, generate, publish} = useDraw(sessionId);
  const [pelada, setPelada] = useState<Pelada | null>(null);
  const [acting, setActing] = useState<'generating' | 'publishing' | null>(null);

  const loadPelada = useCallback(async () => {
    try {
      const data = await peladasApi.show(peladaId);
      setPelada(data);
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    }
  }, [peladaId]);

  useFocusEffect(
    useCallback(() => {
      void loadPelada();
    }, [loadPelada]),
  );

  useEffect(() => {
    if (draw?.warnings && draw.warnings.length > 0) {
      Alert.alert('Avisos do sorteio', draw.warnings.join('\n\n'));
    }
  }, [draw]);

  const isAdmin = pelada ? pelada.admin_id === userId : false;
  const isDraft = draw ? !draw.published : false;

  async function handleGenerate(): Promise<void> {
    setActing('generating');
    try {
      await generate();
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setActing(null);
    }
  }

  async function handlePublish(): Promise<void> {
    if (!draw) return;
    setActing('publishing');
    try {
      await publish(draw.id);
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setActing(null);
    }
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.scroll}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={reload} />}>
      {!draw ? (
        <View style={styles.emptyBlock}>
          {isAdmin ? (
            <>
              <Text style={styles.title}>Bora descobrir?</Text>
              <Text style={styles.subtitle}>
                Só admin pode sortear. Toque no botão pra gerar os times balanceados.
              </Text>
              <CadeOsTimesButton
                onPress={handleGenerate}
                loading={acting === 'generating'}
                disabled={acting !== null}
              />
            </>
          ) : (
            <Card>
              <Text style={styles.title}>Aguardando sorteio</Text>
              <Text style={styles.subtitle}>
                {error ?? 'O admin ainda não gerou/publicou o sorteio desta sessão.'}
              </Text>
            </Card>
          )}
        </View>
      ) : (
        <>
          <View style={styles.statusRow}>
            <Text
              style={[
                styles.status,
                isDraft ? styles.statusDraft : styles.statusPublished,
              ]}>
              {isDraft ? 'Rascunho — ainda não publicado' : 'Publicado'}
            </Text>
          </View>

          {(draw.teams ?? []).map(team => (
            <TeamCard key={team.id} team={team} />
          ))}

          {isAdmin && isDraft ? (
            <View style={styles.actions}>
              <Button
                label="Publicar este sorteio"
                onPress={handlePublish}
                loading={acting === 'publishing'}
                disabled={acting !== null}
              />
              <Button
                label="Refazer (Cadê Os Times?)"
                variant="secondary"
                onPress={handleGenerate}
                loading={acting === 'generating'}
                disabled={acting !== null}
              />
            </View>
          ) : null}
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.md, paddingBottom: theme.spacing.xl},
  emptyBlock: {
    paddingTop: theme.spacing.xl,
    gap: theme.spacing.md,
  },
  title: {
    ...theme.typography.heading,
    color: theme.colors.text,
    textAlign: 'center',
  },
  subtitle: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
    textAlign: 'center',
    marginBottom: theme.spacing.md,
  },
  statusRow: {
    marginBottom: theme.spacing.sm,
  },
  status: {
    ...theme.typography.caption,
    fontWeight: '700',
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: theme.radius.pill,
    alignSelf: 'flex-start',
    overflow: 'hidden',
  },
  statusDraft: {backgroundColor: '#FDEBCC', color: theme.colors.warning},
  statusPublished: {backgroundColor: '#DFF3E4', color: theme.colors.primaryDark},
  actions: {
    gap: theme.spacing.sm,
    marginTop: theme.spacing.md,
  },
});
