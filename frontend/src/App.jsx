import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { Suspense } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { ConfigProvider, Spin, theme } from 'antd';
import viVN from 'antd/locale/vi_VN';
// Layouts
import UserLayout from './components/layouts/UserLayout';
import AdminLayout from './components/layouts/AdminLayout';
// Guards
import ProtectedRoute from './components/guards/ProtectedRoute';
import AdminRoute from './components/guards/AdminRoute';
// Public pages
const HomePage = React.lazy(() => import('./pages/HomePage'));
const CatalogPage = React.lazy(() => import('./pages/catalog/CatalogPage'));
const ProductDetailPage = React.lazy(() => import('./pages/catalog/ProductDetailPage'));
const LoginPage = React.lazy(() => import('./pages/auth/LoginPage'));
const RegisterPage = React.lazy(() => import('./pages/auth/RegisterPage'));
const ForgotPasswordPage = React.lazy(() => import('./pages/auth/ForgotPasswordPage'));
const ResetPasswordPage = React.lazy(() => import('./pages/auth/ResetPasswordPage'));
// User protected pages
const CartPage = React.lazy(() => import('./pages/CartPage'));
const CheckoutPage = React.lazy(() => import('./pages/CheckoutPage'));
const PaymentPage = React.lazy(() => import('./pages/PaymentPage'));
const VnPayReturnPage = React.lazy(() => import('./pages/VnPayReturnPage'));
const ZaloPayReturnPage = React.lazy(() => import('./pages/ZaloPayReturnPage'));
const OrdersPage = React.lazy(() => import('./pages/order/OrdersPage'));
const OrderDetailPage = React.lazy(() => import('./pages/order/OrderDetailPage'));
const WarrantyClaimsPage = React.lazy(() => import('./pages/warranty/WarrantyClaimsPage'));
const WarrantyClaimDetailPage = React.lazy(() => import('./pages/warranty/WarrantyClaimDetailPage'));
const ProfilePage = React.lazy(() => import('./pages/ProfilePage'));
// Admin pages
const AdminDashboard = React.lazy(() => import('./pages/admin/DashboardPage'));
const AdminCategoriesPage = React.lazy(() => import('./pages/admin/CategoriesPage'));
const AdminGamesPage = React.lazy(() => import('./pages/admin/GamesPage'));
const AdminProductsPage = React.lazy(() => import('./pages/admin/ProductsPage'));
const AdminProductFormPage = React.lazy(() => import('./pages/admin/ProductFormPage'));
const AdminInventoryPage = React.lazy(() => import('./pages/admin/InventoryPage'));
const AdminOrdersPage = React.lazy(() => import('./pages/admin/OrdersPage'));
const AdminOrderDetailPage = React.lazy(() => import('./pages/admin/OrderDetailPage'));
const AdminServiceRequestsPage = React.lazy(() => import('./pages/admin/ServiceRequestsPage'));
const AdminWarrantyPage = React.lazy(() => import('./pages/admin/WarrantyPage'));
const AdminCouponsPage = React.lazy(() => import('./pages/admin/CouponsPage'));
const AdminUsersPage = React.lazy(() => import('./pages/admin/UsersPage'));
const AdminStaffPage = React.lazy(() => import('./pages/admin/StaffPage'));
const AdminAuditLogsPage = React.lazy(() => import('./pages/admin/AuditLogsPage'));
const AdminSettingsPage = React.lazy(() => import('./pages/admin/SettingsPage'));
const AdminReportsPage = React.lazy(() => import('./pages/admin/ReportsPage'));
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 5 * 60 * 1000, // 5 min
            retry: 1,
        },
    },
});
const antTheme = {
    algorithm: theme.defaultAlgorithm,
    token: {
        colorPrimary: '#6366f1', // indigo
        colorBgBase: '#f5f7fb',
        colorBgContainer: '#ffffff',
        colorBgElevated: '#ffffff',
        colorText: '#1e293b',
        colorTextSecondary: '#64748b',
        colorBorder: '#e2e8f0',
        borderRadius: 8,
        fontFamily: "'Inter', 'Segoe UI', sans-serif",
    },
};
const PageLoader = () => (_jsx("div", { style: { display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }, children: _jsx(Spin, { size: "large" }) }));
function App() {
        return (_jsxs(QueryClientProvider, { client: queryClient, children: [_jsx(ConfigProvider, { locale: viVN, theme: antTheme, children: _jsx(BrowserRouter, { children: _jsx(Suspense, { fallback: _jsx(PageLoader, {}), children: _jsxs(Routes, { children: [_jsxs(Route, { path: "/admin", element: _jsx(AdminRoute, { children: _jsx(AdminLayout, {}) }), children: [_jsx(Route, { index: true, element: _jsx(AdminDashboard, {}) }), _jsx(Route, { path: "categories", element: _jsx(AdminCategoriesPage, {}) }), _jsx(Route, { path: "games", element: _jsx(AdminGamesPage, {}) }), _jsx(Route, { path: "products", element: _jsx(AdminProductsPage, {}) }), _jsx(Route, { path: "products/create", element: _jsx(AdminProductFormPage, {}) }), _jsx(Route, { path: "products/:id/edit", element: _jsx(AdminProductFormPage, {}) }), _jsx(Route, { path: "inventory", element: _jsx(AdminInventoryPage, {}) }), _jsx(Route, { path: "orders", element: _jsx(AdminOrdersPage, {}) }), _jsx(Route, { path: "orders/:code", element: _jsx(AdminOrderDetailPage, {}) }), _jsx(Route, { path: "service-requests", element: _jsx(AdminServiceRequestsPage, {}) }), _jsx(Route, { path: "warranty", element: _jsx(AdminWarrantyPage, {}) }), _jsx(Route, { path: "coupons", element: _jsx(AdminCouponsPage, {}) }), _jsx(Route, { path: "users", element: _jsx(AdminUsersPage, {}) }), _jsx(Route, { path: "staff", element: _jsx(AdminStaffPage, {}) }), _jsx(Route, { path: "audit-logs", element: _jsx(AdminAuditLogsPage, {}) }), _jsx(Route, { path: "reports", element: _jsx(AdminReportsPage, {}) }), _jsx(Route, { path: "settings", element: _jsx(AdminSettingsPage, {}) })] }), _jsxs(Route, { path: "/", element: _jsx(UserLayout, {}), children: [_jsx(Route, { index: true, element: _jsx(HomePage, {}) }), _jsx(Route, { path: "catalog", element: _jsx(CatalogPage, {}) }), _jsx(Route, { path: "catalog/:slug", element: _jsx(CatalogPage, {}) }), _jsx(Route, { path: "products/:slug", element: _jsx(ProductDetailPage, {}) }), _jsx(Route, { path: "login", element: _jsx(LoginPage, {}) }), _jsx(Route, { path: "register", element: _jsx(RegisterPage, {}) }), _jsx(Route, { path: "forgot-password", element: _jsx(ForgotPasswordPage, {}) }), _jsx(Route, { path: "reset-password", element: _jsx(ResetPasswordPage, {}) }), _jsxs(Route, { element: _jsx(ProtectedRoute, {}), children: [_jsx(Route, { path: "cart", element: _jsx(CartPage, {}) }), _jsx(Route, { path: "checkout", element: _jsx(CheckoutPage, {}) }), _jsx(Route, { path: "payment/:orderCode", element: _jsx(PaymentPage, {}) }), _jsx(Route, { path: "vnpay_return", element: _jsx(VnPayReturnPage, {}) }), _jsx(Route, { path: "zalo_return", element: _jsx(ZaloPayReturnPage, {}) }), _jsx(Route, { path: "orders", element: _jsx(OrdersPage, {}) }), _jsx(Route, { path: "orders/:code", element: _jsx(OrderDetailPage, {}) }), _jsx(Route, { path: "warranty-claims", element: _jsx(WarrantyClaimsPage, {}) }), _jsx(Route, { path: "warranty-claims/:code", element: _jsx(WarrantyClaimDetailPage, {}) }), _jsx(Route, { path: "profile", element: _jsx(ProfilePage, {}) })] })] }), _jsx(Route, { path: "*", element: _jsx(Navigate, { to: "/", replace: true }) })] }) }) }) }), _jsx(ReactQueryDevtools, { initialIsOpen: false })] }));
}
export default App;
