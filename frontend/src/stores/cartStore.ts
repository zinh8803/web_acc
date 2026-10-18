import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { CartItem } from '../types';

interface CartState {
  items: CartItem[];
  itemCount: number;
  setItems: (items: CartItem[]) => void;
  addItem: (item: CartItem) => void;
  removeItem: (itemId: number) => void;
  updateQuantity: (itemId: number, quantity: number) => void;
  clear: () => void;
  clearCart: () => void;
}

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      items: [],
      itemCount: 0,
      setItems: (items) => set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) }),
      addItem: (item) => {
        const existing = get().items.find((i) => i.id === item.id);
        if (existing) {
          const items = get().items.map((i) =>
            i.id === item.id ? { ...i, quantity: i.quantity + item.quantity } : i
          );
          set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
          return;
        }
        const items = [...get().items, item];
        set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
      },
      removeItem: (itemId) => {
        const items = get().items.filter((i) => i.id !== itemId);
        set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
      },
      updateQuantity: (itemId, quantity) => {
        const items = get().items.map((i) => (i.id === itemId ? { ...i, quantity } : i));
        set({ items, itemCount: items.reduce((sum, i) => sum + i.quantity, 0) });
      },
      clear: () => set({ items: [], itemCount: 0 }),
      clearCart: () => set({ items: [], itemCount: 0 }),
    }),
    {
      name: 'cart-storage',
    }
  )
);
