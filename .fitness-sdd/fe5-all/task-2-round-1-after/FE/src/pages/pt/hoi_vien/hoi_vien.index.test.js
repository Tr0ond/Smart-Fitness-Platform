import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import HoiVienPt from './hoi_vien.index.vue'
import { taiDanhSachHoiVienDuocPhanCong } from '../../../services/hoi_vien_pt.api.js'

vi.mock('../../../services/hoi_vien_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/hoi_vien_pt.api.js')
  return { ...actual, taiDanhSachHoiVienDuocPhanCong: vi.fn() }
})

async function taoRouter() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { name: 'ptHoiVien', path: '/pt/hoi-vien', component: HoiVienPt },
      { name: 'ptChiTietHoiVien', path: '/pt/hoi-vien/:id', component: { template: '<div />' } },
    ],
  })
  await router.push({ name: 'ptHoiVien' })
  return router
}

describe('hoi_vien.index FE5', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiDanhSachHoiVienDuocPhanCong.mockResolvedValue([
      { id: 1, member: { id: 7, name: 'Nguyễn PT', email: 'member@example.com' }, start_at: '2026-09-01T00:00:00Z' },
    ])
  })

  it('renders only assigned members with loading-to-data transition and safe link', async () => {
    const router = await taoRouter()
    const wrapper = mount(HoiVienPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Nguyễn PT')
    expect(wrapper.text()).toContain('Đang quản lý')
    expect(wrapper.get('a[href="/pt/hoi-vien/7"]').text()).toContain('Mở hồ sơ')
    expect(taiDanhSachHoiVienDuocPhanCong).toHaveBeenCalledTimes(1)
  })

  it('renders an explicit empty state for an empty authoritative assignment list', async () => {
    taiDanhSachHoiVienDuocPhanCong.mockResolvedValue([])
    const router = await taoRouter()
    const wrapper = mount(HoiVienPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có hội viên được phân công')
  })
})
