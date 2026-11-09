import { create } from 'zustand';
import { persist } from 'zustand/middleware';
export const useCartStore = create()(persist((set, get) => ({
    items: [],
    itemCount: 0,
    setItems: (items) => {
        const normalized = (items || []).map((i) => {
            const isAcc = Boolean(i.game_account_id || i.tai_khoan_game_id);
            return isAcc ? { ...i, quantity: 1 } : i;
        });
        set({ items: normalized, itemCount: normalized.reduce((sum, i) => sum + i.quantity, 0) });
    },
    addItem: (item) => {
        const isAccount = Boolean(item.game_account_id || item.tai_khoan_game_id);
        const existing = get().items.find((i) => {
            if (i.id === item.id) return true;
            if (isAccount) {
                const existingAccId = i.game_account_id || i.tai_khoan_game_id;
                const newAccId = item.game_account_id || item.tai_khoan_game_id;
                return existingAccId && newAccId && String(existingAccId) === String(newAccId);
            }
            return false;
        });

        if (existing) {
            // Nếu là tài khoản game, số lượng LUÔN LUÔN là 1, tuyệt đối không tăng lên 2
            if (isAccount || existing.game_account_id || existing.tai_khoan_game_id) {
                const items = get().items.map((i) => (i.id === existing.id ? { ...i, quantity: 1 } : i));
                set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
                return;
            }
            const items = get().items.map((i) => (i.id === item.id ? { ...i, quantity: i.quantity + item.quantity } : i));
            set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
            return;
        }
        const newItem = isAccount ? { ...item, quantity: 1 } : item;
        const items = [...get().items, newItem];
        set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
    },
    removeItem: (itemId) => {
        const items = get().items.filter((i) => i.id !== itemId);
        set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
    },
    updateQuantity: (itemId, quantity) => {
        const items = get().items.map((i) => {
            if (i.id === itemId) {
                const isAcc = Boolean(i.game_account_id || i.tai_khoan_game_id);
                return { ...i, quantity: isAcc ? 1 : Math.max(1, quantity) };
            }
            return i;
        });
        set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
    },
    clear: () => set({ items: [], itemCount: 0 }),
    clearCart: () => set({ items: [], itemCount: 0 }),
}), {
    name: 'cart-storage',
}));
