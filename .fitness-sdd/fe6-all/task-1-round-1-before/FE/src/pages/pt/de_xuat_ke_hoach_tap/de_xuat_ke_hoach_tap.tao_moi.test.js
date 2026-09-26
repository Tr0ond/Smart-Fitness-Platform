import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import TaoDeXuat from './de_xuat_ke_hoach_tap.tao_moi.vue'
import { taiDanhSachDeXuat, taoDeXuatKeHoach } from '../../../services/de_xuat.api.js'
import { taiChiTietHoiVien } from '../../../services/hoi_vien_pt.api.js'
import { taiKeHoachTapHoiVien } from '../../../services/ke_hoach_tap_pt.api.js'
import { taoKhoaIdempotency } from '../../../utils/khoa_idempotency.js'

vi.mock('../../../services/de_xuat.api.js', async () => {
  const actual = await vi.importActual('../../../services/de_xuat.api.js')
  return { ...actual, taiDanhSachDeXuat: vi.fn(), taoDeXuatKeHoach: vi.fn() }
})

vi.mock('../../../services/hoi_vien_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/hoi_vien_pt.api.js')
  return { ...actual, taiChiTietHoiVien: vi.fn() }
})

vi.mock('../../../services/ke_hoach_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/ke_hoach_tap_pt.api.js')
  return { ...actual, taiKeHoachTapHoiVien: vi.fn() }
})

vi.mock('../../../utils/khoa_idempotency.js', () => ({
  taoKhoaIdempotency: vi.fn(),
}))

vi.mock('../../../components/PT/thanh_dieu_huong_hoi_vien.vue', () => ({
  default: { template: '<nav aria-label="Điều hướng hồ sơ hội viên" />' },
}))

async function taoRouter() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/pt/hoi-vien', name: 'ptHoiVien', component: { template: '<div>Danh sách</div>' } },
      { path: '/pt/hoi-vien/:id/de-xuat', name: 'ptDeXuatKeHoach', component: { template: '<div>Đề xuất</div>' } },
      { path: '/pt/hoi-vien/:id/de-xuat/tao-moi', name: 'ptTaoDeXuatKeHoach', component: TaoDeXuat },
    ],
  })
  await router.push({ name: 'ptTaoDeXuatKeHoach', params: { id: '7' } })
  await router.isReady()
  return router
}

describe('de_xuat_ke_hoach_tap.tao_moi FE6', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiDanhSachDeXuat.mockResolvedValue({ data: [] })
    taiChiTietHoiVien.mockResolvedValue({ data: { member: { id: 7 }, assignment: { id: 71, is_current: true } } })
    taiKeHoachTapHoiVien.mockResolvedValue({ data: { plan: null } })
    taoKhoaIdempotency.mockReturnValue('9c87e6ae-69e1-4a15-8330-9848fe48c7d2')
  })

  it('creates TAO_MOI only after confirming there is no active official Plan', async () => {
    taoDeXuatKeHoach.mockResolvedValue({
      data: { id: 42, status: 'CHO_XAC_NHAN', source: 'HUAN_LUYEN_VIEN', expires_at: '2026-10-21T00:00:00Z' },
    })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    expect(taiChiTietHoiVien).toHaveBeenCalledWith(7)
    expect(taiKeHoachTapHoiVien).toHaveBeenCalledWith(7)
    expect(wrapper.get('#pt-de-xuat-loai').element.value).toBe('TAO_MOI')
    expect(wrapper.text()).toContain('Chưa có kế hoạch chính thức')

    await wrapper.get('#pt-de-xuat-tieu-de').setValue('Kế hoạch tăng sức bền')
    await wrapper.get('#pt-de-xuat-giai-thich').setValue('Bản nháp mới để hội viên xem xét.')
    await wrapper.get('#pt-de-xuat-ngay-ap-dung').setValue('2026-10-20')
    await wrapper.get('#pt-de-xuat-ke-hoach-ten').setValue('Sức bền cơ bản')
    await wrapper.get('#pt-de-xuat-ke-hoach-muc-tieu').setValue('Tăng sức bền')
    await wrapper.get('#pt-de-xuat-bai-id-0-0').setValue('14')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const [memberId, body, key] = taoDeXuatKeHoach.mock.calls[0]
    expect(memberId).toBe(7)
    expect(Object.keys(body).sort()).toEqual(['change_type', 'effective_from', 'explanation', 'plan', 'title'])
    expect(body).toEqual({
      change_type: 'TAO_MOI',
      title: 'Kế hoạch tăng sức bền',
      explanation: 'Bản nháp mới để hội viên xem xét.',
      effective_from: '2026-10-20',
      plan: {
        name: 'Sức bền cơ bản',
        goal: 'Tăng sức bền',
        days: [{
          order: 1,
          weekday: 2,
          name: 'Ngày tập 1',
          estimated_minutes: 60,
          exercises: [{
            exercise_id: 14,
            order: 1,
            target_sets: 3,
            min_reps: 8,
            max_reps: 12,
            rest_seconds: 90,
            target_weight_kg: null,
            notes: null,
          }],
        }],
        template_id: null,
      },
    })
    expect(key).toBe('9c87e6ae-69e1-4a15-8330-9848fe48c7d2')
    expect(wrapper.text()).toContain('chờ hội viên xác nhận')
    expect(wrapper.text()).not.toContain('Áp dụng kế hoạch')
  })

  it('stops before plan/proposal requests when current assignment is absent', async () => {
    taiChiTietHoiVien.mockResolvedValue({ data: { member: { id: 7 }, assignment: { id: 71, is_current: false } } })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('ptHoiVien')
    expect(wrapper.text()).toContain('Danh sách')
    expect(taiKeHoachTapHoiVien).not.toHaveBeenCalled()
    expect(taiDanhSachDeXuat).not.toHaveBeenCalled()
    expect(taoDeXuatKeHoach).not.toHaveBeenCalled()
  })

  it('leaves the composer when unknown-outcome refetch loses assignment scope', async () => {
    taoDeXuatKeHoach.mockRejectedValue({ isNetworkError: true, message: 'timeout' })
    taiDanhSachDeXuat.mockRejectedValueOnce({ httpStatus: 403, message: 'assignment unavailable' })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    await wrapper.get('#pt-de-xuat-tieu-de').setValue('Bản nháp')
    await wrapper.get('#pt-de-xuat-giai-thich').setValue('Nội dung gửi hội viên.')
    await wrapper.get('#pt-de-xuat-ngay-ap-dung').setValue('2026-10-20')
    await wrapper.get('#pt-de-xuat-ke-hoach-ten').setValue('Sức bền')
    await wrapper.get('#pt-de-xuat-ke-hoach-muc-tieu').setValue('Tăng sức bền')
    await wrapper.get('#pt-de-xuat-bai-id-0-0').setValue('14')

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
    expect(taiDanhSachDeXuat).toHaveBeenCalledTimes(1)
    expect(router.currentRoute.value.name).toBe('ptHoiVien')
    expect(wrapper.text()).toContain('Danh sách')
  })

  it('keeps submit disabled until official Plan context can be read', async () => {
    taiKeHoachTapHoiVien.mockRejectedValue({ httpStatus: 503, message: 'Plan unavailable' })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined()
    expect(taoDeXuatKeHoach).not.toHaveBeenCalled()
  })
})
