import api from './api';
import type { User, ApiResponse } from '../types';

export interface LoginData {
  email: string;
  password: string;
}

export interface RegisterData {
  email: string;
  phone?: string;
  full_name: string;
  password: string;
  password_confirmation: string;
}

export const authService = {
  login: (data: LoginData) =>
    api.post<ApiResponse<{ access_token: string; user: User }>>('/auth/login', data),

  register: (data: RegisterData) =>
    api.post<ApiResponse<{ message: string }>>('/auth/register', data),

  logout: () => api.post<ApiResponse<null>>('/auth/logout'),

  refresh: () =>
    api.post<ApiResponse<{ access_token: string }>>('/auth/refresh'),

  forgotPassword: (email: string) =>
    api.post<ApiResponse<{ message: string }>>('/auth/forgot-password', { email }),

  resetPassword: (data: { token: string; email: string; password: string; password_confirmation: string }) =>
    api.post<ApiResponse<{ message: string }>>('/auth/reset-password', data),

  verifyEmail: (id: string, hash: string, params: Record<string, string>) =>
    api.get<ApiResponse<{ message: string }>>(`/auth/verify-email/${id}/${hash}`, { params }),

  resendVerification: () =>
    api.post<ApiResponse<{ message: string }>>('/auth/email/resend'),

  getMe: () => api.get<ApiResponse<User>>('/me'),

  updateMe: (data: Partial<User>) => api.patch<ApiResponse<User>>('/me', data),

  changePassword: (data: { current_password: string; password: string; password_confirmation: string }) =>
    api.patch<ApiResponse<{ message: string }>>('/me/password', data),

  getSessions: () => api.get('/me/sessions'),

  revokeSession: (id: number) => api.delete(`/me/sessions/${id}`),
};
