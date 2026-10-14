import {apiClient} from './client';
import type {PeladaSession, PresenceConfirmation, PresenceStatus} from '@/types';

export const sessionsApi = {
  async current(peladaId: number): Promise<PeladaSession | null> {
    const {data} = await apiClient.get<{session: PeladaSession | null}>(
      `/peladas/${peladaId}/sessions/current`,
    );
    return data.session;
  },

  async confirmPresence(sessionId: number, status: PresenceStatus): Promise<PresenceConfirmation> {
    const {data} = await apiClient.post<{data: PresenceConfirmation}>(
      `/sessions/${sessionId}/presence`,
      {status},
    );
    return data.data;
  },
};
