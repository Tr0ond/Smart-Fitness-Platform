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
}))

vi.mock('../../../services/thanh_toan.api.js', () => ({
  ...api,
  CAC_TRANG_THAI_THANH_TOAN: [],
  CAC_TRANG_THAI_DON_MUA: [],
  CAC_TRANG_THAI_SU_KIEN: [],
  CAC_COT_SAP_XEP_THANH_TOAN: [],
  CAC_COT_SAP_XEP_SU_KIEN: [],
}))

import ThanhToanChiTiet from './thanh_toan.chi_tiet.vue'
import { useThanhToanStore } from '../../../stores/thanh_toan.store.js'

const THANH_TOAN = {
  payment_id: 7,
  status: 'THANH_CONG',
  attempt: 1,
  channel: 'BANK_TRANSFER',
  provider_order_code: 701,
  provider_reference: 'REF-701',
  expected_amount: 100000,
  received_amount: 100000,
  currency: 'VND',
  paid_at: '2026-09-20T10:00:00+07:00',
  confirmed_at: '2026-09-20T10:01:00+07:00',
  order: {
    id: 8,
    code: 'ORD-07',
    status: 'DA_THANH_TOAN',
    expected_amount: 100000,
    currency: 'VND',
    price_locked_at: '2026-09-20T09:00:00+07:00',
  },
  member: { id: 9, code: 'HV-07', email: 'member@example.com' },
  membership_term: {
    id: 10,
    status: 'CHO_KICH_HOAT',
    sequence: 2,
    package_name: 'Gói Plus',
    package_version: 3,
    purchase_price: 100000,
  },
  events: [{
    event_id: 71,
    payment_id: 7,
    provider_reference: 'REF-701',
    amount: 100000,
    currency: 'VND',
    processing_status: 'DA_XU_LY',
    received_at: '2026-09-20T10:00:00+07:00',
    processed_at: '2026-09-20T10:01:00+07:00',
  }],
}

const THANH_TOAN_NHIEM = {
  ...THANH_TOAN,
  raw_payload: 'RAW-DETAIL-SENTINEL',
  signature: 'SIGNATURE-DETAIL-SENTINEL',
  hash: 'HASH-DETAIL-SENTINEL',
  idempotency_key: 'IDEMPOTENCY-DETAIL-SENTINEL',
  secret: 'SECRET-DETAIL-SENTINEL',
  order: {
    ...THANH_TOAN.order,
    raw_payload: 'RAW-ORDER-DETAIL-SENTINEL',
    signature: 'SIGNATURE-ORDER-DETAIL-SENTINEL',
    hash: 'HASH-ORDER-DETAIL-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-ORDER-DETAIL-SENTINEL',
    secret: 'SECRET-ORDER-DETAIL-SENTINEL',
  },
  member: {
    ...THANH_TOAN.member,
    raw_payload: 'RAW-MEMBER-DETAIL-SENTINEL',
    signature: 'SIGNATURE-MEMBER-DETAIL-SENTINEL',
    hash: 'HASH-MEMBER-DETAIL-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-MEMBER-DETAIL-SENTINEL',
    secret: 'SECRET-MEMBER-DETAIL-SENTINEL',
  },
  membership_term: {
    ...THANH_TOAN.membership_term,
    raw_payload: 'RAW-TERM-DETAIL-SENTINEL',
    signature: 'SIGNATURE-TERM-DETAIL-SENTINEL',
    hash: 'HASH-TERM-DETAIL-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-TERM-DETAIL-SENTINEL',
    secret: 'SECRET-TERM-DETAIL-SENTINEL',
  },
  events: [{
    ...THANH_TOAN.events[0],
    raw_payload: 'RAW-EVENT-DETAIL-SENTINEL',
    signature: 'SIGNATURE-EVENT-DETAIL-SENTINEL',
    hash: 'HASH-EVENT-DETAIL-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-EVENT-DETAIL-SENTINEL',
    secret: 'SECRET-EVENT-DETAIL-SENTINEL',
  }],
}

