import React, { Suspense } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
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
const OrdersPage = React.lazy(() => import('./pages/order/OrdersPage'));
const OrderDetailPage = React.lazy(() => import('./pages/order/OrderDetailPage'));
const WarrantyClaimsPage = React.lazy(() => import('./pages/warranty/WarrantyClaimsPage'));
const WarrantyClaimDetailPage = React.lazy(() => import('./pages/warranty/WarrantyClaimDetailPage'));
const ProfilePage = React.lazy(() => import('./pages/ProfilePage'));

// Admin pages
const AdminDashboard = React.lazy(() => import('./pages/admin/DashboardPage'));
const AdminCategoriesPage = React.lazy(() => import('./pages/admin/CategoriesPage'));
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
const AdminBannersPage = React.lazy(() => import('./pages/admin/BannersPage'));
const AdminSettingsPage = React.lazy(() => import('./pages/admin/SettingsPage'));
const AdminReportsPage = React.lazy(() => import('./pages/admin/ReportsPage'));

const antTheme = {
    algorithm: theme.defaultAlgorithm,
    token: {
        colorPrimary: '#6366f1',
        colorBgBase: '#f5f7fb',
        colorBgContainer: '#ffffff',
        colorBgElevated: '#ffffff',
        colorText: '#1e293b',
        colorTextSecondary: '#64748b',
        colorBorder: '#e2e8f0',
        borderRadius: 8,
        fontFamily: "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
    },
};

const PageLoader = () => (
    <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}>
        <Spin size="large" />
    </div>
);

function App() {
    return (
        <ConfigProvider locale={viVN} theme={antTheme}>
            <BrowserRouter>
                <Suspense fallback={<PageLoader />}>
                    <Routes>
                            {/* Admin routes */}
                            <Route path="/admin" element={<AdminRoute><AdminLayout /></AdminRoute>}>
                                <Route index element={<AdminDashboard />} />
                                <Route path="categories" element={<AdminCategoriesPage />} />
                                <Route path="games" element={<Navigate to="/admin/categories" replace />} />
                                <Route path="products" element={<AdminProductsPage />} />
                                <Route path="products/create" element={<AdminProductFormPage />} />
                                <Route path="products/:id/edit" element={<AdminProductFormPage />} />
                                <Route path="inventory" element={<AdminInventoryPage />} />
                                <Route path="orders" element={<AdminOrdersPage />} />
                                <Route path="orders/:code" element={<AdminOrderDetailPage />} />
                                <Route path="service-requests" element={<AdminServiceRequestsPage />} />
                                <Route path="warranty" element={<AdminWarrantyPage />} />
                                <Route path="coupons" element={<AdminCouponsPage />} />
                                <Route path="users" element={<AdminUsersPage />} />
                                <Route path="staff" element={<AdminStaffPage />} />
                                <Route path="reports" element={<AdminReportsPage />} />
                                <Route path="banners" element={<AdminBannersPage />} />
                                <Route path="settings" element={<AdminSettingsPage />} />
                            </Route>

                            {/* Public & Customer routes */}
                            <Route path="/" element={<UserLayout />}>
                                <Route index element={<HomePage />} />
                                <Route path="catalog" element={<CatalogPage />} />
                                <Route path="catalog/:slug" element={<CatalogPage />} />
                                <Route path="products/:slug" element={<ProductDetailPage />} />
                                <Route path="login" element={<LoginPage />} />
                                <Route path="register" element={<RegisterPage />} />
                                <Route path="forgot-password" element={<ForgotPasswordPage />} />
                                <Route path="reset-password" element={<ResetPasswordPage />} />

                                {/* Protected User routes */}
                                <Route element={<ProtectedRoute />}>
                                    <Route path="cart" element={<CartPage />} />
                                    <Route path="checkout" element={<CheckoutPage />} />
                                    <Route path="payment/:orderCode" element={<PaymentPage />} />
                                    <Route path="vnpay_return" element={<VnPayReturnPage />} />
                                    <Route path="orders" element={<OrdersPage />} />
                                    <Route path="orders/:code" element={<OrderDetailPage />} />
                                    <Route path="warranty-claims" element={<WarrantyClaimsPage />} />
                                    <Route path="warranty-claims/:code" element={<WarrantyClaimDetailPage />} />
                                    <Route path="profile" element={<ProfilePage />} />
                                </Route>
                            </Route>

                            {/* Fallback */}
                            <Route path="*" element={<Navigate to="/" replace />} />
                        </Routes>
                    </Suspense>
                </BrowserRouter>
            </ConfigProvider>
    );
}

export default App;
