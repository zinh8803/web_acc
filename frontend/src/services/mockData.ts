export interface MockGame {
  id: number;
  name: string;
  slug: string;
  logo_url: string;
  description: string;
  status: 'ACTIVE';
  sort_order: number;
}

export interface MockCategory {
  id: number;
  game_id: number;
  type: 'ACCOUNT' | 'SERVICE';
  name: string;
  slug: string;
  description: string;
  sort_order: number;
  status: 'ACTIVE';
  game?: MockGame;
}

export interface MockProductVariant {
  id: number;
  product_id: number;
  sku: string;
  name: string;
  price: number;
  sale_price: number;
  compare_at_price: number;
  stock_quantity: number;
  status: 'ACTIVE';
  effective_price: number;
}

export interface MockProduct {
  id: number;
  sku: string;
  type: 'ACCOUNT' | 'SERVICE';
  category_id: number;
  game_id: number;
  name: string;
  slug: string;
  min_price: number;
  price: number;
  sale_price: number;
  thumbnail_url: string;
  short_description: string;
  description: string;
  status: 'ACTIVE';
  is_featured: boolean;
  game: MockGame;
  category: MockCategory;
  variants: MockProductVariant[];
  credentials?: {
    username: string;
    password: string;
    sec_email?: string;
    sec_phone?: string;
    additional_info?: string;
  };
}

// 1. Duy nhất Liên Quân Mobile & các thể loại liên quan
export const MOCK_GAMES: MockGame[] = [
  {
    id: 1,
    name: 'Liên Quân Mobile',
    slug: 'lien-quan-mobile',
    logo_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=120&auto=format&fit=crop&q=80',
    description: 'Đấu Trường Danh Vọng - Game MOBA số 1 Việt Nam trên di động.',
    status: 'ACTIVE',
    sort_order: 1,
  },
  {
    id: 2,
    name: 'Liên Quân - Đấu Trường Danh Vọng',
    slug: 'lien-quan-dtdv',
    logo_url: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=120&auto=format&fit=crop&q=80',
    description: 'Kho acc thi đấu rank Thách Đấu, Cao Thủ mùa giải mới nhất.',
    status: 'ACTIVE',
    sort_order: 2,
  },
  {
    id: 3,
    name: 'Liên Quân - Thử Nghiệm (MCTN)',
    slug: 'lien-quan-mctn',
    logo_url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=120&auto=format&fit=crop&q=80',
    description: 'Tài khoản máy chủ thử nghiệm Liên Quân trải nghiệm tướng mới.',
    status: 'ACTIVE',
    sort_order: 3,
  },
];

// 2. Danh mục chuyên sâu Liên Quân
export const MOCK_CATEGORIES: MockCategory[] = [
  {
    id: 1,
    game_id: 1,
    type: 'ACCOUNT',
    name: 'Acc Liên Quân VIP Rank Thách Đấu',
    slug: 'nick-lien-quan-vip',
    description: 'Acc full 118 tướng, hàng trăm skin SSS Tuyệt Sắc, Nakroth Thứ Nguyên, Raz Muay Thái, ngọc 90 chuẩn.',
    sort_order: 1,
    status: 'ACTIVE',
    game: MOCK_GAMES[0],
  },
  {
    id: 2,
    game_id: 1,
    type: 'ACCOUNT',
    name: 'Acc Liên Quân Giá Rẻ Học Sinh',
    slug: 'nick-lien-quan-gia-re',
    description: 'Acc trắng thông tin 100%, 40-70 tướng, giá học sinh sinh viên siêu tiết kiệm.',
    sort_order: 2,
    status: 'ACTIVE',
    game: MOCK_GAMES[0],
  },
  {
    id: 3,
    game_id: 1,
    type: 'ACCOUNT',
    name: 'Acc Liên Quân Skin SSS & Anime Giới Hạn',
    slug: 'nick-lien-quan-skin-sss',
    description: 'Acc sở hữu skin collab giới hạn: Tanjiro, Nezuko, Sailor Moon, Kirito, Asuna, Thứ Nguyên Vệ Thần.',
    sort_order: 3,
    status: 'ACTIVE',
    game: MOCK_GAMES[0],
  },
  {
    id: 4,
    game_id: 1,
    type: 'ACCOUNT',
    name: 'Acc Liên Quân Trắng Thông Tin Leo Rank',
    slug: 'nick-lien-quan-trang-thong-tin',
    description: 'Acc zin chưa cài số điện thoại & CCCD, đổi mật khẩu Garena ngay lập tức.',
    sort_order: 4,
    status: 'ACTIVE',
    game: MOCK_GAMES[0],
  },
  {
    id: 5,
    game_id: 1,
    type: 'SERVICE',
    name: 'Cày Thuê Rank Liên Quân Siêu Tốc',
    slug: 'dich-vu-cay-thue-lq',
    description: 'Cày thuê từ Tinh Anh, Cao Thủ lên Chiến Tướng - Thách Đấu, tỷ lệ thắng trên 85%, cam kết bảo mật.',
    sort_order: 5,
    status: 'ACTIVE',
    game: MOCK_GAMES[0],
  },
  {
    id: 6,
    game_id: 1,
    type: 'SERVICE',
    name: 'Cày Điểm Chiến Lực & Top Tướng Liên Quân',
    slug: 'cay-diem-chien-luc-top-tuong',
    description: 'Đẩy top server tướng Nakroth, Florentino, Raz, Murad, Elsu lấy danh hiệu Quốc Gia.',
    sort_order: 6,
    status: 'ACTIVE',
    game: MOCK_GAMES[0],
  },
];

