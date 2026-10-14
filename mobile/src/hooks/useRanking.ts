import {useCallback, useEffect, useState} from 'react';
import {rankingApi} from '@/services/api/ranking';
import {apiErrorMessage} from '@/services/api/client';
import type {RankingPeriod, RankingRow} from '@/types';

type State = {
  rows: RankingRow[];
  loading: boolean;
  error: string | null;
};

export function useRanking(peladaId: number, period: RankingPeriod) {
  const [state, setState] = useState<State>({rows: [], loading: true, error: null});

  const load = useCallback(async () => {
    setState(prev => ({...prev, loading: true, error: null}));
    try {
      const rows = await rankingApi.forPelada(peladaId, period);
      setState({rows, loading: false, error: null});
    } catch (err) {
      setState({rows: [], loading: false, error: apiErrorMessage(err)});
    }
  }, [peladaId, period]);

  useEffect(() => {
    void load();
  }, [load]);

  return {...state, reload: load};
}
