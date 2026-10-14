import React from 'react';
import {StyleSheet, View} from 'react-native';
import {Button} from '@/components/ui/Button';
import {t} from '@/i18n/pt-BR';
import type {PresenceStatus} from '@/types';

type Props = {
  value: PresenceStatus | null;
  disabled?: boolean;
  loading?: boolean;
  onChange: (status: PresenceStatus) => void;
};

export function PresenceToggle({value, disabled, loading, onChange}: Props): React.JSX.Element {
  return (
    <View style={styles.row}>
      <Button
        label={t.presence.confirmed}
        variant={value === 'confirmed' ? 'primary' : 'secondary'}
        onPress={() => onChange('confirmed')}
        disabled={disabled}
        loading={loading && value !== 'confirmed'}
        style={styles.btn}
      />
      <Button
        label={t.presence.declined}
        variant={value === 'declined' ? 'danger' : 'secondary'}
        onPress={() => onChange('declined')}
        disabled={disabled}
        loading={loading && value !== 'declined'}
        style={styles.btn}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    gap: 12,
  },
  btn: {
    flex: 1,
  },
});
