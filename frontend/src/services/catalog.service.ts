import api from './api';
import type {
  ApiResponse,
  PaginatedResponse,
  Product,
  ProductFilterParams,
  Category,
  Game,
  Review,
} from '../types';

export const catalogService = {
  // Categories
  getCategories: (params?: { type?: 'ACCOUNT' | 'SERVICE'; game_id?: number }) =>
    api.get<ApiResponse<Category[]>>('/categories', { params }),

  // Games
  getGames: () => api.get<ApiResponse<Game[]>>('/games'),

  // Products
  getProducts: (params: ProductFilterParams) =>
    api.get<PaginatedResponse<Product>>('/products', { params }),

  getProduct: (slug: string) =>
    api.get<ApiResponse<Product>>(`/products/${slug}`),

  getProductReviews: (productId: number, params?: { page?: number; limit?: number }) =>
    api.get<PaginatedResponse<Review>>(`/products/${productId}/reviews`, { params }),
};
