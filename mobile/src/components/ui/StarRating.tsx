import React from 'react';
import {Pressable, StyleSheet, Text, View} from 'react-native';
import {theme} from '@/theme';

type Props = {
  value: number;
  onChange?: (value: number) => void;
  max?: number;
  readOnly?: boolean;
};

export function StarRating({value, onChange, max = 10, readOnly}: Props): React.JSX.Element {
  const stars = Array.from({length: max}, (_, i) => i + 1);

  return (
    <View style={styles.row}>
      {stars.map(n => {
        const filled = n <= value;
        const Node = (
          <Text style={[styles.star, {color: filled ? theme.colors.accent : theme.colors.border}]}>
            ★
          </Text>
        );
        if (readOnly || !onChange) {
          return <View key={n}>{Node}</View>;
        }
        return (
          <Pressable key={n} onPress={() => onChange(n)} hitSlop={6}>
            {Node}
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  star: {
    fontSize: 24,
  },
});
