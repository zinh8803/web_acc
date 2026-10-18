import axios from 'axios';
import {
  MOCK_GAMES,
  MOCK_CATEGORIES,
  MOCK_PRODUCTS,
  MOCK_BANNERS,
  MOCK_PAYMENT_METHODS,
  MOCK_SITE_SETTINGS,
  MOCK_COUPONS,
  INITIAL_ORDERS,
} from './mockData';

// Local storage keys for state persistence
const STORAGE_ORDERS_KEY = 'mock_orders_data';
const STORAGE_WARRANTY_KEY = 'mock_warranty_claims';

function getStoredOrders(): any[] {
  try {
    const raw = localStorage.getItem(STORAGE_ORDERS_KEY);
    if (raw) return JSON.parse(raw);
  } catch (e) {}
  localStorage.setItem(STORAGE_ORDERS_KEY, JSON.stringify(INITIAL_ORDERS));
  return INITIAL_ORDERS;
}

function saveStoredOrders(orders: any[]) {
  localStorage.setItem(STORAGE_ORDERS_KEY, JSON.stringify(orders));
}

function getStoredClaims(): any[] {
  try {
    const raw = localStorage.getItem(STORAGE_WARRANTY_KEY);
    if (raw) return JSON.parse(raw);
  } catch (e) {}
  const defaults = [
    {
      id: 1,
      claim_code: 'BH-LQ-001',
      order_item_id: 1,
      reason: 'Đổi mật khẩu tài khoản thành công',
      description: 'Đã nhận được acc và đổi thông tin thành công, bảo hành 7 ngày.',
      status: 'APPROVED',
      created_at: new Date(Date.now() - 3600 * 1000 * 3).toISOString(),
    },
  ];
  localStorage.setItem(STORAGE_WARRANTY_KEY, JSON.stringify(defaults));
  return defaults;
}

function saveStoredClaims(claims: any[]) {
  localStorage.setItem(STORAGE_WARRANTY_KEY, JSON.stringify(claims));
}

// Create Axios instance (used as client wrapper)
const api = axios.create({
  baseURL: '/mock-api',
  timeout: 500,
});

