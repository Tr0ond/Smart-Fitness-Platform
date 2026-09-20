import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

const api = vi.hoisted(() => ({
  taiDanhSachThanhToan: vi.fn(),
  taiChiTietThanhToan: vi.fn(),
  taiDanhSachCanDoiSoat: vi.fn(),
  taiSuKienThanhToan: vi.fn(),
  laIdThanhToanHopLe: (id) => Number.isSafeInteger(Number(id)) && Number(id) > 0,
  CAC_TRANG_THAI_THANH_TOAN: ['DANG_TAO', 'CHO_THANH_TOAN', 'THANH_CONG', 'THAT_BAI', 'HUY', 'HET_HAN', 'CAN_DOI_SOAT'],
  CAC_TRANG_THAI_DON_MUA: ['CHO_THANH_TOAN', 'DA_THANH_TOAN', 'HET_HAN', 'HUY', 'CAN_DOI_SOAT'],
  CAC_TRANG_THAI_SU_KIEN: ['CHO_XU_LY', 'DA_XU_LY', 'BI_TU_CHOI', 'CAN_DOI_SOAT', 'CHO_THU_LAI'],
  CAC_COT_SAP_XEP_THANH_TOAN: ['created_at', 'confirmed_at', 'expected_amount', 'received_amount'],
  CAC_COT_SAP_XEP_SU_KIEN: ['received_at', 'processed_at', 'created_at'],
}))

vi.mock('../../../services/thanh_toan.api.js', () => api)

import DoiSoatThanhToan from './doi_soat_thanh_toan.index.vue'

const THANH_TOAN = {
  payment_id: 7,
  status: 'CAN_DOI_SOAT',
  order: { code: 'ORD-07', status: 'CAN_DOI_SOAT' },
  member: { code: 'HV-07' },
  membership_term: { status: 'CHO_KICH_HOAT' },
  events: [],
}

const EVENT_CHUA_LIEN_KET = {
  event_id: 71,
  payment_id: null,
  provider_reference: 'UNLINKED-71',
  amount: 100000,
  currency: 'VND',
  processing_status: 'CAN_DOI_SOAT',
  reconciliation_reason: 'UNKNOWN_PROVIDER_ORDER',
  received_at: '2026-09-20T10:00:00+07:00',
  processed_at: null,
}

const EVENT_LIEN_KET = {
  ...EVENT_CHUA_LIEN_KET,
  event_id: 72,
  payment_id: 7,
  provider_reference: 'LINKED-72',
}

const THANH_TOAN_NHIEM = {
  ...THANH_TOAN,
  raw_payload: 'RAW-RECON-PAYMENT-SENTINEL',
  signature: 'SIGNATURE-RECON-PAYMENT-SENTINEL',
  hash: 'HASH-RECON-PAYMENT-SENTINEL',
  idempotency_key: 'IDEMPOTENCY-RECON-PAYMENT-SENTINEL',
  secret: 'SECRET-RECON-PAYMENT-SENTINEL',
  order: {
    ...THANH_TOAN.order,
    raw_payload: 'RAW-RECON-ORDER-SENTINEL',
    signature: 'SIGNATURE-RECON-ORDER-SENTINEL',
    hash: 'HASH-RECON-ORDER-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-RECON-ORDER-SENTINEL',
    secret: 'SECRET-RECON-ORDER-SENTINEL',
  },
  member: {
    ...THANH_TOAN.member,
    raw_payload: 'RAW-RECON-MEMBER-SENTINEL',
    signature: 'SIGNATURE-RECON-MEMBER-SENTINEL',
    hash: 'HASH-RECON-MEMBER-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-RECON-MEMBER-SENTINEL',
    secret: 'SECRET-RECON-MEMBER-SENTINEL',
  },
  membership_term: {
    ...THANH_TOAN.membership_term,
    raw_payload: 'RAW-RECON-TERM-SENTINEL',
    signature: 'SIGNATURE-RECON-TERM-SENTINEL',
    hash: 'HASH-RECON-TERM-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-RECON-TERM-SENTINEL',
    secret: 'SECRET-RECON-TERM-SENTINEL',
  },
}

