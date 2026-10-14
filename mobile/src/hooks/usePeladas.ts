import {useCallback, useEffect, useState} from 'react';
import {peladasApi} from '@/services/api/peladas';
import {apiErrorMessage} from '@/services/api/client';
import type {Pelada} from '@/types';

type State = {
  peladas: Pelada[];
  loading: boolean;
  error: string | null;
};

export function usePeladas() {
  const [state, setState] = useState<State>({peladas: [], loading: true, error: null});

  const load = useCallback(async () => {
    setState(prev => ({...prev, loading: true, error: null}));
    try {
      const peladas = await peladasApi.list();
      setState({peladas, loading: false, error: null});
    } catch (err) {
      setState({peladas: [], loading: false, error: apiErrorMessage(err)});
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  return {...state, reload: load};
}
