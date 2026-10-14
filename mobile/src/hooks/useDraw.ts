import {useCallback, useEffect, useState} from 'react';
import {drawsApi} from '@/services/api/draws';
import {apiErrorMessage} from '@/services/api/client';
import type {Draw} from '@/types';

type State = {
  draw: Draw | null;
  loading: boolean;
  error: string | null;
};

export function useDraw(sessionId: number) {
  const [state, setState] = useState<State>({draw: null, loading: true, error: null});

  const loadPublished = useCallback(async () => {
    setState(prev => ({...prev, loading: true, error: null}));
    try {
      const draw = await drawsApi.getPublished(sessionId);
      setState({draw, loading: false, error: null});
    } catch (err) {
      setState({draw: null, loading: false, error: apiErrorMessage(err)});
    }
  }, [sessionId]);

  useEffect(() => {
    void loadPublished();
  }, [loadPublished]);

  const generate = useCallback(async (): Promise<Draw> => {
    const draw = await drawsApi.generate(sessionId);
    setState({draw, loading: false, error: null});
    return draw;
  }, [sessionId]);

  const publish = useCallback(async (drawId: number): Promise<Draw> => {
    const draw = await drawsApi.publish(drawId);
    setState({draw, loading: false, error: null});
    return draw;
  }, []);

  return {...state, reload: loadPublished, generate, publish};
}
