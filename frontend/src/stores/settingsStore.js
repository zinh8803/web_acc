import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import api from '../services/api';

export const useSettingsStore = create()(
    persist(
        (set, get) => ({
            settings: null,
            siteName: '',
            isLoading: false,
            fetchSettings: async () => {
                try {
                    set({ isLoading: true });
                    const res = await api.get('/settings/public');
                    if (res.data?.data) {
                        const data = res.data.data;
                        const name = data.general_settings?.site_name || '';
                        set({ settings: data, siteName: name, isLoading: false });
                        return data;
                    }
                } catch (e) {
                    console.error('Failed to load settings:', e);
                } finally {
                    set({ isLoading: false });
                }
            },
            setSettings: (newSettings) => {
                const current = get().settings || {};
                const merged = { ...current, ...newSettings };
                const name = merged.general_settings?.site_name || get().siteName || '';
                set({ settings: merged, siteName: name });
            },
        }),
        {
            name: 'site-settings-storage',
            partialize: (state) => ({
                settings: state.settings,
                siteName: state.siteName,
            }),
        }
    )
);

