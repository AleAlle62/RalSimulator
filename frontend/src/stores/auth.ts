import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api, ApiError } from '@/services/api';
import type { User } from '@/types/simulation';

/**
 * Who is signed in, if anyone.
 *
 * Signing in is never required to use the calculator; it only exists so a simulation can be
 * found again later. So the store's resting state is "guest", not "unknown", and nothing in the
 * app blocks on it having resolved.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const isAuthenticated = computed(() => user.value !== null);

  async function register(payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) {
    const response = await api.post<{ user: User }>('/api/register', payload);
    user.value = response.user;
  }

  async function login(payload: { email: string; password: string }) {
    const response = await api.post<{ user: User }>('/api/login', payload);
    user.value = response.user;
  }

  async function logout() {
    await api.post('/api/logout');
    user.value = null;
  }

  /**
   * Called once at start-up to pick up an existing session cookie. A 401 here is the normal
   * answer for a visitor who never signed in, so it resolves to "guest" rather than surfacing
   * as an error; anything else is a real failure and is left to propagate.
   */
  async function restore() {
    try {
      const response = await api.get<{ user: User }>('/api/me');
      user.value = response.user;
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        user.value = null;
        return;
      }
      throw error;
    }
  }

  return { user, isAuthenticated, register, login, logout, restore };
});