function deferred() {
  let resolve
  let reject
  const promise = new Promise((resolvePromise, rejectPromise) => {
    resolve = resolvePromise
    reject = rejectPromise
  })
  return { promise, resolve, reject }
}

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/thanh-toan', name: 'adminThanhToan', component: { template: '<h1>List</h1>' } },
      { path: '/admin/thanh-toan/:id', name: 'adminChiTietThanhToan', component: { template: '<h1>Detail</h1>' } },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang() {
  const router = taoRouter()
  await router.push({ name: 'adminChiTietThanhToan', params: { id: 7 } })
  await router.isReady()
  const pinia = createPinia()
  setActivePinia(pinia)
  const wrapper = mount(ThanhToanChiTiet, { global: { plugins: [pinia, router] } })
  await flushPromises()
  return { router, wrapper }
}

beforeEach(() => {
  vi.resetAllMocks()
  api.taiChiTietThanhToan.mockResolvedValue({ data: THANH_TOAN })
  setActivePinia(createPinia())
})

describe('thanh_toan.chi_tiet FE4-ALL', () => {
  it('render Safe DTO, Payment ownership tach biet voi Membership state', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Payment #7')
    expect(wrapper.text()).toContain('Thanh toán thành công')
    expect(wrapper.text()).toContain('Chờ kích hoạt')
    expect(wrapper.text()).toContain('Gói Plus')
    expect(wrapper.text()).toContain('REF-701')
    expect(wrapper.text()).toContain('Thanh toán thành công xác nhận quyền sở hữu')
    expect(wrapper.text()).not.toContain('Hoàn tiền')
    expect(wrapper.text()).not.toContain('Xác nhận thanh toán')
  })

  it('khong render sensitive fields tu detail response', async () => {
    api.taiChiTietThanhToan.mockResolvedValueOnce({ data: THANH_TOAN_NHIEM })
    const { wrapper } = await mountTrang()

    const noiDung = wrapper.text()
    ;[
      'RAW-DETAIL-SENTINEL',
      'SIGNATURE-DETAIL-SENTINEL',
      'HASH-DETAIL-SENTINEL',
      'IDEMPOTENCY-DETAIL-SENTINEL',
      'SECRET-DETAIL-SENTINEL',
      'RAW-EVENT-DETAIL-SENTINEL',
      'SECRET-EVENT-DETAIL-SENTINEL',
    ].forEach((sentinel) => expect(noiDung).not.toContain(sentinel))
  })

  it('unmount detail clears state and invalidates late response', async () => {
    const pending = deferred()
    api.taiChiTietThanhToan.mockReturnValueOnce(pending.promise)
    const router = taoRouter()
    await router.push({ name: 'adminChiTietThanhToan', params: { id: 7 } })
    await router.isReady()
    const pinia = createPinia()
    setActivePinia(pinia)
    const wrapper = mount(ThanhToanChiTiet, { global: { plugins: [pinia, router] } })
    await flushPromises()
    const store = useThanhToanStore(pinia)
    const sequenceTruoc = store.soThuTuChiTiet

    expect(store.dangTaiChiTiet).toBe(true)
    wrapper.unmount()

    expect(store.chiTietThanhToan).toBeNull()
    expect(store.dangTaiChiTiet).toBe(false)
    expect(store.loiChiTiet).toBeNull()
    expect(store.daTaiChiTietLanDau).toBe(false)
    expect(store.soThuTuChiTiet).toBeGreaterThan(sequenceTruoc)

    pending.resolve({ data: THANH_TOAN })
    await flushPromises()
    expect(store.chiTietThanhToan).toBeNull()
    expect(store.dangTaiChiTiet).toBe(false)
    expect(store.loiChiTiet).toBeNull()
  })

  it('404 hien thong bao generic va khong lo code Backend', async () => {
    api.taiChiTietThanhToan.mockRejectedValueOnce({
      httpStatus: 404,
      code: 'PAYMENT_NOT_FOUND',
      message: 'internal detail message',
    })
    const { wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Không tìm thấy thông tin thanh toán.')
    expect(wrapper.text()).not.toContain('PAYMENT_NOT_FOUND')
    expect(wrapper.text()).not.toContain('internal detail message')
  })

  it('malformed response fail closed co retry va 403 chuyen forbidden', async () => {
    api.taiChiTietThanhToan.mockResolvedValueOnce({ data: { payment_id: 7 } })
    const { router, wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Không thể tải chi tiết thanh toán.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)

    api.taiChiTietThanhToan.mockRejectedValueOnce({ httpStatus: 403, message: 'Forbidden' })
    await router.push({ name: 'adminChiTietThanhToan', params: { id: 8 } })
    await flushPromises()
    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })
})
