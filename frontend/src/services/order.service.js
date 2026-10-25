import api from './api';
export const orderService = {
    // Create order
    checkout: (data) => api.post('/orders', data),
    // List orders
    getOrders: (params) => api.get('/orders', { params }),
    // Get order detail
    getOrder: (code) => api.get(`/orders/${code}`),
    // Cancel order
    cancelOrder: (code, reason) => api.post(`/orders/${code}/cancel`, { reason }),
    // View credentials (requires password re-entry)
    getCredentials: (code, itemId, password) => api.post(`/orders/${code}/items/${itemId}/credentials`, { password }),
    // Create payment for order
    createPayment: (code) => api.get(`/orders/${code}/payments`),
    // Get payment status
    getPaymentStatus: (code) => api.get(`/orders/${code}/payments/status`),
};