// 3. Danh sách sản phẩm Acc Liên Quân Fake
export const MOCK_PRODUCTS: MockProduct[] = [
  {
    id: 101,
    sku: 'LQ-9988',
    type: 'ACCOUNT',
    category_id: 1,
    game_id: 1,
    name: 'Acc Liên Quân #9988: Thách Đấu 68 Sao, Nakroth Thứ Nguyên + Raz Muay Thái + Tulen Tân Niên',
    slug: 'acc-lien-quan-9988-thach-dau-nakroth-thu-nguyen',
    min_price: 1450000,
    price: 1950000,
    sale_price: 1450000,
    thumbnail_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&auto=format&fit=crop&q=80',
    short_description: 'Full 118 tướng, 345 Skin, Rank Thách Đấu 68 Sao, 5 Bảng Ngọc 90 chuẩn đấu giải.',
    description: 'Tài khoản tâm huyết cực đỉnh. Sở hữu dàn skin đắt giá nhất Liên Quân: Nakroth Thứ Nguyên Vệ Thần, Raz Muay Thái, Tulen Tân Niên Vệ Thần, Tel\'Annas Thứ Nguyên, Lauriel Thứ Nguyên. Trắng thông tin hoàn toàn, đổi pass Garena tức thì.',
    status: 'ACTIVE',
    is_featured: true,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[0],
    variants: [
      {
        id: 1001,
        product_id: 101,
        sku: 'LQ-9988-V1',
        name: 'Bàn giao tài khoản gốc',
        price: 1950000,
        sale_price: 1450000,
        compare_at_price: 1950000,
        stock_quantity: 1,
        status: 'ACTIVE',
        effective_price: 1450000,
      },
    ],
    credentials: {
      username: 'lq_thachdau_vip9988',
      password: 'GarenaLQPass@2026',
      sec_email: 'acc9988@shopacclienquan.vn',
      sec_phone: '0978111222',
      additional_info: 'Mã PIN cấp 2: 888999. Vui lòng vào garena.vn đổi mật khẩu ngay sau khi nhận tài khoản!',
    },
  },
  {
    id: 102,
    sku: 'LQ-7721',
    type: 'ACCOUNT',
    category_id: 3,
    game_id: 1,
    name: 'Acc Liên Quân #7721: Full Collab Kimetsu No Yaiba (Tanjiro + Nezuko + Inosuke + Zenitsu)',
    slug: 'acc-lien-quan-7721-collab-kimetsu',
    min_price: 1150000,
    price: 1600000,
    sale_price: 1150000,
    thumbnail_url: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&auto=format&fit=crop&q=80',
    short_description: 'Full bộ sưu tập Sát Thủ Diệt Quỷ giới hạn, 98 Tướng, 210 Skin, Rank Cao Thủ 25 Sao.',
    description: 'Acc hiếm sở hữu toàn bộ skin collab Demon Slayer độc quyền: Yan Tanjiro Kamado, Keera Nezuko Kamado, Enzo Inosuke Hashibira, Murad Zenitsu Agatsuma. Tài khoản trắng thông tin 100% cực an toàn.',
    status: 'ACTIVE',
    is_featured: true,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[2],
    variants: [
      {
        id: 1002,
        product_id: 102,
        sku: 'LQ-7721-V1',
        name: 'Bàn giao tự động',
        price: 1600000,
        sale_price: 1150000,
        compare_at_price: 1600000,
        stock_quantity: 1,
        status: 'ACTIVE',
        effective_price: 1150000,
      },
    ],
    credentials: {
      username: 'kimetsu_lq_7721',
      password: 'Tanjiro@LienQuan2026',
      sec_email: 'kimetsu7721@shopacclienquan.vn',
      sec_phone: '0912333444',
      additional_info: 'Trắng mail & số điện thoại, vào là chiến ngay.',
    },
  },
  {
    id: 103,
    sku: 'LQ-3310',
    type: 'ACCOUNT',
    category_id: 2,
    game_id: 1,
    name: 'Acc Liên Quân #3310: Trắng TT 100%, 75 Tướng, Ngộ Không Nhóc Tì + Florentino Giám Sát',
    slug: 'acc-lien-quan-3310-gia-re-trang-tt',
    min_price: 199000,
    price: 290000,
    sale_price: 199000,
    thumbnail_url: 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=600&auto=format&fit=crop&q=80',
    short_description: 'Giá học sinh sinh viên, Rank Kim Cương I, 75 Tướng, 82 Skin, trắng số điện thoại và email.',
    description: 'Tài khoản giá cực rẻ thích hợp cho tân thủ và anh em leo rank từ đầu. Đầy đủ tướng hot meta: Florentino, Ngộ Không, Nakroth, Elsu, Aoi, Richter. Trắng thông tin tuyệt đối.',
    status: 'ACTIVE',
    is_featured: true,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[1],
    variants: [
      {
        id: 1003,
        product_id: 103,
        sku: 'LQ-3310-V1',
        name: 'Giao ngay',
        price: 290000,
        sale_price: 199000,
        compare_at_price: 290000,
        stock_quantity: 1,
        status: 'ACTIVE',
        effective_price: 199000,
      },
    ],
    credentials: {
      username: 'hocsinh_lq_3310',
      password: 'AccGiaRe!2026',
      sec_email: 'acc3310@shopacclienquan.vn',
      sec_phone: '0909555666',
      additional_info: 'Acc trắng 100%, đổi mật khẩu thoải mái.',
    },
  },
  {
    id: 104,
    sku: 'LQ-5502',
    type: 'ACCOUNT',
    category_id: 4,
    game_id: 1,
    name: 'Acc Liên Quân #5502: 110 Tướng, Full Ngọc Phép & Sát Thủ, Skin Omen Đao Phủ Tận Thế',
    slug: 'acc-lien-quan-5502-omen-dao-phu',
    min_price: 490000,
    price: 650000,
    sale_price: 490000,
    thumbnail_url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
    short_description: 'Rank Tinh Anh I, 110 Tướng, 145 Skin, bảng ngọc đầy đủ tất cả vị trí solo đường Tà Thần.',
    description: 'Acc chuyên đi rừng và đường Caesar. Đầy đủ ngọc chí mạng 16%, ngọc công vật lý xuyên giáp 100, ngọc công phép 88. Bảo hành vĩnh viễn không sợ mất acc.',
    status: 'ACTIVE',
    is_featured: true,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[3],
    variants: [
      {
        id: 1004,
        product_id: 104,
        sku: 'LQ-5502-V1',
        name: 'Acc Full Ngọc',
        price: 650000,
        sale_price: 490000,
        compare_at_price: 650000,
        stock_quantity: 1,
        status: 'ACTIVE',
        effective_price: 490000,
      },
    ],
    credentials: {
      username: 'omen_pro_5502',
      password: 'SoloTop!2026',
      sec_email: 'acc5502@shopacclienquan.vn',
      sec_phone: '0988777999',
      additional_info: 'Bàn giao tự động tức thì.',
    },
  },
  {
    id: 105,
    sku: 'LQ-8833',
    type: 'ACCOUNT',
    category_id: 1,
    game_id: 1,
    name: 'Acc Liên Quân #8833: Chiến Tướng 45 Sao, Full 118 Tướng, Butterfly Nữ Quái Nổi Loạn',
    slug: 'acc-lien-quan-8833-chien-tuong-butterfly',
    min_price: 880000,
    price: 1200000,
    sale_price: 880000,
    thumbnail_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&auto=format&fit=crop&q=80',
    short_description: 'Chiến Tướng 45 Sao, 118 Tướng, 260 Skin, Tỷ lệ thắng 68%, KDA cực khủng.',
    description: 'Acc leo rank đỉnh cao, thông thạo S hơn 30 tướng sát thủ. Trắng thông tin, tài khoản chưa từng vi phạm quy tắc nạp lậu hay can thiệp phần mềm.',
    status: 'ACTIVE',
    is_featured: true,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[0],
    variants: [
      {
        id: 1005,
        product_id: 105,
        sku: 'LQ-8833-V1',
        name: 'Mặc định',
        price: 1200000,
        sale_price: 880000,
        compare_at_price: 1200000,
        stock_quantity: 1,
        status: 'ACTIVE',
        effective_price: 880000,
      },
    ],
    credentials: {
      username: 'chientuong_8833',
      password: 'ChienTuong@Safe2026',
      sec_email: 'acc8833@shopacclienquan.vn',
      sec_phone: '0933444555',
      additional_info: 'Acc sạch 100%, bảo hành 1 đổi 1.',
    },
  },
  {
    id: 106,
    sku: 'SRV-LQ-01',
    type: 'SERVICE',
    category_id: 5,
    game_id: 1,
    name: 'Cày Thuê Rank Liên Quân Siêu Tốc: Tinh Anh Lên Cao Thủ (Bao Thắng 85%+)',
    slug: 'cay-thue-tinh-anh-len-cao-thu',
    min_price: 150000,
    price: 200000,
    sale_price: 150000,
    thumbnail_url: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&auto=format&fit=crop&q=80',
    short_description: 'Cày trong vòng 24 giờ, tuyển thủ Top 1 Thách Đấu trực tiếp cày, bảo mật thông tin 100%.',
    description: 'Dịch vụ cày thuê rank Liên Quân uy tín hàng đầu. Cam kết không chat ingame, không phá bảng ngọc, bảo toàn lịch sử đấu xanh mướt.',
    status: 'ACTIVE',
    is_featured: false,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[4],
    variants: [
      {
        id: 1006,
        product_id: 106,
        sku: 'SRV-LQ-01-V1',
        name: 'Gói Cày Rank 24H',
        price: 200000,
        sale_price: 150000,
        compare_at_price: 200000,
        stock_quantity: 99,
        status: 'ACTIVE',
        effective_price: 150000,
      },
    ],
  },
  {
    id: 107,
    sku: 'SRV-LQ-02',
    type: 'SERVICE',
    category_id: 6,
    game_id: 1,
    name: 'Cày Điểm Chiến Lực Top 1 Tướng Tỉnh / Toàn Quốc (Nakroth, Florentino, Raz)',
    slug: 'cay-diem-chien-luc-top-tuong-nakroth-florentino',
    min_price: 350000,
    price: 450000,
    sale_price: 350000,
    thumbnail_url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
    short_description: 'Đạt danh hiệu Top Tướng trong kỳ tổng kết tuần mới nhất, tỷ lệ thắng cực cao.',
    description: 'Gói cày điểm chiến lực tướng chuyên sâu dành cho game thủ muốn khoe danh hiệu Top Tướng Liên Quân cực ngầu trên trang cá nhân.',
    status: 'ACTIVE',
    is_featured: false,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[5],
    variants: [
      {
        id: 1007,
        product_id: 107,
        sku: 'SRV-LQ-02-V1',
        name: 'Gói Điểm Chiến Lực',
        price: 450000,
        sale_price: 350000,
        compare_at_price: 450000,
        stock_quantity: 99,
        status: 'ACTIVE',
        effective_price: 350000,
      },
    ],
  },
  {
    id: 108,
    sku: 'LQ-4419',
    type: 'ACCOUNT',
    category_id: 3,
    game_id: 1,
    name: 'Acc Liên Quân #4419: Murad Siêu Việt Bậc 5 + Raz Muay Thái + Quillen Đặc Công',
    slug: 'acc-lien-quan-4419-murad-sieu-viet',
    min_price: 520000,
    price: 750000,
    sale_price: 520000,
    thumbnail_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&auto=format&fit=crop&q=80',
    short_description: 'Murad Siêu Việt cấp tối đa full hiệu ứng biến về, 105 Tướng, 160 Skin, Rank Tinh Anh.',
    description: 'Acc chuyên trị tướng sát thủ cơ động. Đầy đủ các skin bậc S+, SS của dòng tướng Rừng. Đổi được toàn bộ thông tin tài khoản ngay.',
    status: 'ACTIVE',
    is_featured: true,
    game: MOCK_GAMES[0],
    category: MOCK_CATEGORIES[2],
    variants: [
      {
        id: 1008,
        product_id: 108,
        sku: 'LQ-4419-V1',
        name: 'Acc Murad Siêu Việt',
        price: 750000,
        sale_price: 520000,
        compare_at_price: 750000,
        stock_quantity: 1,
        status: 'ACTIVE',
        effective_price: 520000,
      },
    ],
    credentials: {
      username: 'murad_sieuviet_4419',
      password: 'MuradPass@2026',
      sec_email: 'acc4419@shopacclienquan.vn',
      sec_phone: '0944666888',
      additional_info: 'Bảo hành không back trọn đời.',
    },
  },
];

