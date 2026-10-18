import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';

const WarrantyClaimDetailPage: React.FC = () => {
  const { code } = useParams<{ code: string }>();

  return (
    <div style={{ maxWidth: '800px', margin: '40px auto', padding: '0 20px' }}>
      <div style={{ background: '#121324', borderRadius: '16px', padding: '32px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <h2 style={{ color: '#fff', marginBottom: '16px' }}>Chi tiết khiếu nại {code}</h2>
        <p style={{ color: '#94a3b8' }}>Kỹ thuật viên đang xem xét và phản hồi trong thời gian sớm nhất.</p>
        <Link to="/warranty-claims">
          <Button icon={<ArrowLeftOutlined />} style={{ marginTop: '16px' }}>
            Quay lại danh sách
          </Button>
        </Link>
      </div>
    </div>
  );
};

export default WarrantyClaimDetailPage;
