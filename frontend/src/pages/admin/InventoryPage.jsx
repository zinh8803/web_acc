import React, { useEffect, useState } from 'react';
import { Button, Descriptions, Form, Input, message, Modal, Select, Table, Tag, Upload } from 'antd';
import { EditOutlined, EyeOutlined, LockOutlined, PlusOutlined, SearchOutlined } from '@ant-design/icons';
import api from '../../services/api';

const statusOptions = [
    { value: 'AVAILABLE', label: 'Sẵn sàng bán' },
    { value: 'RESERVED', label: 'Đang giữ chỗ' },
    { value: 'SOLD', label: 'Đã bán' },
    { value: 'LOCKED', label: 'Đang khóa' },
    { value: 'RECOVERED', label: 'Đã thu hồi' },
    { value: 'RETIRED', label: 'Ngừng bán' },
];

const generatePassword = (length = 18) => {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    const randomValues = crypto.getRandomValues(new Uint32Array(length));
    return Array.from(randomValues, (value) => alphabet[value % alphabet.length]).join('');
};

const InventoryPage = () => {
    const [accounts, setAccounts] = useState([]);
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [addOpen, setAddOpen] = useState(false);
    const [viewData, setViewData] = useState(null);
    const [editingAccount, setEditingAccount] = useState(null);
    const [addForm] = Form.useForm();
    const [editForm] = Form.useForm();
    const [filterForm] = Form.useForm();
    const [filters, setFilters] = useState({});
    const [pagination, setPagination] = useState({ current: 1, pageSize: 20, total: 0 });

    const fetchAccounts = async (page = pagination.current, pageSize = pagination.pageSize, activeFilters = filters) => {
        setLoading(true);
        try {
            const response = await api.get('/admin/game-accounts', { params: { ...activeFilters, page, per_page: pageSize } });
            const result = response.data?.data;
            setAccounts(Array.isArray(result?.data) ? result.data : []);
            setPagination({ current: result?.current_page || page, pageSize: result?.per_page || pageSize, total: result?.total || 0 });
        } catch (error) {
            message.error(error.response?.data?.message || 'Không tải được kho tài khoản.');
        } finally {
            setLoading(false);
        }
    };

    const fetchProducts = async () => {
        try {
            const response = await api.get('/admin/products', { params: { per_page: 100 } });
            const result = response.data?.data;
            const list = Array.isArray(result?.data) ? result.data : Array.isArray(result) ? result : [];
            setProducts(list.filter((product) => product.type === 'ACCOUNT'));
        } catch (error) {
            message.error(error.response?.data?.message || 'Không tải được danh sách sản phẩm.');
        }
    };

    useEffect(() => { fetchAccounts(1, 20, {}); fetchProducts(); }, []);

    const applyFilters = (values) => {
        const nextFilters = Object.fromEntries(Object.entries(values).filter(([, value]) => value !== undefined && value !== ''));
        setFilters(nextFilters);
        fetchAccounts(1, pagination.pageSize, nextFilters);
    };

    const resetFilters = () => {
        filterForm.resetFields();
        setFilters({});
        fetchAccounts(1, pagination.pageSize, {});
    };

    const openAddModal = () => {
        addForm.resetFields();
        addForm.setFieldValue('password', generatePassword());
        setAddOpen(true);
    };

    const addAccount = async (values) => {
        try {
            const payload = new FormData();
            Object.entries(values).forEach(([key, value]) => {
                if (key === 'image_file') {
                    const file = value?.[0]?.originFileObj;
                    if (file) payload.append('image_file', file);
                } else if (key === 'public_details') {
                    payload.append('public_details', JSON.stringify((value || []).filter((item) => item?.label && item?.value)));
                } else if (value !== undefined && value !== null && value !== '') {
                    payload.append(key, String(value));
                }
            });
            await api.post('/admin/game-accounts', payload);
            message.success('Đã nhập tài khoản vào kho.');
            setAddOpen(false);
            addForm.resetFields();
            await fetchAccounts(1, pagination.pageSize, filters);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể thêm tài khoản.');
        }
    };

    const viewCredentials = async (id) => {
        try {
            const response = await api.get(`/admin/game-accounts/${id}`);
            if (response.data?.success) setViewData(response.data.data);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể giải mã tài khoản.');
        }
    };

    const editAccount = async (account) => {
        try {
            const response = await api.get(`/admin/game-accounts/${account.id}`);
            const data = response.data.data;
            setEditingAccount(data);
            editForm.setFieldsValue({
                username: data.credentials?.username,
                password: data.credentials?.password,
                sec_email: data.credentials?.extra?.sec_email,
                sec_phone: data.credentials?.extra?.sec_phone,
                additional_info: data.credentials?.extra?.additional_info,
                cost_price: data.cost_price ?? data.credentials?.extra?.cost_price,
                public_details: data.public_details || [],
            });
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể tải thông tin tài khoản.');
        }
    };

    const saveAccount = async (values) => {
        const payload = new FormData();
        Object.entries(values).forEach(([key, value]) => {
            if (key === 'image_file') {
                const file = value?.[0]?.originFileObj;
                if (file) payload.append('image_file', file);
            } else if (key === 'public_details') {
                payload.append('public_details', JSON.stringify((value || []).filter((item) => item?.label && item?.value)));
            } else if (value !== undefined && value !== null) {
                payload.append(key, String(value));
            }
        });
        payload.append('_method', 'PATCH');
        try {
            await api.post(`/admin/game-accounts/${editingAccount.id}`, payload);
            message.success('Đã cập nhật thông tin tài khoản.');
            setEditingAccount(null);
            editForm.resetFields();
            await fetchAccounts(pagination.current, pagination.pageSize, filters);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không cập nhật được thông tin tài khoản.');
        }
    };

    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 75 },
        { title: 'Sản phẩm', dataIndex: 'product', key: 'product', render: (product) => <span style={{ color: '#fff', fontWeight: 600 }}>{product?.name || 'Tài khoản lẻ'}</span> },
        { title: 'Ảnh & thông tin công khai', key: 'listing', render: (_, account) => <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>{account.image_url && <img src={account.image_url} alt="" style={{ width: 48, height: 36, objectFit: 'cover', borderRadius: 4 }} />}<span>{(account.public_details || []).map((detail) => `${detail.label}: ${detail.value}`).join(' · ') || 'Chưa thêm thông tin'}</span></div> },
        { title: 'Bảo mật', key: 'security', render: () => <Tag color="green"><LockOutlined /> AES-256-GCM</Tag> },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (status) => {
            const labels = { AVAILABLE: ['cyan', 'Sẵn sàng bán'], RESERVED: ['orange', 'Đang giữ chỗ'], SOLD: ['magenta', 'Đã bán'], LOCKED: ['default', 'Đang khóa'], RECOVERED: ['red', 'Đã thu hồi'], RETIRED: ['default', 'Ngừng bán'] };
            const [color, label] = labels[status] || ['default', status];
            return <Tag color={color}>{label}</Tag>;
        } },
        { title: 'Thao tác', key: 'action', render: (_, record) => <div style={{ display: 'flex', gap: 8 }}><Button size="small" icon={<EditOutlined />} onClick={() => editAccount(record)}>Sửa thông tin</Button><Button size="small" type="primary" icon={<EyeOutlined />} onClick={() => viewCredentials(record.id)}>Giải mã</Button></div> },
    ];

    return <div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
            <div><h1 style={{ color: '#fff', fontSize: 24, fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>KHO TÀI KHOẢN GAME</h1><p style={{ color: '#94a3b8', fontSize: 13, margin: 0 }}>Lọc tài khoản theo sản phẩm và trạng thái kho.</p></div>
            <Button type="primary" icon={<PlusOutlined />} onClick={openAddModal}>Nhập tài khoản</Button>
        </div>
        <div style={{ background: '#121324', borderRadius: 14, padding: 16, marginBottom: 16, border: '1px solid rgba(255,255,255,0.08)' }}>
            <Form form={filterForm} layout="inline" onFinish={applyFilters} style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                <Form.Item name="product_id" style={{ minWidth: 260, margin: 0 }}><Select allowClear showSearch optionFilterProp="label" placeholder="Tất cả sản phẩm" options={products.map((product) => ({ value: product.id, label: product.name }))} /></Form.Item>
                <Form.Item name="status" style={{ minWidth: 180, margin: 0 }}><Select allowClear placeholder="Tất cả trạng thái" options={statusOptions} /></Form.Item>
                <Form.Item name="search" style={{ minWidth: 220, margin: 0 }}><Input allowClear placeholder="Tìm theo tên sản phẩm" /></Form.Item>
                <Button type="primary" htmlType="submit" icon={<SearchOutlined />}>Lọc</Button>
                <Button onClick={resetFilters}>Đặt lại</Button>
            </Form>
        </div>
        <div style={{ background: '#121324', borderRadius: 14, padding: 20, border: '1px solid rgba(255,255,255,0.08)' }}>
            <Table columns={columns} dataSource={accounts} rowKey="id" loading={loading} pagination={{ current: pagination.current, pageSize: pagination.pageSize, total: pagination.total, showSizeChanger: true, pageSizeOptions: [20, 50, 100], showTotal: (total) => `Tổng ${total} tài khoản` }} onChange={(next) => fetchAccounts(next.current, next.pageSize)} />
        </div>
        <Modal title="Nhập tài khoản vào kho" open={addOpen} onCancel={() => setAddOpen(false)} footer={null} destroyOnClose>
            <Form form={addForm} layout="vertical" onFinish={addAccount}>
                <Form.Item name="product_id" label="Sản phẩm" rules={[{ required: true }]}><Select showSearch optionFilterProp="label" placeholder="Chọn sản phẩm" options={products.map((product) => ({ value: product.id, label: product.name }))} /></Form.Item>
                <Form.Item name="username" label="Tên đăng nhập" rules={[{ required: true }]}><Input /></Form.Item>
                <Form.Item name="password" label="Mật khẩu (đã tạo ngẫu nhiên, có thể sửa)" rules={[{ required: true }]}><Input.Password addonAfter={<Button type="link" onClick={() => addForm.setFieldValue('password', generatePassword())}>Tạo lại</Button>} /></Form.Item>
                <Form.Item name="image_file" label="Ảnh riêng của tài khoản" valuePropName="fileList" getValueFromEvent={(event) => event?.fileList}><Upload accept="image/*" maxCount={1} beforeUpload={() => false} listType="picture"><Button>Chọn ảnh</Button></Upload></Form.Item>
                <Form.List name="public_details">
                    {(fields, { add, remove }) => <div>
                        <div style={{ color: '#cbd5e1', marginBottom: 8 }}>Thông tin riêng hiển thị cho khách (ví dụ: rank, skin, cấp độ)</div>
                        {fields.map((field) => <div key={field.key} style={{ display: 'flex', gap: 8, alignItems: 'flex-start' }}>
                            <Form.Item name={[field.name, 'label']} rules={[{ required: true, message: 'Nhập tên thông tin.' }]} style={{ flex: 1 }}><Input placeholder="Tên thuộc tính" /></Form.Item>
                            <Form.Item name={[field.name, 'value']} rules={[{ required: true, message: 'Nhập giá trị.' }]} style={{ flex: 1 }}><Input placeholder="Giá trị" /></Form.Item>
                            <Button danger onClick={() => remove(field.name)}>Xóa</Button>
                        </div>)}
                        <Button type="dashed" onClick={() => add()} block>Thêm thông tin</Button>
                    </div>}
                </Form.List>
                <Form.Item name="sec_email" label="Email bảo mật"><Input /></Form.Item>
                <Form.Item name="sec_phone" label="Số điện thoại bảo mật"><Input /></Form.Item>
                <Form.Item name="additional_info" label="Thông tin bổ sung"><Input.TextArea rows={2} /></Form.Item>
                <Form.Item name="cost_price" label="Giá vốn"><Input type="number" min={0} /></Form.Item>
                <Button type="primary" block htmlType="submit">Lưu vào kho</Button>
            </Form>
        </Modal>
        <Modal title={`Sửa thông tin tài khoản #${editingAccount?.id || ''}`} open={!!editingAccount} onCancel={() => setEditingAccount(null)} footer={null} destroyOnClose>
            <Form form={editForm} layout="vertical" onFinish={saveAccount}>
                {editingAccount?.image_url && <img src={editingAccount.image_url} alt="Ảnh hiện tại" style={{ width: 120, height: 80, objectFit: 'cover', marginBottom: 12 }} />}
                <Form.Item name="username" label="Tên đăng nhập" rules={[{ required: true }]}><Input /></Form.Item>
                <Form.Item name="password" label="Mật khẩu" rules={[{ required: true }]}><Input.Password /></Form.Item>
                <Form.Item name="sec_email" label="Email bảo mật"><Input /></Form.Item>
                <Form.Item name="sec_phone" label="Số điện thoại bảo mật"><Input /></Form.Item>
                <Form.Item name="additional_info" label="Thông tin bổ sung"><Input.TextArea rows={2} /></Form.Item>
                <Form.Item name="cost_price" label="Giá vốn"><Input type="number" min={0} /></Form.Item>
                <Form.Item name="image_file" label="Ảnh riêng" valuePropName="fileList" getValueFromEvent={(event) => event?.fileList}><Upload accept="image/*" maxCount={1} beforeUpload={() => false} listType="picture"><Button>Chọn ảnh mới</Button></Upload></Form.Item>
                <Form.List name="public_details">{(fields, { add, remove }) => <div>
                    <div style={{ color: '#cbd5e1', marginBottom: 8 }}>Thông tin riêng hiển thị cho khách</div>
                    {fields.map((field) => <div key={field.key} style={{ display: 'flex', gap: 8 }}>
                        <Form.Item name={[field.name, 'label']} rules={[{ required: true }]} style={{ flex: 1 }}><Input placeholder="Tên thuộc tính" /></Form.Item>
                        <Form.Item name={[field.name, 'value']} rules={[{ required: true }]} style={{ flex: 1 }}><Input placeholder="Giá trị" /></Form.Item>
                        <Button danger onClick={() => remove(field.name)}>Xóa</Button>
                    </div>)}
                    <Button type="dashed" onClick={() => add()} block>Thêm thông tin</Button>
                </div>}</Form.List>
                <Button type="primary" htmlType="submit" block style={{ marginTop: 16 }}>Lưu thay đổi</Button>
            </Form>
        </Modal>
        <Modal title="Thông tin tài khoản đã giải mã (đã ghi audit log)" open={!!viewData} onCancel={() => setViewData(null)} footer={<Button type="primary" onClick={() => setViewData(null)}>Đóng</Button>}>
            {viewData && <Descriptions column={1} bordered style={{ marginTop: 16 }}>
                <Descriptions.Item label="Sản phẩm">{viewData.product?.name}</Descriptions.Item>
                <Descriptions.Item label="Tên đăng nhập">{viewData.credentials?.username}</Descriptions.Item>
                <Descriptions.Item label="Mật khẩu">{viewData.credentials?.password}</Descriptions.Item>
                <Descriptions.Item label="Email">{viewData.credentials?.extra?.sec_email || 'Không có'}</Descriptions.Item>
                <Descriptions.Item label="Điện thoại">{viewData.credentials?.extra?.sec_phone || 'Không có'}</Descriptions.Item>
                <Descriptions.Item label="Thông tin thêm">{viewData.credentials?.extra?.additional_info || 'Không có'}</Descriptions.Item>
            </Descriptions>}
        </Modal>
    </div>;
};

export default InventoryPage;
