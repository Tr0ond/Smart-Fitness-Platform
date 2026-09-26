import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import KeHoachTapPt from './ke_hoach_tap.index.vue'
import { taiKeHoachTapHoiVien } from '../../../services/ke_hoach_tap_pt.api.js'

vi.mock('../../../services/ke_hoach_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/ke_hoach_tap_pt.api.js')
  return { ...actual, taiKeHoachTapHoiVien: vi.fn() }
})

async function taoRouter() {
  const names = [
    ['ptHoiVien', '/pt/hoi-vien'], ['ptChiTietHoiVien', '/pt/hoi-vien/:id'],
    ['ptTienDoHoiVien', '/pt/hoi-vien/:id/tien-do'], ['ptKeHoachTapHoiVien', '/pt/hoi-vien/:id/ke-hoach-tap'],
    ['ptLichSuTapHoiVien', '/pt/hoi-vien/:id/lich-su-tap'], ['ptGhiChuHoiVien', '/pt/hoi-vien/:id/ghi-chu'],
  ]
  const router = createRouter({ history: createMemoryHistory(), routes: names.map(([name, path]) => ({
    name, path, component: name === 'ptKeHoachTapHoiVien' ? KeHoachTapPt : { template: '<div />' },
  })) })
  await router.push({ name: 'ptKeHoachTapHoiVien', params: { id: '7' } })
  return router
}

describe('ke_hoach_tap.index FE5', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiKeHoachTapHoiVien.mockResolvedValue({
      plan: {
        name: 'Kế hoạch chính thức', status: 'DANG_SU_DUNG',
        current_version: {
          number: 2, effective_from: '2026-09-01', goal: 'TANG_SUC_MANH',
          days: [{ id: 1, order: 1, name: 'Ngày 1', exercises: [{ id: 4, exercise_id: 12, name: 'Squat', target_sets: 3, min_reps: 8, max_reps: 10 }] }],
        },
      },
      future_schedule: [{ id: 8, name: 'Ngày 1', scheduled_date: '2026-09-23' }],
      schedule_window: { from: '2026-09-22', to: '2026-12-21', timezone: 'Asia/Ho_Chi_Minh' },
    })
  })

  it('labels the official current plan and future schedule as read-only', async () => {
    const router = await taoRouter()
    const wrapper = mount(KeHoachTapPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Kế hoạch chính thức')
    expect(wrapper.text()).toContain('Squat')
    expect(wrapper.text()).toContain('Lịch tập sắp tới')
    expect(wrapper.text()).toContain('Read-only snapshot')
    expect(wrapper.findAll('button')).toHaveLength(0)
  })

  it('describes an empty schedule using the server window without assuming its duration', async () => {
    taiKeHoachTapHoiVien.mockResolvedValueOnce({
      plan: {
        name: 'Official window plan',
        status: 'DANG_SU_DUNG',
        current_version: { number: 1, effective_from: '2026-10-03', goal: 'TANG_SUC_MANH', days: [] },
      },
      future_schedule: [],
      schedule_window: { from: '2026-10-03', to: '2027-01-16', timezone: 'Asia/Ho_Chi_Minh' },
    })
    const router = await taoRouter()
    const wrapper = mount(KeHoachTapPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    expect(wrapper.text()).toContain('Không có lịch tập trong khoảng ngày hiển thị.')
    expect(wrapper.text()).toContain('2026-10-03 — 2027-01-16')
    expect(wrapper.text()).not.toMatch(/\b\d+\s+ngày\b/)
  })
})
