import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Form, Input, InputNumber, Select, Button, message } from 'antd';
import api from '../../services/api';

const ProductFormPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [form] = Form.useForm();
  const [games, setGames] = useState<any[]>([]);
  const [categories, setCategories] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchMeta = async () => {
      try {
        const [gRes, cRes] = await Promise.all([
          api.get('/games'),
          api.get('/categories'),
        ]);
        if (gRes.data?.data) setGames(gRes.data.data);
        if (cRes.data?.data) setCategories(cRes.data.data);
      } catch (e) {}
    };
    fetchMeta();

    if (id) {
      const fetchProd = async () => {
        try {
          const res = await api.get(`/admin/products/${id}`);
          if (res.data?.data) {
            form.setFieldsValue(res.data.data);
          }
        } catch (e) {}
      };
      fetchProd();
    }
  }, [id]);

  const onFinish = async (values: any) => {
    setLoading(true);
    try {
      if (id) {
        await api.put(`/admin/products/${id}`, values);
        message.success('Cập nhật sản phẩm thành công!');
      } else {
        await api.post('/admin/products', values);
        message.success('Tạo sản phẩm thành công!');
      }
      navigate('/admin/products');
    } catch (err: any) {
      message.error(err.response?.data?.message || 'Có lỗi xảy ra khi lưu sản phẩm.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ maxWidth: '800px', margin: '0 auto' }}>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        {id ? 'CẬP NHẬT SẢN PHẨM' : 'THÊM SẢN PHẨM MỚI'} ✏️
      </h1>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '32px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Form form={form} layout="vertical" onFinish={onFinish} initialValues={{ type: 'ACCOUNT', status: 'ACTIVE' }}>
          <Form.Item name="name" label="Tên sản phẩm" rules={[{ required: true }]}>
            <Input size="large" placeholder="Ví dụ: Acc Liên Quân #9999 VIP" />
          </Form.Item>

          <Form.Item name="game_id" label="Trò chơi" rules={[{ required: true }]}>
            <Select placeholder="Chọn trò chơi">
              {games.map((g) => <Select.Option key={g.id} value={g.id}>{g.name}</Select.Option>)}
            </Select>
          </Form.Item>

          <Form.Item name="category_id" label="Danh mục" rules={[{ required: true }]}>
            <Select placeholder="Chọn danh mục">
              {categories.map((c) => <Select.Option key={c.id} value={c.id}>{c.name}</Select.Option>)}
            </Select>
          </Form.Item>

          <Form.Item name="price" label="Giá bán (VNĐ)" rules={[{ required: true }]}>
            <InputNumber style={{ width: '100%' }} size="large" min={0} />
          </Form.Item>

          <Form.Item name="thumbnail_url" label="Link hình ảnh đại diện">
            <Input placeholder="https://..." />
          </Form.Item>

          <Form.Item name="short_description" label="Mô tả ngắn">
            <Input.TextArea rows={2} />
          </Form.Item>

          <Form.Item name="description" label="Mô tả chi tiết">
            <Input.TextArea rows={4} />
          </Form.Item>

          <Button type="primary" size="large" block htmlType="submit" loading={loading} style={{ marginTop: '16px', fontWeight: 700 }}>
            Lưu Sản Phẩm
          </Button>
        </Form>
      </div>
    </div>
  );
};

export default ProductFormPage;
