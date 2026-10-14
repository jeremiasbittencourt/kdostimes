import {apiClient} from './client';
import type {RankingPeriod, RankingRow} from '@/types';

export const rankingApi = {
  async forPelada(peladaId: number, period: RankingPeriod): Promise<RankingRow[]> {
    const {data} = await apiClient.get<{period: string; ranking: RankingRow[]}>(
      `/peladas/${peladaId}/ranking`,
      {params: {period}},
    );
    return data.ranking;
  },

  async forUser(
    peladaId: number,
    userId: number,
    period: RankingPeriod,
  ): Promise<{user: {id: number; name: string; avatar_url: string | null}; entry: RankingRow | null}> {
    const {data} = await apiClient.get(
      `/peladas/${peladaId}/ranking/${userId}`,
      {params: {period}},
    );
    return data;
  },
};
