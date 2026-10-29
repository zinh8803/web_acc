import axios from 'axios';

const api = axios.create({
  // Set VITE_API_BASE_URL when the API is hosted on a different origin.
  baseURL: import.meta.env.VITE_API_URL || import.meta.env.VITE_API_BASE_URL || '/api/v1',
  timeout: 20000,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
  const isAdminRequest = config.url?.startsWith('/admin/');
  const token = isAdminRequest
    ? localStorage.getItem('admin_access_token') || localStorage.getItem('admin_token')
    : localStorage.getItem('access_token') || localStorage.getItem('admin_access_token') || localStorage.getItem('admin_token');

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

export default api;
