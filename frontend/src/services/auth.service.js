import api from './api';
export const authService = {
    login: (data) => api.post('/auth/login', data),
    register: (data) => api.post('/auth/register', data),
    logout: () => api.post('/auth/logout'),
    refresh: () => api.post('/auth/refresh'),
    forgotPassword: (email) => api.post('/auth/forgot-password', { email }),
    resetPassword: (data) => api.post('/auth/reset-password', data),
    verifyEmail: (id, hash, params) => api.get(`/auth/verify-email/${id}/${hash}`, { params }),
    resendVerification: () => api.post('/auth/email/resend'),
    getMe: () => api.get('/me'),
    updateMe: (data) => api.patch('/me', data),
    changePassword: (data) => api.patch('/me/password', data),
    getSessions: () => api.get('/me/sessions'),
    revokeSession: (id) => api.delete(`/me/sessions/${id}`),
};
