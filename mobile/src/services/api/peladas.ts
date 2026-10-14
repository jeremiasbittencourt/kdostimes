import {apiClient} from './client';
import type {Pelada} from '@/types';

export type CreatePeladaInput = {
  name: string;
  location?: string | null;
  day_of_week: number;
  time: string;
  players_per_team: number;
  has_fixed_goalkeepers: boolean;
  confirmation_deadline_hours: number;
  enabled_voting_category_codes?: string[];
};

export type JoinPeladaInput = {short_code?: string; invite_token?: string};

export const peladasApi = {
  async list(): Promise<Pelada[]> {
    const {data} = await apiClient.get<{data: Pelada[]}>('/peladas');
    return data.data;
  },

  async show(id: number): Promise<Pelada> {
    const {data} = await apiClient.get<{data: Pelada}>(`/peladas/${id}`);
    return data.data;
  },

  async create(input: CreatePeladaInput): Promise<Pelada> {
    const {data} = await apiClient.post<{data: Pelada}>('/peladas', input);
    return data.data;
  },

  async join(input: JoinPeladaInput): Promise<Pelada> {
    const {data} = await apiClient.post<{data: Pelada}>('/peladas/join', input);
    return data.data;
  },

  async updateMember(
    peladaId: number,
    userId: number,
    input: {is_fixed_goalkeeper?: boolean; role?: 'admin' | 'member'},
  ): Promise<void> {
    await apiClient.put(`/peladas/${peladaId}/members/${userId}`, input);
  },

  async removeMember(peladaId: number, userId: number): Promise<void> {
    await apiClient.delete(`/peladas/${peladaId}/members/${userId}`);
  },

  async createInvite(peladaId: number): Promise<{token: string; expires_at: string}> {
    const {data} = await apiClient.post<{data: {token: string; expires_at: string}}>(
      `/peladas/${peladaId}/invites`,
    );
    return data.data;
  },
};
