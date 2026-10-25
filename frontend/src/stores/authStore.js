import { create } from 'zustand';
import { persist } from 'zustand/middleware';
export const useAuthStore = create()(persist((set) => ({
    user: null,
    accessToken: null,
    isAuthenticated: false,
    setUser: (user) => set({ user, isAuthenticated: !!user }),
    setAccessToken: (token) => {
        if (token) {
            localStorage.setItem('access_token', token);
        }
        else {
            localStorage.removeItem('access_token');
        }
        set({ accessToken: token });
    },
    setAuth: (user, token) => {
        localStorage.setItem('access_token', token);
        set({ user, accessToken: token, isAuthenticated: true });
    },
    logout: () => {
        localStorage.removeItem('access_token');
        set({ user: null, accessToken: null, isAuthenticated: false });
    },
}), {
    name: 'auth-storage',
    partialize: (state) => ({ user: state.user, isAuthenticated: state.isAuthenticated }),
}));