const EVENT_NHIEM = {
  ...EVENT_CHUA_LIEN_KET,
  raw_payload: 'RAW-RECON-EVENT-SENTINEL',
  signature: 'SIGNATURE-RECON-EVENT-SENTINEL',
  hash: 'HASH-RECON-EVENT-SENTINEL',
  idempotency_key: 'IDEMPOTENCY-RECON-EVENT-SENTINEL',
  secret: 'SECRET-RECON-EVENT-SENTINEL',
}

function phanHoi(items, pagination = {}) {
  return {
    data: {
      items,
      pagination: {
        current_page: pagination.current_page ?? 1,
        per_page: pagination.per_page ?? 20,
        total: pagination.total ?? items.length,
        last_page: pagination.last_page ?? 1,
      },
    },
  }
}

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/doi-soat-thanh-toan', name: 'adminDoiSoatThanhToan', component: { template: '<h1>Reconciliation</h1>' } },
      { path: '/admin/thanh-toan/:id', name: 'adminChiTietThanhToan', component: { template: '<h1>Detail</h1>' } },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang() {
  const router = taoRouter()
  await router.push({ name: 'adminDoiSoatThanhToan' })
  await router.isReady()
  const pinia = createPinia()
  setActivePinia(pinia)
  const wrapper = mount(DoiSoatThanhToan, { global: { plugins: [pinia, router] } })
  await flushPromises()
  return { router, wrapper }
}

beforeEach(() => {
  vi.resetAllMocks()
  api.taiDanhSachCanDoiSoat.mockResolvedValue(phanHoi([THANH_TOAN]))
  api.taiSuKienThanhToan.mockResolvedValue(phanHoi([EVENT_CHUA_LIEN_KET, EVENT_LIEN_KET]))
  setActivePinia(createPinia())
})

