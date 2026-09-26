import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import LichSuTapPt from './lich_su_tap.index.vue'
import {
  taiChiTietLichSuTapHoiVien,
  taiLichSuTapHoiVien,
} from '../../../services/lich_su_tap_pt.api.js'

vi.mock('../../../services/lich_su_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/lich_su_tap_pt.api.js')
  return {
    ...actual,
    taiChiTietLichSuTapHoiVien: vi.fn(),
    taiLichSuTapHoiVien: vi.fn(),
  }
})

async function taoRouter() {
  const routes = [
    { name: 'ptHoiVien', path: '/pt/hoi-vien', component: { template: '<div />' } },
    { name: 'ptChiTietHoiVien', path: '/pt/hoi-vien/:id', component: { template: '<div />' } },
    { name: 'ptTienDoHoiVien', path: '/pt/hoi-vien/:id/tien-do', component: { template: '<div />' } },
    { name: 'ptKeHoachTapHoiVien', path: '/pt/hoi-vien/:id/ke-hoach-tap', component: { template: '<div />' } },
    { name: 'ptLichSuTapHoiVien', path: '/pt/hoi-vien/:id/lich-su-tap', component: LichSuTapPt },
    { name: 'ptGhiChuHoiVien', path: '/pt/hoi-vien/:id/ghi-chu', component: { template: '<div />' } },
  ]
  const router = createRouter({ history: createMemoryHistory(), routes })
  await router.push({ name: 'ptLichSuTapHoiVien', params: { id: '7' } })
  return router
}

describe('lich_su_tap.index FE5', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiLichSuTapHoiVien.mockResolvedValue([{ id: 4, name: 'Buổi sức mạnh', status: 'HOAN_THANH', ended_at: '2026-09-20T11:00:00Z' }])
    taiChiTietLichSuTapHoiVien.mockResolvedValue({ id: 4, name: 'Buổi sức mạnh', status: 'HOAN_THANH', revision: 1, exercises: [] })
  })

  it('renders immutable session list and read-only detail without edit/delete controls', async () => {
    const router = await taoRouter()
    const wrapper = mount(LichSuTapPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Buổi sức mạnh')
    expect(wrapper.text()).toContain('bất biến')
    await wrapper.get('.pt-danh-sach-phien__nut').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Không thể chỉnh sửa')
    expect(wrapper.findAll('button').map((button) => button.text()).join(' ')).not.toMatch(/Sửa|Xóa|Xoá/)
    expect(taiChiTietLichSuTapHoiVien).toHaveBeenCalledWith(7, 4)
  })

  it('shows explicit empty state when no immutable sessions exist', async () => {
    taiLichSuTapHoiVien.mockResolvedValue([])
    const router = await taoRouter()
    const wrapper = mount(LichSuTapPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có phiên tập')
  })
})
