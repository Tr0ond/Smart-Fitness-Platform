import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
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

import ThanhToanIndex from './thanh_toan.index.vue'

const THANH_TOAN = {
  payment_id: 7,
  status: 'THANH_CONG',
  reconciliation_reason: 'POST_SUCCESS_RECONCILIATION',
  expected_amount: 100000,
  received_amount: 100000,
  currency: 'VND',
  order: { code: 'ORD-07', status: 'DA_THANH_TOAN' },
  member: { code: 'HV-07', email: 'member@example.com' },
  membership_term: { status: 'CHO_KICH_HOAT' },
  events: [],
}

const THANH_TOAN_NHIEM = {
  ...THANH_TOAN,
  raw_payload: 'RAW-LIST-SENTINEL',
  signature: 'SIGNATURE-LIST-SENTINEL',
  hash: 'HASH-LIST-SENTINEL',
  idempotency_key: 'IDEMPOTENCY-LIST-SENTINEL',
  secret: 'SECRET-LIST-SENTINEL',
  order: {
    ...THANH_TOAN.order,
    raw_payload: 'RAW-ORDER-LIST-SENTINEL',
    signature: 'SIGNATURE-ORDER-LIST-SENTINEL',
    hash: 'HASH-ORDER-LIST-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-ORDER-LIST-SENTINEL',
    secret: 'SECRET-ORDER-LIST-SENTINEL',
  },
  member: {
    ...THANH_TOAN.member,
    raw_payload: 'RAW-MEMBER-LIST-SENTINEL',
    signature: 'SIGNATURE-MEMBER-LIST-SENTINEL',
    hash: 'HASH-MEMBER-LIST-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-MEMBER-LIST-SENTINEL',
    secret: 'SECRET-MEMBER-LIST-SENTINEL',
  },
  membership_term: {
    ...THANH_TOAN.membership_term,
    raw_payload: 'RAW-TERM-LIST-SENTINEL',
    signature: 'SIGNATURE-TERM-LIST-SENTINEL',
    hash: 'HASH-TERM-LIST-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-TERM-LIST-SENTINEL',
    secret: 'SECRET-TERM-LIST-SENTINEL',
  },
  events: [{
    event_id: 71,
    payment_id: 7,
    processing_status: 'CAN_DOI_SOAT',
    raw_payload: 'RAW-EVENT-LIST-SENTINEL',
    signature: 'SIGNATURE-EVENT-LIST-SENTINEL',
    hash: 'HASH-EVENT-LIST-SENTINEL',
    idempotency_key: 'IDEMPOTENCY-EVENT-LIST-SENTINEL',
    secret: 'SECRET-EVENT-LIST-SENTINEL',
  }],
}

