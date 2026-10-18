import api from './api';
import type { ApiResponse, PaginatedResponse, Order, Payment } from '../types';

export interface CheckoutData {
  items: Array<{ variant_id: number; quantity: number }>;
  coupon_code?: string;
  payment_method_code: string;
  customer_note?: string;
  // For service orders: additional form data per item
  service_form_data?: Record<string, unknown>;
}

export const orderService = {
  // Create order
  checkout: (data: CheckoutData) =>
    api.post<ApiResponse<Order>>('/orders', data),

  // List orders
  getOrders: (params?: { status?: string; page?: number; limit?: number }) =>
    api.get<PaginatedResponse<Order>>('/orders', { params }),

  // Get order detail
  getOrder: (code: string) => api.get<ApiResponse<Order>>(`/orders/${code}`),

  // Cancel order
  cancelOrder: (code: string, reason?: string) =>
    api.post<ApiResponse<Order>>(`/orders/${code}/cancel`, { reason }),

  // View credentials (requires password re-entry)
  getCredentials: (code: string, itemId: number, password: string) =>
    api.post<
      ApiResponse<{ username: string; password: string; extra_info: Record<string, string> }>
    >(`/orders/${code}/items/${itemId}/credentials`, { password }),

  // Create payment for order
  createPayment: (code: string, method_code: string) =>
    api.post<ApiResponse<Payment>>(`/orders/${code}/payments`, { method_code }),

  // Get payment status
  getPaymentStatus: (paymentCode: string) =>
    api.get<ApiResponse<Payment>>(`/payments/${paymentCode}/status`),
};
