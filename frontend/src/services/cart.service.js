import api from './api';
export const cartService = {
    getCart: () => api.get('/cart'),
    addItem: (variant_id, quantity = 1) => api.post('/cart/items', { variant_id, quantity }),
    updateItem: (itemId, quantity) => api.patch(`/cart/items/${itemId}`, { quantity }),
    removeItem: (itemId) => api.delete(`/cart/items/${itemId}`),
    validateCoupon: (code, order_total) => api.post('/coupons/validate', {
        code,
        order_total,
    }),
};