// 4. Banners
export const MOCK_BANNERS = [
  {
    id: 1,
    title: 'XẢ KHO ACC LIÊN QUÂN VIP - GIẢM GIÁ ĐẾN 40% CHÀO MÙA GIẢI MỚI',
    image_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1600&auto=format&fit=crop&q=80',
    link_url: '/catalog',
    sort_order: 1,
    status: 'ACTIVE',
  },
  {
    id: 2,
    title: 'BẢO HÀNH TRỌN ĐỜI 100% KHÔNG BACK - BÀN GIAO NICK TỰ ĐỘNG SAU 3 GIÂY',
    image_url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1600&auto=format&fit=crop&q=80',
    link_url: '/warranty-claims',
    sort_order: 2,
    status: 'ACTIVE',
  },
];

// 5. Cổng thanh toán
export const MOCK_PAYMENT_METHODS = [
  {
    id: 1,
    code: 'vietqr',
    name: 'Chuyển Khoản Ngân Hàng 24/7 (VietQR Bàn Giao Tự Động)',
    provider: 'SEPAY',
    icon_url: 'https://img.vietqr.io/image/MB-0987654321-compact2.png',
    fee_percent: 0,
    is_active: true,
  },
  {
    id: 2,
    code: 'momo',
    name: 'Ví Điện Tử MoMo Quét Mã Nhanh',
    provider: 'MOMO',
    icon_url: 'https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png',
    fee_percent: 0,
    is_active: true,
  },
  {
    id: 3,
    code: 'vnpay',
    name: 'Cổng Thanh Toán Quốc Gia VNPAY-QR',
    provider: 'VNPAY',
    icon_url: 'https://vnpay.vn/assets/images/logo.svg',
    fee_percent: 0,
    is_active: true,
  },
];

