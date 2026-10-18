import { Descriptions, Tag, Avatar } from 'antd';
import { UserOutlined } from '@ant-design/icons';
import { useAuthStore } from '../stores/authStore';

const ProfilePage: React.FC = () => {
  const { user } = useAuthStore();

  return (
    <div style={{ maxWidth: '800px', margin: '40px auto', padding: '0 20px' }}>
      <div style={{ background: '#121324', borderRadius: '16px', padding: '36px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '20px', marginBottom: '28px' }}>
          <Avatar size={72} icon={<UserOutlined />} style={{ backgroundColor: '#6366f1' }} src={user?.avatar_url} />
          <div>
            <h2 style={{ color: '#fff', fontSize: '22px', fontWeight: 700, margin: 0 }}>
              {user?.full_name || 'Khách Hàng'}
            </h2>
            <div style={{ color: '#94a3b8', fontSize: '14px', marginTop: '4px' }}>{user?.email}</div>
            <div style={{ marginTop: '8px' }}>
              <Tag color="cyan">Thành viên Verified</Tag>
              <Tag color="green">Bảo mật 2FA</Tag>
            </div>
          </div>
        </div>

        <Descriptions
          title={<span style={{ color: '#fff' }}>Thông tin chi tiết</span>}
          bordered
          column={1}
          style={{ background: 'transparent' }}
        >
          <Descriptions.Item label="Họ và tên">{user?.full_name || '---'}</Descriptions.Item>
          <Descriptions.Item label="Email">{user?.email || '---'}</Descriptions.Item>
          <Descriptions.Item label="Số điện thoại">{user?.phone || 'Chưa cập nhật'}</Descriptions.Item>
          <Descriptions.Item label="Trạng thái tài khoản">
            <Tag color="success">Đang hoạt động</Tag>
          </Descriptions.Item>
        </Descriptions>
      </div>
    </div>
  );
};

export default ProfilePage;
