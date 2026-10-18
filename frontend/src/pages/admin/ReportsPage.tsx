import React, { useEffect, useState } from 'react';
import { Row, Col, Spin, Empty, Tag } from 'antd';
import { BarChartOutlined, PieChartOutlined } from '@ant-design/icons';
import api from '../../services/api';

const AdminReportsPage: React.FC = () => {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchReport = async () => {
      try {
        const res = await api.get('/admin/reports/revenue');
        if (res.data?.data) {
          setData(res.data.data);
        }
      } catch (e) {
        console.error('Failed to load reports from DB', e);
      } finally {
        setLoading(false);
      }
    };
    fetchReport();
  }, []);

  const totalGameRevenue = data?.game_revenue?.reduce((acc: number, g: any) => acc + Number(g.total_amount || 0), 0) || 0;
  const totalTxCount = data?.payment_stats?.reduce((acc: number, p: any) => acc + Number(p.tx_count || 0), 0) || 0;

  return (
    <div>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        BÁO CÁO DOANH THU & PHÂN TÍCH (DB) 📈
      </h1>

      {loading ? (
        <div style={{ textAlign: 'center', padding: '60px 0' }}><Spin size="large" /></div>
      ) : (
        <Row gutter={[20, 20]}>
          <Col xs={24} md={12}>
            <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)', height: '100%' }}>
              <h3 style={{ color: '#fff', marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <BarChartOutlined style={{ color: '#06b6d4' }} /> Doanh Thu Theo Tựa Game (Từ Database)
              </h3>
              {data?.game_revenue && data.game_revenue.length > 0 ? (
                <div style={{ color: '#cbd5e1', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  {data.game_revenue.map((g: any) => {
                    const amt = Number(g.total_amount || 0);
                    const pct = totalGameRevenue > 0 ? Math.round((amt / totalGameRevenue) * 100) : 0;
                    return (
                      <div key={g.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span>• <strong>{g.name}</strong>:</span>
                        <span>
                          <strong style={{ color: '#06b6d4' }}>{amt.toLocaleString('vi-VN')} đ</strong>{' '}
                          <Tag color="cyan">{pct}%</Tag>
                        </span>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <Empty description={<span style={{ color: '#94a3b8' }}>Chưa có phát sinh doanh thu</span>} />
              )}
            </div>
          </Col>

          <Col xs={24} md={12}>
            <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)', height: '100%' }}>
              <h3 style={{ color: '#fff', marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <PieChartOutlined style={{ color: '#10b981' }} /> Phương Thức Thanh Toán (Từ Database)
              </h3>
              {data?.payment_stats && data.payment_stats.length > 0 ? (
                <div style={{ color: '#cbd5e1', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  {data.payment_stats.map((p: any) => {
                    const count = Number(p.tx_count || 0);
                    const pct = totalTxCount > 0 ? Math.round((count / totalTxCount) * 100) : 0;
                    return (
                      <div key={p.code} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span>• <strong>{p.name}</strong>:</span>
                        <span>
                          <strong style={{ color: '#10b981' }}>{count} giao dịch</strong>{' '}
                          <Tag color="green">{pct}%</Tag>
                        </span>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <Empty description={<span style={{ color: '#94a3b8' }}>Chưa có giao dịch thanh toán</span>} />
              )}
            </div>
          </Col>
        </Row>
      )}
    </div>
  );
};

export default AdminReportsPage;
