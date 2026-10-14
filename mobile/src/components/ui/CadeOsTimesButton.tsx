import React from 'react';
import {ActivityIndicator, Pressable, StyleSheet, Text} from 'react-native';
import {theme} from '@/theme';

type Props = {
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  label?: string;
};

/**
 * Botão-mantra da marca: grande, verde, com a pergunta clássica no meio.
 * Usado na tela de sorteio como CTA principal.
 */
export function CadeOsTimesButton({
  onPress,
  loading,
  disabled,
  label = 'Cadê Os Times?',
}: Props): React.JSX.Element {
  const isDisabled = Boolean(loading || disabled);
  return (
    <Pressable
      onPress={isDisabled ? undefined : onPress}
      style={[styles.btn, isDisabled && styles.btnDisabled]}>
      {loading ? (
        <ActivityIndicator color="#fff" size="large" />
      ) : (
        <Text style={styles.label}>{label}</Text>
      )}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  btn: {
    backgroundColor: theme.colors.primary,
    borderRadius: theme.radius.lg,
    paddingVertical: 28,
    paddingHorizontal: theme.spacing.lg,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: theme.colors.primaryDark,
    shadowOffset: {width: 0, height: 4},
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
  },
  btnDisabled: {opacity: 0.6},
  label: {
    fontSize: 26,
    fontWeight: '800',
    color: '#fff',
    letterSpacing: 0.5,
    textAlign: 'center',
  },
});
