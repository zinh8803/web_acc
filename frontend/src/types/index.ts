// ==================== ENUMS ====================
export type UserStatus = 'ACTIVE' | 'INACTIVE' | 'BANNED';
export type AdminStatus = 'ACTIVE' | 'DISABLED';
export type CategoryType = 'ACCOUNT' | 'SERVICE';
export type DataType = 'TEXT' | 'NUMBER' | 'BOOLEAN' | 'SELECT' | 'MULTI_SELECT';
export type ProductType = 'ACCOUNT' | 'SERVICE';
export type ProductStatus = 'DRAFT' | 'ACTIVE' | 'HIDDEN' | 'SOLD_OUT' | 'ARCHIVED';
export type GameAccountStatus = 'AVAILABLE' | 'RESERVED' | 'SOLD' | 'LOCKED' | 'RECOVERED' | 'RETIRED';
export type OrderStatus =
  | 'PENDING_PAYMENT'
  | 'PAID'
  | 'PROCESSING'
  | 'COMPLETED'
  | 'CANCELLED'
  | 'EXPIRED'
  | 'REFUNDED'
  | 'PARTIALLY_REFUNDED';
export type PaymentStatus = 'PENDING' | 'PAID' | 'FAILED' | 'EXPIRED' | 'CANCELLED' | 'NEED_REVIEW';
export type ServiceRequestStatus =
  | 'PENDING'
  | 'ASSIGNED'
  | 'IN_PROGRESS'
  | 'NEED_INFO'
  | 'DONE'
  | 'CANCELLED'
  | 'REFUNDED';
export type WarrantyClaimStatus =
  | 'SUBMITTED'
  | 'IN_REVIEW'
  | 'NEED_INFO'
  | 'APPROVED'
  | 'REJECTED'
  | 'RESOLVED'
  | 'CANCELLED';
export type FulfillmentStatus = 'PENDING' | 'DELIVERED' | 'IN_PROGRESS' | 'COMPLETED' | 'FAILED' | 'CANCELLED';
export type DiscountType = 'PERCENT' | 'FIXED';
export type RefundStatus = 'REQUESTED' | 'APPROVED' | 'REJECTED' | 'COMPLETED';

// ==================== MODELS ====================
export interface User {
  id: number;
  email: string;
  phone: string | null;
  full_name: string;
  avatar_url: string | null;
  birth_date: string | null;
  status: UserStatus;
  email_verified_at: string | null;
  created_at: string;
}

export interface Game {
  id: number;
  name: string;
  slug: string;
  logo_url: string | null;
  description: string | null;
  status: 'ACTIVE' | 'HIDDEN';
  sort_order: number;
}

export interface Category {
  id: number;
  parent_id: number | null;
  game_id: number | null;
  type: CategoryType;
  name: string;
  slug: string;
  description: string | null;
  image_url: string | null;
  level: number;
  sort_order: number;
  status: 'ACTIVE' | 'HIDDEN';
  children?: Category[];
  game?: Game;
}

export interface AttributeOption {
  id: number;
  attribute_id: number;
  value: string;
  label: string;
  sort_order: number;
}

export interface Attribute {
  id: number;
  code: string;
  name: string;
  data_type: DataType;
  unit: string | null;
  is_filterable: boolean;
  options?: AttributeOption[];
}

export interface ProductVariant {
  id: number;
  product_id: number;
  sku: string;
  name: string;
  price: number;
  compare_at_price: number | null;
  sale_price: number | null;
  sale_start_at: string | null;
  sale_end_at: string | null;
  stock_quantity: number;
  estimated_hours: number | null;
  status: 'ACTIVE' | 'HIDDEN';
  sort_order: number;
  // computed
  effective_price: number;
}

export interface ProductImage {
  id: number;
  product_id: number;
  url: string;
  thumbnail_url: string;
  alt_text: string | null;
  sort_order: number;
  is_primary: boolean;
}

export interface ProductAttributeValue {
  attribute: Attribute;
  value_text: string | null;
  value_number: number | null;
  value_boolean: boolean | null;
  option?: AttributeOption;
  options?: AttributeOption[]; // for MULTI_SELECT
}

export interface WarrantyPolicy {
  id: number;
  code: string;
  name: string;
  product_type: ProductType;
  duration_days: number | null;
  is_lifetime: boolean;
  coverage: string[];
  exclusions: string | null;
  max_claims: number;
  resolution_options: string[];
  claim_sla_hours: number;
  description: string | null;
  status: 'ACTIVE' | 'DISABLED';
}

export interface Product {
  id: number;
  sku: string;
  type: ProductType;
  category_id: number;
  game_id: number | null;
  name: string;
  slug: string;
  short_description: string | null;
  description: string | null;
  thumbnail_url: string | null;
  min_price: number;
  status: ProductStatus;
  is_featured: boolean;
  service_form_schema: Record<string, unknown> | null;
  sold_count: number;
  view_count: number;
  rating_avg: number;
  rating_count: number;
  warranty_policy?: WarrantyPolicy | null;
  category?: Category;
  game?: Game | null;
  variants?: ProductVariant[];
  images?: ProductImage[];
  attributes?: ProductAttributeValue[];
}

