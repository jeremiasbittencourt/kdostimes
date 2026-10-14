import {apiClient} from './client';
import type {Draw} from '@/types';

export const drawsApi = {
  async generate(sessionId: number): Promise<Draw> {
    const {data} = await apiClient.post<{data: Draw}>(`/sessions/${sessionId}/draw`);
    return data.data;
  },

  async getPublished(sessionId: number): Promise<Draw | null> {
    try {
      const {data} = await apiClient.get<{data: Draw}>(`/sessions/${sessionId}/draw`);
      return data.data;
    } catch (err) {
      const status = (err as {response?: {status?: number}})?.response?.status;
      if (status === 404) {
        return null;
      }
      throw err;
    }
  },

  async publish(drawId: number): Promise<Draw> {
    const {data} = await apiClient.post<{data: Draw}>(`/draws/${drawId}/publish`);
    return data.data;
  },
};
