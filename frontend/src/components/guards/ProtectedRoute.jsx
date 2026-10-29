import React from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuthStore } from '../../stores/authStore';

const ProtectedRoute = () => {
    const { isAuthenticated } = useAuthStore();
    const location = useLocation();
    const hasToken = !!(
        localStorage.getItem('access_token') ||
        localStorage.getItem('admin_access_token') ||
        localStorage.getItem('admin_token')
    );

    if (!isAuthenticated && !hasToken) {
        return <Navigate to="/login" state={{ from: location.pathname }} replace />;
    }
    return <Outlet />;
};

export default ProtectedRoute;
