import {apiClient} from './client';
import type {VotingSession} from '@/types';

export type VotingResults = Record<string, Array<{voted_user_id: number; total: number}>>;

export const votingApi = {
  async forSession(sessionId: number): Promise<VotingSession | null> {
    const {data} = await apiClient.get<{voting: VotingSession | null}>(
      `/sessions/${sessionId}/voting`,
    );
    return data.voting;
  },

  async castVote(
    votingSessionId: number,
    input: {category_id: number; voted_user_id: number},
  ): Promise<void> {
    await apiClient.post(`/voting-sessions/${votingSessionId}/votes`, input);
  },

  async results(votingSessionId: number): Promise<VotingResults> {
    const {data} = await apiClient.get<{results: VotingResults}>(
      `/voting-sessions/${votingSessionId}/results`,
    );
    return data.results;
  },
};