// 6. Cấu hình trang web
export const MOCK_SITE_SETTINGS = {
  general_settings: {
    site_name: 'SHOPACCLIENQUAN.VN - Sàn Giao Dịch Acc Liên Quân Số 1 Việt Nam',
    hotline: '0987.654.321',
    zalo: '0987654321',
    telegram: 'https://t.me/shopacclienquan_vn',
    default_warranty_days: 7,
  },
};

// 7. Coupons
export const MOCK_COUPONS = [
  {
    code: 'CHAOBANMOI',
    name: 'Chào bạn mới giảm 10%',
    discount_type: 'PERCENT',
    discount_value: 10,
    min_order_amount: 100000,
    max_discount_amount: 200000,
  },
  {
    code: 'LIENQUANVIP',
    name: 'Vip Liên Quân giảm 50K',
    discount_type: 'FIXED',
    discount_value: 50000,
    min_order_amount: 300000,
    max_discount_amount: 50000,
  },
];

// 8. Đơn hàng mẫu ban đầu
export const INITIAL_ORDERS: any[] = [
  {
    id: 1,
    order_code: 'LQ202610189901',
    status: 'COMPLETED',
    subtotal: 1450000,
    discount_total: 145000,
    total: 1305000,
    payment_method_code: 'vietqr',
    created_at: new Date(Date.now() - 3600 * 1000 * 5).toISOString(),
    items: [
      {
        id: 1,
        order_id: 1,
        product_name: 'Acc Liên Quân #9988: Thách Đấu 68 Sao, Nakroth Thứ Nguyên + Raz Muay Thái',
        variant_name: 'Bàn giao tài khoản gốc',
        unit_price: 1450000,
        quantity: 1,
        line_total: 1450000,
        fulfillment_status: 'DELIVERED',
        game_account: {
          username: 'lq_thachdau_vip9988',
          password: 'GarenaLQPass@2026',
          extra: {
            sec_email: 'acc9988@shopacclienquan.vn',
            sec_phone: '0978111222',
            additional_info: 'Mã PIN cấp 2: 888999. Vui lòng đổi mật khẩu ngay!',
          },
        },
      },
    ],
  },
];
