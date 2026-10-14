import {create} from 'zustand';
import {secureToken} from '@/services/storage/secureToken';
import {setUnauthorizedHandler} from '@/services/api/client';
import {authApi} from '@/services/api/auth';
import type {User} from '@/types';

type AuthStatus = 'idle' | 'hydrating' | 'authenticated' | 'unauthenticated';

type AuthState = {
  status: AuthStatus;
  user: User | null;
  token: string | null;
  hydrate: () => Promise<void>;
  setSession: (payload: {user: User; token: string}) => Promise<void>;
  clearSession: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

export const useAuthStore = create<AuthState>((set, get) => {
  setUnauthorizedHandler(() => {
    void get().clearSession();
  });

  return {
    status: 'idle',
    user: null,
    token: null,

    async hydrate() {
      set({status: 'hydrating'});
      const token = await secureToken.load();
      if (!token) {
        set({status: 'unauthenticated', token: null, user: null});
        return;
      }
      try {
        const user = await authApi.me();
        set({status: 'authenticated', token, user});
      } catch {
        await secureToken.clear();
        set({status: 'unauthenticated', token: null, user: null});
      }
    },

    async setSession({user, token}) {
      await secureToken.save(token);
      set({status: 'authenticated', user, token});
    },

    async clearSession() {
      await secureToken.clear();
      set({status: 'unauthenticated', user: null, token: null});
    },

    async refreshUser() {
      if (get().status !== 'authenticated') {
        return;
      }
      const user = await authApi.me();
      set({user});
    },
  };
});
