import React, {useCallback} from 'react';
import {FlatList, RefreshControl, StyleSheet, Text, View} from 'react-native';
import {useNavigation, useFocusEffect} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {PeladaCard} from '@/components/pelada/PeladaCard';
import {Button} from '@/components/ui/Button';
import {usePeladas} from '@/hooks/usePeladas';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {AppStackParamList} from '@/navigation/types';

type Nav = NativeStackNavigationProp<AppStackParamList, 'Home'>;

export function HomeScreen(): React.JSX.Element {
  const navigation = useNavigation<Nav>();
  const {peladas, loading, error, reload} = usePeladas();

  useFocusEffect(
    useCallback(() => {
      void reload();
    }, [reload]),
  );

  return (
    <View style={styles.container}>
      <View style={styles.actions}>
        <Button
          label={t.peladas.create}
          onPress={() => navigation.navigate('CreatePelada')}
          style={styles.actionBtn}
        />
        <Button
          label={t.peladas.join}
          variant="secondary"
          onPress={() => navigation.navigate('JoinPelada')}
          style={styles.actionBtn}
        />
      </View>

      <FlatList
        data={peladas}
        keyExtractor={item => String(item.id)}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={reload} />}
        ListEmptyComponent={
          !loading ? (
            <Text style={styles.empty}>{error ?? t.peladas.empty}</Text>
          ) : null
        }
        renderItem={({item}) => (
          <PeladaCard
            pelada={item}
            onPress={() => navigation.navigate('PeladaDetail', {peladaId: item.id})}
          />
        )}
      />

      <View style={styles.footer}>
        <Button
          label={t.profile.title}
          variant="ghost"
          onPress={() => navigation.navigate('Profile')}
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: theme.colors.bg},
  actions: {
    flexDirection: 'row',
    gap: theme.spacing.sm,
    padding: theme.spacing.md,
  },
  actionBtn: {flex: 1},
  list: {paddingHorizontal: theme.spacing.md, paddingBottom: theme.spacing.lg},
  empty: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
    textAlign: 'center',
    marginTop: theme.spacing.xl,
  },
  footer: {
    padding: theme.spacing.sm,
  },
});
