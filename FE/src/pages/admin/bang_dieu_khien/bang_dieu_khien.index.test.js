import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import BangDieuKhien from './bang_dieu_khien.index.vue'

const { taiTongQuanAdmin } = vi.hoisted(() => ({
  taiTongQuanAdmin: vi.fn(),
}))

vi.mock('../../../services/bang_dieu_khien.api.js', () => ({
  taiTongQuanAdmin,
}))

const BAO_CAO_DASHBOARD = {
  data: {
    branch: { id: 1, timezone: 'Asia/Ho_Chi_Minh' },
    period: { from: '2026-08-03', to: '2026-09-01', inclusive_days: 30 },
    accounts: { active_members_count: 2, active_trainers_count: 1 },
    memberships: { active_terms_count: 3, awaiting_activation_terms_count: 4 },
    activity: {
      check_ins_today_count: 5,
      completed_workouts_today_count: 6,
      completed_workouts_in_period_count: 7,
    },
    payments: { successful_in_period_count: 8, reconciliation_required_count: 9 },
    pt: { active_assignments_count: 0 },
    generated_at: '2026-09-01T00:00:00.000Z',
    revenue: 999999,
    unknown_metric: 123,
  },
}

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin/bang-dieu-khien',
        name: 'adminBangDieuKhien',
        component: BangDieuKhien,
        meta: {
          yeuCauXacThuc: true,
          vaiTro: ['ADMIN'],
          boCuc: 'admin',
          tieuDe: 'Bảng điều khiển',
          duongDanPhanCap: [{ nhan: 'Bảng điều khiển' }],
        },
      },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang(ketQua = BAO_CAO_DASHBOARD) {
  taiTongQuanAdmin.mockResolvedValueOnce(ketQua)
  const router = taoRouter()
  await router.push({ name: 'adminBangDieuKhien' })
  await router.isReady()

  const wrapper = mount(BangDieuKhien, {
    global: { plugins: [router] },
  })
  await flushPromises()

  return { router, wrapper }
}

describe('bang_dieu_khien.index FE1-T02', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render tieu de, period/timezone va dung allow-list 10 metric', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('h1').text()).toBe('Bảng điều khiển')
    expect(wrapper.findAll('.the-chi-so')).toHaveLength(10)
    expect(wrapper.text()).toContain('2026-08-03 – 2026-09-01')
    expect(wrapper.text()).toContain('Asia/Ho_Chi_Minh')
    expect(wrapper.text()).not.toContain('999999')
    expect(wrapper.text()).not.toContain('Doanh thu')
    expect(wrapper.text()).not.toContain('unknown_metric')
    expect(wrapper.get('.the-chi-so:last-child .the-chi-so__gia-tri').text()).toBe('0')
  })

  it('initial request dung filter rong de Backend chon default period', async () => {
    await mountTrang()

    expect(taiTongQuanAdmin).toHaveBeenCalledWith({})
  })

  it('ap dung valid from/to gui exact date-only query', async () => {
    const { wrapper } = await mountTrang()
    const inputs = wrapper.findAll('input[type="date"]')

    await inputs[0].setValue('2026-08-01')
    await inputs[1].setValue('2026-08-31')
    taiTongQuanAdmin.mockResolvedValueOnce(BAO_CAO_DASHBOARD)
    await wrapper.get('.bo-loc-danh-sach').trigger('submit')
    await flushPromises()

    expect(taiTongQuanAdmin).toHaveBeenLastCalledWith({ from: '2026-08-01', to: '2026-08-31' })
  })

  it('reject cap ngay thieu va range qua 366 ngay truoc request', async () => {
    const { wrapper } = await mountTrang()
    const inputs = wrapper.findAll('input[type="date"]')

    await inputs[0].setValue('2026-08-01')
    await wrapper.get('.bo-loc-danh-sach').trigger('submit')
    expect(taiTongQuanAdmin).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Vui lòng chọn Đến ngày.')

    await inputs[1].setValue('2027-08-02')
    await wrapper.get('.bo-loc-danh-sach').trigger('submit')
    expect(taiTongQuanAdmin).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Khoảng thời gian không được dài hơn 366 ngày.')
  })

  it('dat lai filter xoa ca hai ngay va goi Backend default period', async () => {
    const { wrapper } = await mountTrang()
    const inputs = wrapper.findAll('input[type="date"]')

    await inputs[0].setValue('2026-08-01')
    await inputs[1].setValue('2026-08-31')
    taiTongQuanAdmin.mockResolvedValueOnce(BAO_CAO_DASHBOARD)
    await wrapper.get('.bo-loc-danh-sach .nut--phu').trigger('click')
    await flushPromises()

    expect(inputs[0].element.value).toBe('')
    expect(inputs[1].element.value).toBe('')
    expect(taiTongQuanAdmin).toHaveBeenLastCalledWith({})
  })

  it('map 422 field error va giu metric cu, 5xx co retry thu cong', async () => {
    const { wrapper } = await mountTrang()
    document.body.appendChild(wrapper.element)
    const inputs = wrapper.findAll('input[type="date"]')
    taiTongQuanAdmin.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { to: ['Đến ngày không hợp lệ theo Backend.'] },
    })

    await inputs[0].setValue('2026-08-01')
    await inputs[1].setValue('2026-08-31')
    await wrapper.get('.bo-loc-danh-sach').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Đến ngày không hợp lệ theo Backend.')
    expect(wrapper.findAll('.the-chi-so')).toHaveLength(10)
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
    expect(document.activeElement).toBe(inputs[1].element)

    taiTongQuanAdmin.mockRejectedValueOnce({
      httpStatus: 503,
      message: 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await wrapper.get('.bo-loc-danh-sach').trigger('submit')
    await flushPromises()
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)

    taiTongQuanAdmin.mockResolvedValueOnce(BAO_CAO_DASHBOARD)
    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(taiTongQuanAdmin).toHaveBeenLastCalledWith({ from: '2026-08-01', to: '2026-08-31' })
    expect(wrapper.text()).not.toContain('Máy chủ đang gặp sự cố')
    wrapper.unmount()
  })

  it('403 di chuyen error layout ma khong logout hay tu tao session cleanup', async () => {
    taiTongQuanAdmin.mockRejectedValueOnce({
      httpStatus: 403,
      message: 'Bạn không có quyền thực hiện thao tác này.',
      fieldErrors: {},
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push({ name: 'adminBangDieuKhien' })
    await router.isReady()
    mount(BangDieuKhien, { global: { plugins: [router] } })
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })

  it('response thieu metric required thi fail-safe khong invent zero', async () => {
    const duLieuKhongDayDu = {
      data: {
        branch: { id: 1, timezone: 'Asia/Ho_Chi_Minh' },
        period: { from: '2026-08-03', to: '2026-09-01', inclusive_days: 30 },
        accounts: { active_members_count: 2 },
      },
    }
    const { wrapper } = await mountTrang(duLieuKhongDayDu)

    expect(wrapper.findAll('.the-chi-so')).toHaveLength(0)
    expect(wrapper.text()).toContain('Dữ liệu bảng điều khiển không đầy đủ.')
  })
})