function phanHoi(items = [THANH_TOAN], pagination = {}) {
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
      { path: '/admin/thanh-toan', name: 'adminThanhToan', component: { template: '<h1>Payment</h1>' } },
      { path: '/admin/thanh-toan/:id', name: 'adminChiTietThanhToan', component: { template: '<h1>Detail</h1>' } },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang() {
  const router = taoRouter()
  await router.push({ name: 'adminThanhToan' })
  await router.isReady()
  const pinia = createPinia()
  setActivePinia(pinia)
  const wrapper = mount(ThanhToanIndex, { global: { plugins: [pinia, router] } })
  await flushPromises()
  return { router, wrapper }
}

beforeEach(() => {
  vi.resetAllMocks()
  api.taiDanhSachThanhToan.mockResolvedValue(phanHoi())
  setActivePinia(createPinia())
})

describe('thanh_toan.index FE4-ALL', () => {
  it('render Payment status tach biet Membership status va read-only', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Thanh toán thành công')
    expect(wrapper.text()).toContain('Chờ kích hoạt')
    expect(wrapper.text()).toContain('Cảnh báo sau thanh toán thành công')
    expect(wrapper.text()).toContain('ORD-07')
    expect(wrapper.find('a[href*="thanh-toan"]').exists()).toBe(true)
    expect(wrapper.get('section[aria-label="Danh sách thanh toán"]').attributes('aria-busy')).toBe('false')
    expect(wrapper.get('input[name="order_code"]').attributes('maxlength')).toBe('40')
    expect(wrapper.get('table caption').text()).toContain('Danh sách')
    expect(wrapper.text()).not.toContain('Hoàn tiền')
    expect(wrapper.text()).not.toContain('Xác nhận thanh toán')
  })

  it('khong giu sensitive response fields trong Store hoac DOM va giu static render an toan', async () => {
    api.taiDanhSachThanhToan.mockResolvedValueOnce(phanHoi([THANH_TOAN_NHIEM]))
    const { wrapper } = await mountTrang()
    const noiDung = wrapper.text()

    expect(noiDung).not.toContain('RAW-LIST-SENTINEL')
    expect(noiDung).not.toContain('SIGNATURE-LIST-SENTINEL')
    expect(noiDung).not.toContain('HASH-LIST-SENTINEL')
    expect(noiDung).not.toContain('IDEMPOTENCY-LIST-SENTINEL')
    expect(noiDung).not.toContain('SECRET-LIST-SENTINEL')

    const indexSource = readFileSync(resolve(process.cwd(), 'src/pages/admin/thanh_toan/thanh_toan.index.vue'), 'utf8')
    const detailSource = readFileSync(resolve(process.cwd(), 'src/pages/admin/thanh_toan/thanh_toan.chi_tiet.vue'), 'utf8')
    const reconciliationSource = readFileSync(
      resolve(process.cwd(), 'src/pages/admin/doi_soat_thanh_toan/doi_soat_thanh_toan.index.vue'),
      'utf8',
    )
    ;[indexSource, detailSource, reconciliationSource].forEach((source) => {
      expect(source).not.toMatch(/v-html\s*=/)
      expect(source).not.toMatch(/Object\.(entries|values)\s*\(/)
    })
  })

  it('FE4 CSS chi dung visual tokens cho spacing va typography', () => {
    const css = readFileSync(resolve(process.cwd(), 'src/assets/main.css'), 'utf8')
    const viTriBatDau = css.indexOf('/* FE4 Admin Payment:')
    expect(viTriBatDau).toBeGreaterThanOrEqual(0)
    const khoiFe4 = css.slice(viTriBatDau)

    ;[
      '--khoang-1',
      '--khoang-2',
      '--khoang-3',
      '--khoang-4',
      '--khoang-5',
      '--co-chu-khu-vuc',
      '--co-chu-nho',
      '--co-chu-meta',
      '--dong-cao-chu',
    ].forEach((token) => expect(khoiFe4).toContain(`var(${token})`))

    khoiFe4.split('\n').forEach((dong) => {
      const khaiBao = dong.match(
        /^\s*(?:gap|padding(?:-[a-z-]+)?|margin(?:-[a-z-]+)?|font-size|line-height)\s*:\s*([^;]+);/,
      )
      if (!khaiBao) {
        return
      }

      const giaTri = khaiBao[1].trim()
      if (giaTri === '0') {
        return
      }

      expect(giaTri).toMatch(/^var\(--(?:khoang-[1-6]|co-chu-(?:khu-vuc|nho|meta)|dong-cao-chu)\)/)
    })
  })

  it('gui filter allow-list va server pagination qua Store', async () => {
    api.taiDanhSachThanhToan.mockResolvedValueOnce(phanHoi())
      .mockResolvedValueOnce(phanHoi([THANH_TOAN], { current_page: 1, total: 21, last_page: 2 }))
      .mockResolvedValueOnce(phanHoi([], { current_page: 2, total: 21, last_page: 2 }))
    const { wrapper } = await mountTrang()

    await wrapper.get('input[name="order_code"]').setValue('ORD-07')
    await wrapper.get('.bo-loc-danh-sach').trigger('submit')
    await flushPromises()

    expect(api.taiDanhSachThanhToan).toHaveBeenLastCalledWith(expect.objectContaining({
      order_code: 'ORD-07',
      page: 1,
    }))
    await wrapper.get('.thanh-phan-trang button:last-child').trigger('click')
    await flushPromises()
    expect(api.taiDanhSachThanhToan).toHaveBeenLastCalledWith(expect.objectContaining({
      order_code: 'ORD-07',
      page: 2,
    }))
  })

  it('co loading empty error retry va 403 navigation', async () => {
    api.taiDanhSachThanhToan.mockRejectedValueOnce({
      httpStatus: 503,
      code: 'SERVICE_UNAVAILABLE',
      message: 'Máy chủ đang gặp sự cố.',
    })
    const { router, wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Máy chủ đang gặp sự cố.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)

    api.taiDanhSachThanhToan.mockResolvedValueOnce(phanHoi([]))
    await wrapper.find('.trang-thai-loi button').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có lần thanh toán')

    api.taiDanhSachThanhToan.mockRejectedValueOnce({ httpStatus: 403, message: 'Forbidden' })
    await wrapper.get('.bo-loc-danh-sach').trigger('submit')
    await flushPromises()
    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })
})
