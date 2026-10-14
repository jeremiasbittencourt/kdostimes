import React, {useMemo, useState} from 'react';
import {Alert, Platform, Pressable, ScrollView, StyleSheet, Switch, Text, View} from 'react-native';
import {Button} from '@/components/ui/Button';
import {Card} from '@/components/ui/Card';
import {Input} from '@/components/ui/Input';
import {StarRating} from '@/components/ui/StarRating';
import {authApi} from '@/services/api/auth';
import {apiErrorMessage} from '@/services/api/client';
import {useAuthStore} from '@/state/authStore';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {NotificationEvent, Position} from '@/types';

const ALL_POSITIONS: Position[] = ['GOL', 'ZAG', 'LAT', 'MEI', 'ATA'];

const SILENCEABLE_EVENTS: NotificationEvent[] = [
  'session_opened',
  'presence_deadline_reminder',
  'draw_published',
  'voting_opened',
  'voting_deadline_reminder',
  'voting_closed',
];

export function ProfileScreen(): React.JSX.Element {
  const user = useAuthStore(s => s.user);
  const refreshUser = useAuthStore(s => s.refreshUser);
  const clearSession = useAuthStore(s => s.clearSession);

  const [name, setName] = useState(user?.name ?? '');
  const [skill, setSkill] = useState(user?.skill_rating ?? 5);
  const [positions, setPositions] = useState<Position[]>(
    (user?.positions ?? []).sort((a, b) => a.priority - b.priority).map(p => p.position),
  );
  const [saving, setSaving] = useState(false);

  const prefsByEvent = useMemo(() => {
    const map = new Map<string, boolean>();
    (user?.notification_preferences ?? []).forEach(p => map.set(p.event_type, p.enabled));
    return map;
  }, [user]);

  const [prefs, setPrefs] = useState<Record<string, boolean>>(() => {
    const init: Record<string, boolean> = {};
    SILENCEABLE_EVENTS.forEach(e => {
      init[e] = prefsByEvent.get(e) ?? true;
    });
    return init;
  });

  function togglePosition(pos: Position) {
    setPositions(prev => {
      if (prev.includes(pos)) {
        return prev.filter(p => p !== pos);
      }
      if (prev.length >= 2) {
        return [prev[1]!, pos];
      }
      return [...prev, pos];
    });
  }

  async function save() {
    setSaving(true);
    try {
      await authApi.updateMe({
        name,
        skill_rating: skill,
        positions: positions.map((position, idx) => ({position, priority: (idx + 1) as 1 | 2})),
      });
      await authApi.updateNotificationPreferences(
        SILENCEABLE_EVENTS.map(event_type => ({event_type, enabled: prefs[event_type] ?? true})),
      );
      await refreshUser();
      Alert.alert('Cadê Os Times?', t.profile.saved);
    } catch (err) {
      Alert.alert('Ops', apiErrorMessage(err));
    } finally {
      setSaving(false);
    }
  }

  async function logout() {
    try {
      await authApi.logout();
    } catch {
      // segue — mesmo se a API falhar, limpa localmente
    }
    await clearSession();
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.scroll}>
      <Card>
        <Input label="Nome" value={name} onChangeText={setName} />
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>{t.profile.skill}</Text>
        <StarRating value={skill} onChange={setSkill} max={10} />
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>{t.profile.positions}</Text>
        <Text style={styles.hint}>
          {positions.length === 0
            ? 'Toque para selecionar até 2.'
            : `1ª: ${positions[0]}${positions[1] ? ` · 2ª: ${positions[1]}` : ''}`}
        </Text>
        <View style={styles.posRow}>
          {ALL_POSITIONS.map(pos => {
            const active = positions.includes(pos);
            return (
              <Pressable
                key={pos}
                onPress={() => togglePosition(pos)}
                style={[styles.posPill, active && styles.posPillActive]}>
                <Text style={[styles.posText, active && styles.posTextActive]}>{pos}</Text>
              </Pressable>
            );
          })}
        </View>
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>{t.profile.notifications}</Text>
        {SILENCEABLE_EVENTS.map(event => (
          <View key={event} style={styles.prefRow}>
            <Text style={styles.prefLabel}>{t.notificationEvents[event]}</Text>
            <Switch
              value={prefs[event] ?? true}
              onValueChange={v => setPrefs(prev => ({...prev, [event]: v}))}
              trackColor={{true: theme.colors.primaryLight, false: theme.colors.border}}
              thumbColor={prefs[event] ? theme.colors.primary : theme.colors.surface}
            />
          </View>
        ))}
        <Text style={styles.hint}>
          Eventos críticos (remoção do grupo) sempre chegam — não podem ser silenciados.
        </Text>
      </Card>

      <Button label={t.common.save} onPress={save} loading={saving} />
      <Button label={t.profile.logout} variant="danger" onPress={logout} style={{marginTop: theme.spacing.sm}} />

      <Text style={styles.footer}>
        {user?.email} · {Platform.OS}
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  scroll: {padding: theme.spacing.md},
  sectionTitle: {
    ...theme.typography.title,
    color: theme.colors.text,
    marginBottom: theme.spacing.sm,
  },
  hint: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
    marginTop: theme.spacing.xs,
  },
  posRow: {flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: theme.spacing.sm},
  posPill: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: theme.radius.pill,
    backgroundColor: theme.colors.surface,
    borderWidth: 1,
    borderColor: theme.colors.border,
  },
  posPillActive: {backgroundColor: theme.colors.primary, borderColor: theme.colors.primary},
  posText: {...theme.typography.body, color: theme.colors.text, fontWeight: '600'},
  posTextActive: {color: '#fff'},
  prefRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: theme.spacing.xs,
  },
  prefLabel: {...theme.typography.body, color: theme.colors.text, flex: 1, paddingRight: theme.spacing.sm},
  footer: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
    textAlign: 'center',
    marginTop: theme.spacing.lg,
  },
});
