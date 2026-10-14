import {apiClient} from './client';
import type {AuthPayload, User} from '@/types';

export const authApi = {
  async register(input: {name: string; email: string; password: string; password_confirmation: string}): Promise<AuthPayload> {
    const {data} = await apiClient.post<AuthPayload>('/auth/register', input);
    return data;
  },

  async login(input: {email: string; password: string; device_name?: string}): Promise<AuthPayload> {
    const {data} = await apiClient.post<AuthPayload>('/auth/login', input);
    return data;
  },

  async forgotPassword(email: string): Promise<void> {
    await apiClient.post('/auth/forgot-password', {email});
  },

  async logout(deviceToken?: string): Promise<void> {
    await apiClient.post('/auth/logout', deviceToken ? {device_token: deviceToken} : {});
  },

  async me(): Promise<User> {
    const {data} = await apiClient.get<User>('/me');
    return data;
  },

  async updateMe(input: Partial<Pick<User, 'name' | 'skill_rating' | 'avatar_url'>> & {
    positions?: Array<{position: string; priority: 1 | 2}>;
  }): Promise<User> {
    const {data} = await apiClient.put<User>('/me', input);
    return data;
  },

  async updateNotificationPreferences(
    preferences: Array<{event_type: string; enabled: boolean}>,
  ): Promise<{preferences: Array<{event_type: string; enabled: boolean}>}> {
    const {data} = await apiClient.put('/me/notification-preferences', {preferences});
    return data;
  },
};