// Mock Adapter using axios request override
api.interceptors.request.use(async (config) => {
  const url = config.url || '';
  const method = (config.method || 'get').toLowerCase();
  const params = config.params || {};
  const data = typeof config.data === 'string' ? JSON.parse(config.data || '{}') : (config.data || {});

  // Simulate network latency (50ms - 150ms)
  await new Promise((res) => setTimeout(res, 80));

  let mockResponse: any = { success: true, data: null };

  // 1. Games
  if (url === '/games' || url.startsWith('/games?')) {
    mockResponse = { success: true, data: MOCK_GAMES };
  }
  // 2. Categories
  else if (url === '/categories' || url.startsWith('/categories?')) {
    let cats = [...MOCK_CATEGORIES];
    if (params.game) {
      cats = cats.filter((c) => c.game?.slug === params.game);
    }
    mockResponse = { success: true, data: cats };
  }
  // 3. Banners
  else if (url === '/banners') {
    mockResponse = { success: true, data: MOCK_BANNERS };
  }
  // 4. Settings
  else if (url === '/settings/public' || url === '/admin/settings') {
    if (method === 'get') {
      mockResponse = {
        success: true,
        data: [
          {
            key: 'general_settings',
            value: MOCK_SITE_SETTINGS.general_settings,
          },
        ],
      };
    } else {
      mockResponse = { success: true, message: 'Settings saved' };
    }
  }
  // 5. Payment Methods
  else if (url === '/payment-methods') {
    mockResponse = { success: true, data: MOCK_PAYMENT_METHODS };
  }
  // 6. Products list
  else if (url === '/products' || url.startsWith('/products?')) {
    let list = [...MOCK_PRODUCTS];

    if (params.game) {
      list = list.filter((p) => p.game.slug === params.game);
    }
    if (params.category) {
      list = list.filter((p) => p.category.slug === params.category);
    }
    if (params.type) {
      list = list.filter((p) => p.type.toLowerCase() === params.type.toLowerCase());
    }
    if (params.search || params.q) {
      const q = (params.search || params.q).toLowerCase();
      list = list.filter(
        (p) =>
          p.name.toLowerCase().includes(q) ||
          p.sku.toLowerCase().includes(q) ||
          p.short_description.toLowerCase().includes(q)
      );
    }
    if (params.min_price) {
      list = list.filter((p) => p.min_price >= Number(params.min_price));
    }
    if (params.max_price) {
      list = list.filter((p) => p.min_price <= Number(params.max_price));
    }

    mockResponse = {
      success: true,
      data: {
        data: list,
        total: list.length,
        current_page: 1,
        last_page: 1,
      },
    };
  }
  // 7. Single Product detail
  else if (url.startsWith('/products/')) {
    const slugOrId = url.replace('/products/', '').split('?')[0];
    const found = MOCK_PRODUCTS.find((p) => p.slug === slugOrId || String(p.id) === slugOrId);
    if (found) {
      mockResponse = { success: true, data: found };
    } else {
      const error: any = new Error('Product not found');
      error.response = { status: 404, data: { success: false, message: 'Không tìm thấy sản phẩm.' } };
      return Promise.reject(error);
    }
  }
  // 8. Coupons validation
  else if (url === '/coupons/validate') {
    const code = (data.code || '').toUpperCase().trim();
    const orderTotal = Number(data.order_total || data.subtotal || 0);
    const foundCoupon = MOCK_COUPONS.find((c) => c.code === code);

    if (foundCoupon) {
      let discountAmount = 0;
      if (foundCoupon.discount_type === 'PERCENT') {
        discountAmount = Math.round((orderTotal * foundCoupon.discount_value) / 100);
        if (foundCoupon.max_discount_amount && discountAmount > foundCoupon.max_discount_amount) {
          discountAmount = foundCoupon.max_discount_amount;
        }
      } else {
        discountAmount = foundCoupon.discount_value;
      }
      mockResponse = {
        success: true,
        data: {
          code: foundCoupon.code,
          discount_amount: discountAmount,
          final_total: Math.max(0, orderTotal - discountAmount),
        },
      };
    } else {
      const error: any = new Error('Coupon invalid');
      error.response = { status: 400, data: { success: false, message: 'Mã giảm giá không tồn tại hoặc hết hạn.' } };
      return Promise.reject(error);
    }
  }
  // 9. Auth endpoints (Fake login/register)
  else if (url === '/auth/login' || url === '/admin/auth/login') {
    const isAdmin = url.includes('/admin');
    const fakeToken = 'mock_jwt_token_' + Math.random().toString(36).substring(2);
    if (isAdmin) {
      mockResponse = {
        success: true,
        data: {
          token: fakeToken,
          access_token: fakeToken,
          admin: {
            id: 1,
            email: data.email || 'admin@shopacclienquan.vn',
            full_name: 'Quản Trị Viên Shop Liên Quân',
            role: 'SUPER_ADMIN',
          },
        },
      };
    } else {
      mockResponse = {
        success: true,
        data: {
          token: fakeToken,
          access_token: fakeToken,
          user: {
            id: 1,
            email: data.email || 'khachhang@shopacclienquan.vn',
            full_name: 'Khách Hàng Liên Quân',
            phone: '0912345678',
            avatar_url: null,
          },
        },
      };
    }
  } else if (url === '/auth/register') {
    const fakeToken = 'mock_jwt_token_' + Math.random().toString(36).substring(2);
    mockResponse = {
      success: true,
      data: {
        token: fakeToken,
        user: {
          id: Math.floor(Math.random() * 1000) + 2,
          email: data.email,
          full_name: data.full_name || 'Khách Hàng Mới',
          phone: data.phone || '0988888888',
        },
      },
    };
  }
  // 10. Orders endpoints
  else if (url === '/orders' && method === 'post') {
    const currentOrders = getStoredOrders();
    const orderCode = 'LQ' + Math.floor(10000000 + Math.random() * 90000000);
    const subtotal = data.items
      ? data.items.reduce((s: number, i: any) => {
          const prod = MOCK_PRODUCTS.find((p) => p.variants.some((v) => v.id === i.variant_id)) || MOCK_PRODUCTS[0];
          return s + prod.min_price * (i.quantity || 1);
        }, 0)
      : 500000;
    const discount = data.coupon_code ? 50000 : 0;
    const total = Math.max(0, subtotal - discount);

    const items = (data.items || [{ variant_id: 1001, quantity: 1 }]).map((it: any, idx: number) => {
      const prod = MOCK_PRODUCTS.find((p) => p.variants.some((v) => v.id === it.variant_id)) || MOCK_PRODUCTS[idx % MOCK_PRODUCTS.length];
      return {
        id: idx + 1,
        order_id: currentOrders.length + 1,
        product_name: prod.name,
        variant_name: 'Bàn giao tài khoản gốc',
        unit_price: prod.min_price,
        quantity: it.quantity || 1,
        line_total: prod.min_price * (it.quantity || 1),
        fulfillment_status: 'DELIVERED',
        game_account: prod.credentials
          ? {
              username: prod.credentials.username,
              password: prod.credentials.password,
              extra: {
                sec_email: prod.credentials.sec_email,
                sec_phone: prod.credentials.sec_phone,
                additional_info: prod.credentials.additional_info,
              },
            }
          : null,
      };
    });

    const newOrder = {
      id: currentOrders.length + 1,
      order_code: orderCode,
      status: 'PAID', // auto paid to demo instant delivery
      subtotal,
      discount_total: discount,
      total,
      payment_method_code: data.payment_method_code || 'vietqr',
      customer_note: data.customer_note || '',
      created_at: new Date().toISOString(),
      items,
    };

    saveStoredOrders([newOrder, ...currentOrders]);
    mockResponse = { success: true, data: newOrder };
  } else if (url === '/orders' && method === 'get') {
    mockResponse = {
      success: true,
      data: {
        data: getStoredOrders(),
      },
    };
  } else if (url.startsWith('/orders/') && !url.includes('/payments') && !url.includes('/credentials')) {
    const code = url.replace('/orders/', '').split('/')[0].split('?')[0];
    const currentOrders = getStoredOrders();
    const order = currentOrders.find((o) => o.order_code === code) || currentOrders[0];
    mockResponse = { success: true, data: order };
  } else if (url.includes('/payments')) {
    const code = url.split('/orders/')[1].split('/')[0];
    const currentOrders = getStoredOrders();
    const order = currentOrders.find((o) => o.order_code === code) || currentOrders[0];
    mockResponse = {
      success: true,
      data: {
        payment_code: 'PAY-' + code,
        amount: order?.total || 1450000,
        provider: 'SEPAY',
        method_code: order?.payment_method_code || 'vietqr',
        transfer_content: code,
        account_number: '0987654321',
        bank_name: 'MB BANK (Quân Đội)',
        account_holder: 'SHOP ACC LIEN QUAN VN',
        qr_payload: `https://img.vietqr.io/image/MB-0987654321-compact2.png?amount=${order?.total || 1450000}&addInfo=${code}&accountName=SHOP+ACC+LIEN+QUAN+VN`,
      },
    };
  }
  // 11. View Credentials in order detail
  else if (url.includes('/credentials')) {
    const code = url.split('/orders/')[1].split('/')[0];
    const currentOrders = getStoredOrders();
    const order = currentOrders.find((o) => o.order_code === code) || currentOrders[0];
    const acc = order?.items?.[0]?.game_account;

    mockResponse = {
      success: true,
      data: {
        username: acc?.username || 'lienquan_vip_acc',
        password: acc?.password || 'PassLienQuan#2026',
        extra_info: acc?.extra || {
          sec_email: 'acc@shopacclienquan.vn',
          sec_phone: '0988776655',
          additional_info: 'Đổi mật khẩu tại garena.vn',
        },
      },
    };
  }
  // 12. Warranty claims
  else if (url === '/warranty-claims') {
    if (method === 'post') {
      const claims = getStoredClaims();
      const newClaim = {
        id: claims.length + 1,
        claim_code: 'BH-LQ-' + Math.floor(100 + Math.random() * 900),
        order_item_id: data.order_item_id || 1,
        reason: data.reason || 'Yêu cầu hỗ trợ',
        description: data.description || '',
        status: 'SUBMITTED',
        created_at: new Date().toISOString(),
      };
      saveStoredClaims([newClaim, ...claims]);
      mockResponse = { success: true, data: newClaim };
    } else {
      mockResponse = {
        success: true,
        data: {
          data: getStoredClaims(),
        },
      };
    }
  }

  // Return synthetic axios-compatible response without sending real HTTP request
  config.adapter = async () => ({
    data: mockResponse,
    status: 200,
    statusText: 'OK',
    headers: {},
    config,
  });

  return config;
});

export default api;
