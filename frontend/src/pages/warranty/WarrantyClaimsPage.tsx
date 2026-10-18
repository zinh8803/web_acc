import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Modal, Form, Input, Select, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import api from '../../services/api';

const WarrantyClaimsPage: React.FC = () => {
  const [claims, setClaims] = useState<any[]>([]);
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [form] = Form.useForm();

  const fetchClaims = async () => {
    try {
      const res = await api.get('/warranty-claims');
      const list = res.data?.data?.data || res.data?.data || [];
      setClaims(Array.isArray(list) ? list : []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const fetchPaidOrders = async () => {
    try {
      const res = await api.get('/orders');
      const list = res.data?.data?.data || res.data?.data || [];
      const paid = list.filter((o: any) => ['completed', 'paid', 'COMPLETED', 'PAID'].includes(o.status));
      setOrders(paid);
    } catch (e) {}
  };

  useEffect(() => {
    fetchClaims();
    fetchPaidOrders();
  }, []);

  const handleCreateClaim = async (values: any) => {
    try {
      const res = await api.post('/warranty-claims', {
        order_item_id: values.order_item_id,
        reason: values.reason,
        description: values.description,
      });
      if (res.data?.success) {
        message.success('Đã gửi yêu cầu bảo hành thành công!');
        setIsModalOpen(false);
        form.resetFields();
        fetchClaims();
      }
    } catch (err: any) {
      message.error(err.response?.data?.message || 'Không thể gửi khiếu nại bảo hành.');
    }
  };

  const columns = [
    {
      title: 'Mã khiếu nại',
      dataIndex: 'claim_code',
      key: 'claim_code',
      render: (code: string) => <Tag color="orange" style={{ fontWeight: 700 }}>{code}</Tag>,
    },
    {
      title: 'Lý do',
      dataIndex: 'reason',
      key: 'reason',
      render: (r: string) => <span style={{ color: '#fff', fontWeight: 600 }}>{r}</span>,
    },
    {
      title: 'Mô tả chi tiết',
      dataIndex: 'description',
      key: 'description',
      ellipsis: true,
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      render: (st: string) => {
        switch (st?.toLowerCase()) {
          case 'resolved':
          case 'approved':
            return <Tag color="success">ĐÃ XỬ LÝ / ĐỔI MỚI</Tag>;
          case 'rejected':
            return <Tag color="error">TỪ CHỐI</Tag>;
          default:
            return <Tag color="processing">ĐANG XỬ LÝ (SLA 24H)</Tag>;
        }
      },
    },
    {
      title: 'Ngày gửi',
      dataIndex: 'created_at',
      key: 'created_at',
      render: (d: string) => new Date(d).toLocaleDateString('vi-VN'),
    },
  ];

  return (
    <div style={{ maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <h1 style={{ color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            TRUNG TÂM BẢO HÀNH & KHIẾU NẠI 🛡️
          </h1>
          <p style={{ color: '#94a3b8', fontSize: '14px', marginTop: '4px' }}>
            Cam kết giải quyết trong 24h. Bảo hành 1 đổi 1 hoặc hoàn tiền nếu acc bị lỗi, sai pass, back nick.
          </p>
        </div>

        <Button
          type="primary"
          icon={<PlusOutlined />}
          onClick={() => setIsModalOpen(true)}
          style={{ height: '42px', fontWeight: 700, borderRadius: '8px' }}
        >
          Gửi Yêu Cầu Bảo Hành
        </Button>
      </div>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={claims} rowKey="id" loading={loading} pagination={{ pageSize: 8 }} />
      </div>

      {/* Create Claim Modal */}
      <Modal
        title={<span style={{ color: '#fff' }}>Gửi Yêu Cầu Khiếu Nại Bảo Hành</span>}
        open={isModalOpen}
        onCancel={() => setIsModalOpen(false)}
        footer={null}
      >
        <Form form={form} layout="vertical" onFinish={handleCreateClaim}>
          <Form.Item
            name="order_item_id"
            label="Chọn sản phẩm trong đơn đã mua"
            rules={[{ required: true, message: 'Vui lòng chọn sản phẩm cần bảo hành!' }]}
          >
            <Select placeholder="Chọn sản phẩm">
              {orders.flatMap((o) =>
                (o.items || []).map((i: any) => (
                  <Select.Option key={i.id} value={i.id}>
                    {o.order_code} - {i.product_name || 'Tài khoản'}
                  </Select.Option>
                ))
              )}
            </Select>
          </Form.Item>

          <Form.Item
            name="reason"
            label="Lý do bảo hành"
            rules={[{ required: true, message: 'Vui lòng chọn lý do!' }]}
          >
            <Select placeholder="Chọn lý do">
              <Select.Option value="Sai mật khẩu hoặc tài khoản">Sai mật khẩu hoặc tài khoản</Select.Option>
              <Select.Option value="Bị chủ cũ đổi thông tin (Back nick)">Bị chủ cũ đổi thông tin (Back nick)</Select.Option>
              <Select.Option value="Thiếu tướng/skin so với mô tả">Thiếu tướng/skin so với mô tả</Select.Option>
              <Select.Option value="Khác">Lý do khác</Select.Option>
            </Select>
          </Form.Item>

          <Form.Item
            name="description"
            label="Mô tả chi tiết sự cố"
            rules={[{ required: true, message: 'Vui lòng mô tả chi tiết!' }]}
          >
            <Input.TextArea rows={4} placeholder="Mô tả cụ thể thời gian gặp lỗi và tình trạng tài khoản..." />
          </Form.Item>

          <Button type="primary" block htmlType="submit" size="large" style={{ fontWeight: 700 }}>
            Gửi Yêu Cầu Cho Đội Kỹ Thuật
          </Button>
        </Form>
      </Modal>
    </div>
  );
};

export default WarrantyClaimsPage;