describe('doi_soat_thanh_toan.index FE4-ALL', () => {
  it('render hai queue doc lap va event payment_id null khong tao lien ket gia', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Payment cần đối soát')
    expect(wrapper.text()).toContain('Sự kiện thanh toán cần đối soát')
    expect(wrapper.text()).toContain('ORD-07')
    expect(wrapper.text()).toContain('UNLINKED-71')
    expect(wrapper.text()).toContain('Chưa liên kết với Payment')
    expect(wrapper.text()).toContain('#7')
    expect(wrapper.findAll('a[href*="/admin/thanh-toan/7"]').length).toBeGreaterThan(0)
    expect(wrapper.get('section[aria-label="Đối soát thanh toán"]').attributes('aria-label'))
      .toBe('Đối soát thanh toán')
    expect(wrapper.findAll('section[aria-labelledby]').length).toBeGreaterThanOrEqual(2)
  })

  it('khong render sensitive fields tu hai queue response', async () => {
    api.taiDanhSachCanDoiSoat.mockResolvedValueOnce(phanHoi([THANH_TOAN_NHIEM]))
    api.taiSuKienThanhToan.mockResolvedValueOnce(phanHoi([EVENT_NHIEM]))
    const { wrapper } = await mountTrang()

    const noiDung = wrapper.text()
    ;[
      'RAW-RECON-PAYMENT-SENTINEL',
      'SIGNATURE-RECON-PAYMENT-SENTINEL',
      'HASH-RECON-PAYMENT-SENTINEL',
      'IDEMPOTENCY-RECON-PAYMENT-SENTINEL',
      'SECRET-RECON-PAYMENT-SENTINEL',
      'RAW-RECON-EVENT-SENTINEL',
      'SIGNATURE-RECON-EVENT-SENTINEL',
      'HASH-RECON-EVENT-SENTINEL',
      'IDEMPOTENCY-RECON-EVENT-SENTINEL',
      'SECRET-RECON-EVENT-SENTINEL',
    ].forEach((sentinel) => expect(noiDung).not.toContain(sentinel))
  })

  it('gui filter hai endpoint doc lap va giu server pagination rieng', async () => {
    const { wrapper } = await mountTrang()
    const forms = wrapper.findAll('.bo-loc-danh-sach')

    await forms[0].find('input[type="search"]').setValue('ORD-07')
    await forms[0].trigger('submit')
    await flushPromises()
    expect(api.taiDanhSachCanDoiSoat).toHaveBeenLastCalledWith(expect.objectContaining({
      order_code: 'ORD-07',
      page: 1,
    }))

    await forms[1].find('input[type="search"]').setValue('UNLINKED-71')
    await forms[1].trigger('submit')
    await flushPromises()
    expect(api.taiSuKienThanhToan).toHaveBeenLastCalledWith(expect.objectContaining({
      order_code: 'UNLINKED-71',
      page: 1,
    }))
  })

  it('co error retry rieng queue va 403 navigation dung chung', async () => {
    api.taiDanhSachCanDoiSoat.mockRejectedValueOnce({
      httpStatus: 503,
      code: 'SERVICE_UNAVAILABLE',
      message: 'Queue Payment tam thoi khong kha dung.',
    })
    const { router, wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Queue Payment tam thoi khong kha dung.')
    const loiPayment = wrapper.findAll('.trang-thai-loi')[0]
    expect(loiPayment.find('button').exists()).toBe(true)

    api.taiDanhSachCanDoiSoat.mockResolvedValueOnce(phanHoi([]))
    await loiPayment.find('button').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Không có Payment cần đối soát')

    api.taiSuKienThanhToan.mockRejectedValueOnce({ httpStatus: 403, message: 'Forbidden' })
    await wrapper.findAll('.bo-loc-danh-sach')[1].trigger('submit')
    await flushPromises()
    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })

  it('giu Payment rows va hien retained-data retry banner doc lap cho moi queue', async () => {
    const { wrapper } = await mountTrang()
    const forms = wrapper.findAll('.bo-loc-danh-sach')

    api.taiDanhSachCanDoiSoat.mockRejectedValueOnce({
      isNetworkError: true,
      code: 'NETWORK_ERROR',
      message: 'Payment queue network error.',
    })
    await forms[0].trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('ORD-07')
    expect(wrapper.text()).toContain('Payment queue network error.')
    expect(wrapper.find('#doi-soat-loi-payment-cap-nhat').exists()).toBe(true)
    expect(wrapper.find('#doi-soat-loi-su-kien-cap-nhat').exists()).toBe(false)
    expect(wrapper.text()).toContain('UNLINKED-71')

    const soLanGoiPaymentTruocRetry = api.taiDanhSachCanDoiSoat.mock.calls.length
    api.taiDanhSachCanDoiSoat.mockResolvedValueOnce(phanHoi([THANH_TOAN]))
    await wrapper.get('#doi-soat-loi-payment-cap-nhat button').trigger('click')
    await flushPromises()
    expect(api.taiDanhSachCanDoiSoat.mock.calls.length).toBe(soLanGoiPaymentTruocRetry + 1)
    expect(wrapper.find('#doi-soat-loi-payment-cap-nhat').exists()).toBe(false)
    expect(wrapper.text()).toContain('UNLINKED-71')

    api.taiSuKienThanhToan.mockRejectedValueOnce({
      httpStatus: 503,
      code: 'EVENT_QUEUE_UNAVAILABLE',
      message: 'Event queue 5xx error.',
    })
    await forms[1].trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('UNLINKED-71')
    expect(wrapper.text()).toContain('Event queue 5xx error.')
    expect(wrapper.find('#doi-soat-loi-su-kien-cap-nhat').exists()).toBe(true)
    expect(wrapper.find('#doi-soat-loi-payment-cap-nhat').exists()).toBe(false)
    expect(wrapper.text()).toContain('ORD-07')

    const soLanGoiSuKienTruocRetry = api.taiSuKienThanhToan.mock.calls.length
    api.taiSuKienThanhToan.mockResolvedValueOnce(phanHoi([EVENT_CHUA_LIEN_KET, EVENT_LIEN_KET]))
    await wrapper.get('#doi-soat-loi-su-kien-cap-nhat button').trigger('click')
    await flushPromises()
    expect(api.taiSuKienThanhToan.mock.calls.length).toBe(soLanGoiSuKienTruocRetry + 1)
    expect(wrapper.find('#doi-soat-loi-su-kien-cap-nhat').exists()).toBe(false)
  })
})
