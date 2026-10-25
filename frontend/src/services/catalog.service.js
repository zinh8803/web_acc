import api from './api';
export const catalogService = {
    // Categories
    getCategories: (params) => api.get('/categories', { params }),
    // Games
    getGames: () => api.get('/games'),
    // Products
    getProducts: (params) => api.get('/products', { params }),
    getProduct: (slug) => api.get(`/products/${slug}`),
    getProductReviews: (productId, params) => api.get(`/products/${productId}/reviews`, { params }),
};
