import api from './api';
import type { ApiResponse, Cart, CartItem } from '../types';

export const cartService = {
  getCart: () => api.get<ApiResponse<Cart>>('/cart'),

  addItem: (variant_id: number, quantity = 1) =>
    api.post<ApiResponse<CartItem>>('/cart/items', { variant_id, quantity }),

  updateItem: (itemId: number, quantity: number) =>
    api.patch<ApiResponse<CartItem>>(`/cart/items/${itemId}`, { quantity }),

  removeItem: (itemId: number) => api.delete(`/cart/items/${itemId}`),

  validateCoupon: (code: string, order_total: number) =>
    api.post<ApiResponse<{ discount_amount: number; coupon: import('../types').Coupon }>>('/coupons/validate', {
      code,
      order_total,
    }),
};
