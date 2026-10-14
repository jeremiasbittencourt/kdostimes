import React, {useState} from 'react';
import {Pressable, StyleSheet, Text, View} from 'react-native';
import {Card} from '@/components/ui/Card';
import {theme} from '@/theme';
import type {User, VotingCategory} from '@/types';

type Props = {
  category: VotingCategory;
  candidates: User[];
  votedUserId: number | null;
  disabled?: boolean;
  onVote: (votedUserId: number) => Promise<void> | void;
};

/**
 * Exibe uma categoria de votação com os candidatos confirmados.
 * Colapsa quando o usuário já votou, mostrando quem foi escolhido.
 */
export function CategoryCard({category, candidates, votedUserId, disabled, onVote}: Props): React.JSX.Element {
  const [submittingFor, setSubmittingFor] = useState<number | null>(null);

  const alreadyVoted = votedUserId !== null;
  const votedCandidate = candidates.find(c => c.id === votedUserId);
  const isHumor = category.type === 'humor';

  async function handlePress(user: User): Promise<void> {
    setSubmittingFor(user.id);
    try {
      await onVote(user.id);
    } finally {
      setSubmittingFor(null);
    }
  }

  return (
    <Card style={[styles.card, isHumor && styles.cardHumor]}>
      <View style={styles.header}>
        <Text style={styles.name}>{category.name}</Text>
        <Text style={[styles.tag, isHumor ? styles.tagHumor : styles.tagPositive]}>
          {isHumor ? 'humor' : `+${category.points_per_vote}`}
        </Text>
      </View>
      {category.description ? <Text style={styles.desc}>{category.description}</Text> : null}

      {alreadyVoted ? (
        <Text style={styles.voted}>
          Seu voto: <Text style={styles.votedName}>{votedCandidate?.name ?? '—'}</Text>
        </Text>
      ) : (
        <View style={styles.list}>
          {candidates.map(candidate => (
            <Pressable
              key={candidate.id}
              onPress={() => handlePress(candidate)}
              disabled={disabled || submittingFor !== null}
              style={({pressed}) => [
                styles.candidate,
                pressed && styles.candidatePressed,
                submittingFor === candidate.id && styles.candidateActive,
              ]}>
              <Text style={styles.candidateName}>{candidate.name}</Text>
            </Pressable>
          ))}
          {candidates.length === 0 ? (
            <Text style={styles.empty}>Nenhum jogador elegível.</Text>
          ) : null}
        </View>
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  card: {
    borderLeftWidth: 4,
    borderLeftColor: theme.colors.primary,
  },
  cardHumor: {
    borderLeftColor: theme.colors.warning,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  name: {
    ...theme.typography.title,
    color: theme.colors.text,
  },
  tag: {
    ...theme.typography.caption,
    fontWeight: '700',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: theme.radius.pill,
  },
  tagPositive: {backgroundColor: '#DFF3E4', color: theme.colors.primaryDark},
  tagHumor: {backgroundColor: '#FDEBCC', color: theme.colors.warning},
  desc: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
    marginVertical: theme.spacing.xs,
  },
  list: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: theme.spacing.sm,
  },
  candidate: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    borderColor: theme.colors.border,
    backgroundColor: theme.colors.surface,
  },
  candidatePressed: {
    backgroundColor: theme.colors.bg,
  },
  candidateActive: {
    backgroundColor: theme.colors.primaryLight,
    borderColor: theme.colors.primary,
  },
  candidateName: {
    ...theme.typography.body,
    color: theme.colors.text,
    fontWeight: '600',
  },
  empty: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
  },
  voted: {
    ...theme.typography.body,
    color: theme.colors.textMuted,
    marginTop: theme.spacing.sm,
  },
  votedName: {
    color: theme.colors.primary,
    fontWeight: '700',
  },
});
