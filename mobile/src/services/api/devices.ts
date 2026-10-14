import {apiClient} from './client';

export const devicesApi = {
  async register(token: string, platform: 'ios' | 'android'): Promise<{id: number}> {
    const {data} = await apiClient.post<{device_token: {id: number}}>('/me/device-tokens', {
      token,
      platform,
    });
    return data.device_token;
  },

  async unregister(id: number): Promise<void> {
    await apiClient.delete(`/me/device-tokens/${id}`);
  },
};
