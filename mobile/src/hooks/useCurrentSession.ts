import {useCallback, useEffect, useState} from 'react';
import {sessionsApi} from '@/services/api/sessions';
import {apiErrorMessage} from '@/services/api/client';
import type {PeladaSession} from '@/types';

type State = {
  session: PeladaSession | null;
  loading: boolean;
  error: string | null;
};

export function useCurrentSession(peladaId: number) {
  const [state, setState] = useState<State>({session: null, loading: true, error: null});

  const load = useCallback(async () => {
    setState(prev => ({...prev, loading: true, error: null}));
    try {
      const session = await sessionsApi.current(peladaId);
      setState({session, loading: false, error: null});
    } catch (err) {
      setState({session: null, loading: false, error: apiErrorMessage(err)});
    }
  }, [peladaId]);

  useEffect(() => {
    void load();
  }, [load]);

  return {...state, reload: load};
}