export interface CartItem {
  id: number;
  variant_id?: number;
  quantity: number;
  variant?: ProductVariant & { product: Product };
  productId?: number;
  name?: string;
  price?: number;
  thumbnailUrl?: string | null;
}

export interface Cart {
  id: number;
  items: CartItem[];
}

export interface Coupon {
  id: number;
  code: string;
  name: string;
  discount_type: DiscountType;
  discount_value: number;
  max_discount_amount: number | null;
  min_order_amount: number;
}

export interface OrderItem {
  id: number;
  order_id: number;
  item_type: ProductType;
  product_id: number;
  variant_id: number;
  product_name: string;
  variant_name: string;
  thumbnail_url: string | null;
  unit_price: number;
  quantity: number;
  line_total: number;
  game_account_id: number | null;
  warranty_policy_id: number | null;
  warranty_snapshot: WarrantyPolicy | null;
  warranty_expires_at: string | null;
  fulfillment_status: FulfillmentStatus;
  delivered_at: string | null;
  service_request?: ServiceRequest;
  warranty_claims?: WarrantyClaim[];
}

export interface Order {
  id: number;
  order_code: string;
  user_id: number;
  status: OrderStatus;
  subtotal: number;
  discount_total: number;
  total: number;
  coupon_code: string | null;
  payment_method_code: string;
  customer_note: string | null;
  expires_at: string;
  paid_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  cancel_reason: string | null;
  created_at: string;
  items?: OrderItem[];
  payments?: Payment[];
}

export interface Payment {
  id: number;
  payment_code: string;
  order_id: number;
  method_code: string;
  provider: string;
  amount: number;
  paid_amount: number | null;
  status: PaymentStatus;
  provider_txn_id: string | null;
  transfer_content: string | null;
  pay_url: string | null;
  qr_payload: string | null;
  expires_at: string;
  paid_at: string | null;
}

export interface ServiceRequest {
  id: number;
  request_code: string;
  order_item_id: number;
  user_id: number;
  product_id: number;
  variant_id: number;
  game_id: number | null;
  status: ServiceRequestStatus;
  form_data: Record<string, unknown>;
  customer_note: string | null;
  assigned_to: number | null;
  priority: number;
  due_at: string | null;
  started_at: string | null;
  completed_at: string | null;
  auto_confirm_at: string | null;
  confirmed_at: string | null;
  created_at: string;
  logs?: ServiceRequestLog[];
}

export interface ServiceRequestLog {
  id: number;
  from_status: ServiceRequestStatus | null;
  to_status: ServiceRequestStatus;
  actor_type: 'SYSTEM' | 'USER' | 'ADMIN';
  message: string;
  progress_percent: number | null;
  is_visible_to_user: boolean;
  created_at: string;
}

export interface WarrantyClaim {
  id: number;
  claim_code: string;
  order_item_id: number;
  user_id: number;
  game_account_id: number | null;
  policy_id: number;
  claim_type:
    | 'LOSS_ACCESS'
    | 'RECOVERED_BY_OLD_OWNER'
    | 'WRONG_CREDENTIALS'
    | 'SERVICE_NOT_DONE'
    | 'OTHER';
  description: string;
  status: WarrantyClaimStatus;
  resolution_type: string | null;
  resolution_note: string | null;
  reject_reason: string | null;
  due_at: string | null;
  submitted_at: string;
  resolved_at: string | null;
  created_at: string;
  messages?: WarrantyClaimMessage[];
}

export interface WarrantyClaimMessage {
  id: number;
  claim_id: number;
  sender_type: 'USER' | 'ADMIN';
  sender_id: number;
  message: string;
  is_internal: boolean;
  created_at: string;
}

export interface Review {
  id: number;
  user_id: number;
  product_id: number;
  order_item_id: number;
  rating: number;
  comment: string | null;
  status: 'PENDING' | 'APPROVED' | 'HIDDEN';
  created_at: string;
  user?: Pick<User, 'id' | 'full_name' | 'avatar_url'>;
}

// ==================== API RESPONSE ====================
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  error?: {
    code: string;
    message: string;
  };
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    page: number;
    limit: number;
    total: number;
    last_page: number;
  };
}

// ==================== FILTER PARAMS ====================
export interface ProductFilterParams {
  category_id?: number;
  game_id?: number;
  type?: ProductType;
  status?: ProductStatus;
  min_price?: number;
  max_price?: number;
  is_featured?: boolean;
  search?: string;
  sort?: 'price_asc' | 'price_desc' | 'newest' | 'sold' | 'rating';
  page?: number;
  limit?: number;
  [key: string]: unknown; // for attribute filters like attr_rank, attr_hero_count
}
