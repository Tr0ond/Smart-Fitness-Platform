import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import TienDoPt from './tien_do.index.vue'
import {
  taiChiSoCoThe,
  taiTienDoBaiTap,
  taiTienDoHoiVien,
} from '../../../services/tien_do.api.js'
import { taiKeHoachTapHoiVien } from '../../../services/ke_hoach_tap_pt.api.js'

vi.mock('../../../services/tien_do.api.js', async () => {
  const actual = await vi.importActual('../../../services/tien_do.api.js')
  return {
    ...actual,
    taiChiSoCoThe: vi.fn(),
    taiTienDoBaiTap: vi.fn(),
    taiTienDoHoiVien: vi.fn(),
  }
})
vi.mock('../../../services/ke_hoach_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/ke_hoach_tap_pt.api.js')
  return { ...actual, taiKeHoachTapHoiVien: vi.fn() }
})

async function taoRouter() {
  const routes = [
    { name: 'ptHoiVien', path: '/pt/hoi-vien', component: { template: '<div />' } },
    { name: 'ptChiTietHoiVien', path: '/pt/hoi-vien/:id', component: { template: '<div />' } },
    { name: 'ptTienDoHoiVien', path: '/pt/hoi-vien/:id/tien-do', component: TienDoPt },
    { name: 'ptKeHoachTapHoiVien', path: '/pt/hoi-vien/:id/ke-hoach-tap', component: { template: '<div />' } },
    { name: 'ptLichSuTapHoiVien', path: '/pt/hoi-vien/:id/lich-su-tap', component: { template: '<div />' } },
    { name: 'ptGhiChuHoiVien', path: '/pt/hoi-vien/:id/ghi-chu', component: { template: '<div />' } },
  ]
  const router = createRouter({ history: createMemoryHistory(), routes })
  await router.push({ name: 'ptTienDoHoiVien', params: { id: '7' } })
  return router
}

describe('tien_do.index FE5', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiTienDoHoiVien.mockResolvedValue({ completed_sessions_count: 4, training_frequency: { active_days_count: 2, completed_sessions_per_7_days: '3.50' } })
    taiChiSoCoThe.mockResolvedValue({ items: [{ id: 1, measured_at: '2026-09-20T10:00:00Z', weight_kg: '70.00', bmi: '22.1', bmi_status: 'available' }] })
    taiKeHoachTapHoiVien.mockResolvedValue({
      plan: { current_version: { days: [{ id: 1, exercises: [{ exercise_id: 12, name: 'Squat' }] }] } },
      future_schedule: [],
    })
    taiTienDoBaiTap.mockResolvedValue({ exercise: { id: 12, name: 'Squat' }, items: [] })
  })

  it('renders backend metrics and derives exercise selector only from official plan', async () => {
    const router = await taoRouter()
    const wrapper = mount(TienDoPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Buổi đã hoàn thành')
    expect(wrapper.text()).toContain('4')
    const options = wrapper.findAll('select option').map((option) => option.text())
    expect(options).toEqual(['Chọn bài tập', 'Squat'])
    await wrapper.get('select').setValue('12')
    await flushPromises()
    expect(taiTienDoBaiTap).toHaveBeenCalledWith(7, 12, expect.anything())
  })

  it('shows empty progress state when body history is empty', async () => {
    taiChiSoCoThe.mockResolvedValue({ items: [] })
    const router = await taoRouter()
    const wrapper = mount(TienDoPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có chỉ số cơ thể')
  })
})
