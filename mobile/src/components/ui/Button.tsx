import React from 'react';
import {ActivityIndicator, Pressable, StyleSheet, Text, type ViewStyle} from 'react-native';
import {theme} from '@/theme';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

type Props = {
  label: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  variant?: Variant;
  style?: ViewStyle;
};

export function Button({label, onPress, loading, disabled, variant = 'primary', style}: Props): React.JSX.Element {
  const isDisabled = Boolean(loading || disabled);
  const variantStyles = getVariantStyles(variant, isDisabled);

  return (
    <Pressable
      onPress={isDisabled ? undefined : onPress}
      style={[styles.base, variantStyles.container, style]}>
      {loading ? (
        <ActivityIndicator color={variantStyles.text.color} />
      ) : (
        <Text style={[styles.label, variantStyles.text]}>{label}</Text>
      )}
    </Pressable>
  );
}

function getVariantStyles(variant: Variant, disabled: boolean) {
  const opacity = disabled ? 0.5 : 1;
  switch (variant) {
    case 'primary':
      return {
        container: {backgroundColor: theme.colors.primary, opacity},
        text: {color: '#fff'},
      };
    case 'secondary':
      return {
        container: {
          backgroundColor: theme.colors.surface,
          borderWidth: 1,
          borderColor: theme.colors.primary,
          opacity,
        },
        text: {color: theme.colors.primary},
      };
    case 'ghost':
      return {
        container: {backgroundColor: 'transparent', opacity},
        text: {color: theme.colors.primary},
      };
    case 'danger':
      return {
        container: {backgroundColor: theme.colors.danger, opacity},
        text: {color: '#fff'},
      };
  }
}

const styles = StyleSheet.create({
  base: {
    minHeight: 48,
    paddingHorizontal: theme.spacing.md,
    borderRadius: theme.radius.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: {
    ...theme.typography.title,
  },
});
