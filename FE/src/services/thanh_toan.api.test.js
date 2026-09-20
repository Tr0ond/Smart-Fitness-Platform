import { beforeEach, describe, expect, it, vi } from 'vitest'

const { get, post, patch, put, delete: xoa } = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}))

vi.mock('./api.js', () => ({ default: { get, post, patch, put, delete: xoa } }))

import {
  laIdThanhToanHopLe,
  taiChiTietThanhToan,
  taiDanhSachCanDoiSoat,
  taiDanhSachThanhToan,
  taiSuKienThanhToan,
} from './thanh_toan.api.js'

beforeEach(() => {
  vi.clearAllMocks()
  get.mockResolvedValue({ data: { data: { items: [], pagination: {} } } })
})

describe('thanh_toan.api FE4-ALL', () => {
  it('gui allow-list Payment voi GET va loai field ngoai contract', async () => {
    await taiDanhSachThanhToan({
      order_code: ' ORD-01 ',
      member: 'member@example.com',
      payment_status: 'THANH_CONG',
      order_status: 'DA_THANH_TOAN',
      provider_order_code: '123',
      provider_reference: 'REF-01',
      reconciliation_required: '1',
      from: '2026-09-01',
      to: '2026-09-20',
      sort_by: 'confirmed_at',
      sort_direction: 'desc',
      page: '2',
      per_page: 50,
      branch_id: 99,
    })

    expect(get).toHaveBeenCalledWith('/admin/payments', {
      params: {
        order_code: 'ORD-01',
        member: 'member@example.com',
        payment_status: 'THANH_CONG',
        order_status: 'DA_THANH_TOAN',
        provider_order_code: 123,
        provider_reference: 'REF-01',
        reconciliation_required: '1',
        from: '2026-09-01',
        to: '2026-09-20',
        sort_by: 'confirmed_at',
        sort_direction: 'desc',
        page: 2,
        per_page: 50,
      },
    })
    expect(get.mock.calls[0][1]).not.toHaveProperty('data')
    expect(get.mock.calls[0][1]).not.toHaveProperty('headers')
    expect(post).not.toHaveBeenCalled()
    expect(patch).not.toHaveBeenCalled()
    expect(put).not.toHaveBeenCalled()
    expect(xoa).not.toHaveBeenCalled()
  })

  it('bat buoc hang doi Payment dung co Backend va khong client join event', async () => {
    await taiDanhSachCanDoiSoat({
      payment_status: 'CAN_DOI_SOAT',
      reconciliation_required: '0',
      page: 3,
    })

    expect(get).toHaveBeenCalledWith('/admin/payments', {
      params: {
        payment_status: 'CAN_DOI_SOAT',
        reconciliation_required: 1,
        page: 3,
      },
    })
  })

  it('gui queue event doc lap va giu allow-list processing_status', async () => {
    await taiSuKienThanhToan({
      processing_status: 'CHO_THU_LAI',
      provider_order_code: 77,
      provider_reference: 'evt-77',
      from: '2026-09-01',
      to: '2026-09-02',
      sort_by: 'received_at',
      sort_direction: 'asc',
      page: 2,
      per_page: 10,
      payment_id: 1,
    })

    expect(get).toHaveBeenCalledWith('/admin/payment-events', {
      params: {
        processing_status: 'CHO_THU_LAI',
        provider_order_code: 77,
        provider_reference: 'evt-77',
        from: '2026-09-01',
        to: '2026-09-02',
        sort_by: 'received_at',
        sort_direction: 'asc',
        page: 2,
        per_page: 10,
        reconciliation_required: 1,
      },
    })
  })

  it('detail chi nhan ID duong an toan va khong goi mang khi ID sai', async () => {
    await taiChiTietThanhToan('17')
    expect(get).toHaveBeenCalledWith('/admin/payments/17')

    for (const id of [0, -1, 1.2, '0', '1.2', '', '9007199254740992', null, {}]) {
      await expect(taiChiTietThanhToan(id)).rejects.toMatchObject({
        httpStatus: 404,
        code: 'PAYMENT_ID_INVALID',
      })
    }

    expect(get).toHaveBeenCalledTimes(1)
  })

  it('chan enum/date/range sai truoc khi GET', async () => {
    await expect(taiDanhSachThanhToan({ payment_status: 'UNKNOWN' })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiDanhSachThanhToan({ from: '2026-02-30' })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiDanhSachThanhToan({ from: '2026-09-20', to: '2026-09-01' })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiDanhSachThanhToan({ per_page: 101 })).rejects.toMatchObject({ httpStatus: 422 })
    expect(get).not.toHaveBeenCalled()
  })

  it('xac nhan ID null cung hop le cho DTO event nhung khong tao API detail', () => {
    expect(laIdThanhToanHopLe(1)).toBe(true)
    expect(laIdThanhToanHopLe('2')).toBe(true)
    expect(laIdThanhToanHopLe(null)).toBe(false)
    expect(laIdThanhToanHopLe('')).toBe(false)
    expect(laIdThanhToanHopLe(Number.NaN)).toBe(false)
  })
})
