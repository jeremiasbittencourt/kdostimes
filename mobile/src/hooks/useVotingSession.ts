import {useCallback, useEffect, useState} from 'react';
import {votingApi} from '@/services/api/voting';
import {apiErrorMessage} from '@/services/api/client';
import type {VotingSession} from '@/types';

type State = {
  voting: VotingSession | null;
  loading: boolean;
  error: string | null;
};

export function useVotingSession(sessionId: number) {
  const [state, setState] = useState<State>({voting: null, loading: true, error: null});

  const load = useCallback(async () => {
    setState(prev => ({...prev, loading: true, error: null}));
    try {
      const voting = await votingApi.forSession(sessionId);
      setState({voting, loading: false, error: null});
    } catch (err) {
      setState({voting: null, loading: false, error: apiErrorMessage(err)});
    }
  }, [sessionId]);

  useEffect(() => {
    void load();
  }, [load]);

  return {...state, reload: load};
}
