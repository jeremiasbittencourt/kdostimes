import React from 'react';
import {ActivityIndicator, StyleSheet, Text, View} from 'react-native';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';

export function SplashScreen(): React.JSX.Element {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>{t.brand.name}</Text>
      <Text style={styles.tagline}>{t.brand.tagline}</Text>
      <ActivityIndicator color="#fff" style={styles.spinner} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: theme.colors.primary,
    padding: theme.spacing.lg,
  },
  title: {
    fontSize: 34,
    fontWeight: '800',
    color: '#fff',
    marginBottom: theme.spacing.md,
  },
  tagline: {
    ...theme.typography.body,
    color: '#E5F1E8',
    textAlign: 'center',
  },
  spinner: {
    marginTop: theme.spacing.xl,
  },
});
