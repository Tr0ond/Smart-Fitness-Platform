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

function timNutTheoNhan(wrapper, nhan) {
  return wrapper.findAll('button').find((nut) => nut.text().includes(nhan))
}

async function dienBanNhapToiThieu(wrapper) {
  await wrapper.get('#pt-de-xuat-tieu-de').setValue('Kế hoạch mới')
  await wrapper.get('#pt-de-xuat-giai-thich').setValue('Nội dung cho hội viên.')
  await wrapper.get('#pt-de-xuat-ngay-ap-dung').setValue('2026-10-20')
  await wrapper.get('#pt-de-xuat-ke-hoach-ten').setValue('Sức bền cơ bản')
  await wrapper.get('#pt-de-xuat-ke-hoach-muc-tieu').setValue('Tăng sức bền')
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

  it('submits an untouched official decimal weight as a number and keeps invalid source strings from being submitted', async () => {
    taiKeHoachTapHoiVien.mockResolvedValue({ data: { plan: {
      id: 60,
      name: 'Plan chÃ­nh thá»©c',
      current_version: { goal: 'Sá»©c bá»n', days: [{
        order: 1, weekday: 2, name: 'NgÃ y trÃªn', estimated_minutes: 60,
        exercises: [
          { exercise_id: 14, order: 1, target_sets: 4, min_reps: 8, max_reps: 10,
            target_weight_kg: '20.00', rest_seconds: 90, notes: null },
          { exercise_id: 15, order: 2, target_sets: 3, min_reps: 10, max_reps: 12,
            target_weight_kg: null, rest_seconds: 45, notes: null },
        ],
      }] },
    } } })
    taoDeXuatKeHoach.mockResolvedValue({ data: { id: 45, status: 'CHO_XAC_NHAN' } })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.get('#pt-de-xuat-bai-tai-0-0').element.value).toBe('20')
    await dienBanNhapToiThieu(wrapper)
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const [, body] = taoDeXuatKeHoach.mock.calls[0]
    expect(body.change_type).toBe('DIEU_CHINH')
    expect(body.plan.days[0].exercises[0].target_weight_kg).toBe(20)
    expect(body.plan.days[0].exercises[1].target_weight_kg).toBeNull()

    const invalidValues = [' ', 'bad', '-1', '10000.00', '1.234']
    for (const value of invalidValues) {
      taiKeHoachTapHoiVien.mockResolvedValueOnce({ data: { plan: {
        id: 61, name: 'Plan', current_version: { goal: 'Goal', days: [{
          order: 1, weekday: 2, name: 'Day', estimated_minutes: 60,
          exercises: [{ exercise_id: 14, order: 1, target_sets: 3, min_reps: 8, max_reps: 12,
            target_weight_kg: value, rest_seconds: 90, notes: null }],
        }] },
      } } })
      const routerInvalid = await taoRouter()
      const invalidWrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), routerInvalid] } })
      await flushPromises()
      await dienBanNhapToiThieu(invalidWrapper)
      await invalidWrapper.get('form').trigger('submit')
      await flushPromises()
      expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
      expect(invalidWrapper.get('#pt-de-xuat-bai-tai-0-0').attributes('aria-invalid')).toBe('true')
      invalidWrapper.unmount()
    }
  })

  it('keeps day and exercise order unique after removing middle rows and adding replacements', async () => {
    taoDeXuatKeHoach.mockResolvedValue({ data: { id: 43, status: 'CHO_XAC_NHAN' } })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    await timNutTheoNhan(wrapper, 'Thêm ngày tập').trigger('click')
    await timNutTheoNhan(wrapper, 'Thêm ngày tập').trigger('click')
    await wrapper.get('#pt-de-xuat-ngay-ten-0').setValue('Ngày A')
    await wrapper.get('#pt-de-xuat-ngay-ten-1').setValue('Ngày B')
    await wrapper.get('#pt-de-xuat-ngay-ten-2').setValue('Ngày C')
    await wrapper.get('#pt-de-xuat-ngay-thu-0').setValue('2')
    await wrapper.get('#pt-de-xuat-ngay-thu-1').setValue('5')
    await wrapper.get('#pt-de-xuat-ngay-thu-2').setValue('8')

    const firstDay = wrapper.findAll('.pt-de-xuat-ngay')[0]
    await timNutTheoNhan(firstDay, 'Thêm bài tập').trigger('click')
    await timNutTheoNhan(firstDay, 'Thêm bài tập').trigger('click')
    for (let index = 0; index < 3; index += 1) {
      await wrapper.get(`#pt-de-xuat-bai-id-0-${index}`).setValue(String(14 + index))
    }

    const daysBeforeRemoval = wrapper.findAll('.pt-de-xuat-ngay')
    await daysBeforeRemoval[1].get('.pt-card__dau button').trigger('click')
    let daysAfterRemoval = wrapper.findAll('.pt-de-xuat-ngay')
    expect(daysAfterRemoval.map((day) => day.get('h3').text())).toEqual(['Ngày tập 1', 'Ngày tập 2'])
    expect(wrapper.get('#pt-de-xuat-ngay-ten-0').element.value).toBe('Ngày A')
    expect(wrapper.get('#pt-de-xuat-ngay-ten-1').element.value).toBe('Ngày C')
    expect(wrapper.get('#pt-de-xuat-ngay-thu-0').element.value).toBe('2')
    expect(wrapper.get('#pt-de-xuat-ngay-thu-1').element.value).toBe('8')
    await timNutTheoNhan(wrapper, 'Thêm ngày tập').trigger('click')

    const exercisesBeforeRemoval = wrapper.findAll('.pt-de-xuat-ngay')[0].findAll('fieldset.pt-de-xuat-bai')
    await exercisesBeforeRemoval[1].get('button').trigger('click')
    const firstDayAfterRemoval = wrapper.findAll('.pt-de-xuat-ngay')[0]
    expect(firstDayAfterRemoval.findAll('fieldset.pt-de-xuat-bai').map((row) => row.get('legend').text())).toEqual(['Bài tập 1', 'Bài tập 2'])
    expect(wrapper.get('#pt-de-xuat-bai-id-0-0').element.value).toBe('14')
    expect(wrapper.get('#pt-de-xuat-bai-id-0-1').element.value).toBe('16')
    await timNutTheoNhan(firstDayAfterRemoval, 'Thêm bài tập').trigger('click')

    await dienBanNhapToiThieu(wrapper)
    for (const [dayIndex, day] of wrapper.findAll('.pt-de-xuat-ngay').entries()) {
      for (const [exerciseIndex] of day.findAll('input[id^="pt-de-xuat-bai-id-"]').entries()) {
        await wrapper.get(`#pt-de-xuat-bai-id-${dayIndex}-${exerciseIndex}`).setValue(String(20 + dayIndex * 10 + exerciseIndex))
      }
    }
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const [, body] = taoDeXuatKeHoach.mock.calls[0]
    const days = body.plan.days
    expect(days).toHaveLength(3)
    expect(new Set(days.map((day) => day.order)).size).toBe(3)
    expect(new Set(days.map((day) => day.weekday)).size).toBe(3)
    expect(days.map((day) => day.weekday)).toEqual([2, 8, 3])
    expect(new Set(days[0].exercises.map((exercise) => exercise.order)).size).toBe(3)
    expect(days[0].exercises.map((exercise) => exercise.order)).toEqual([1, 2, 3])
  })

  it('links nested 422 errors to controls and clears indexed errors after structure changes', async () => {
    taoDeXuatKeHoach.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu chưa hợp lệ.',
      fieldErrors: {
        'plan.days.0.weekday': ['Thứ này đã được dùng.'],
        'plan.days.0.exercises.1.max_reps': ['Số lần tối đa phải bằng hoặc lớn hơn tối thiểu.'],
      },
    })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    await timNutTheoNhan(wrapper, 'Thêm bài tập').trigger('click')
    await dienBanNhapToiThieu(wrapper)
    await wrapper.get('#pt-de-xuat-bai-id-0-0').setValue('14')
    await wrapper.get('#pt-de-xuat-bai-id-0-1').setValue('15')

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const maxRep = wrapper.get('#pt-de-xuat-bai-rep-max-0-1')
    expect(maxRep.attributes('aria-invalid')).toBe('true')
    const maxRepErrorId = maxRep.attributes('aria-describedby')
    expect(maxRepErrorId).toBe('pt-de-xuat-loi-plan-days-0-exercises-1-max_reps')
    expect(wrapper.get(`#${maxRepErrorId}`).text()).toContain('bằng hoặc lớn hơn tối thiểu')
    const weekday = wrapper.get('#pt-de-xuat-ngay-thu-0')
    expect(weekday.attributes('aria-invalid')).toBe('true')
    expect(wrapper.get(`#${weekday.attributes('aria-describedby')}`).text()).toContain('Thứ này đã được dùng')
    expect(wrapper.find('[aria-live="assertive"]').text()).toContain('plan.days.0.exercises.1.max_reps')
    expect(wrapper.get('#pt-de-xuat-tieu-de').element.value).toBe('Kế hoạch mới')

    await wrapper.findAll('.pt-de-xuat-ngay')[0].findAll('fieldset.pt-de-xuat-bai')[0].get('button').trigger('click')
    const reindexedMaxRep = wrapper.get('#pt-de-xuat-bai-rep-max-0-0')
    expect(reindexedMaxRep.attributes('aria-invalid')).toBe('false')
    expect(wrapper.find('#pt-de-xuat-loi-plan-days-0-exercises-1-max_reps').exists()).toBe(false)
  })
})
