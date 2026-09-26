import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import ChiTietHoiVienPt from './hoi_vien.chi_tiet.vue'
import HoiVienPt from './hoi_vien.index.vue'
import {
  taiChiTietHoiVien,
  taiDanhSachHoiVienDuocPhanCong,
} from '../../../services/hoi_vien_pt.api.js'

vi.mock('../../../services/hoi_vien_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/hoi_vien_pt.api.js')
  return {
    ...actual,
    taiChiTietHoiVien: vi.fn(),
    taiDanhSachHoiVienDuocPhanCong: vi.fn(),
  }
})

function taoDeferred() {
  let resolve
  let reject
  const promise = new Promise((resolvePromise, rejectPromise) => {
    resolve = resolvePromise
    reject = rejectPromise
  })
  return { promise, resolve, reject }
}

async function taoRouter() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { name: 'ptHoiVien', path: '/pt/hoi-vien', component: HoiVienPt },
      { name: 'ptChiTietHoiVien', path: '/pt/hoi-vien/:id', component: ChiTietHoiVienPt },
      { name: 'ptTienDoHoiVien', path: '/pt/hoi-vien/:id/tien-do', component: { template: '<div />' } },
      { name: 'ptKeHoachTapHoiVien', path: '/pt/hoi-vien/:id/ke-hoach-tap', component: { template: '<div />' } },
      { name: 'ptLichSuTapHoiVien', path: '/pt/hoi-vien/:id/lich-su-tap', component: { template: '<div />' } },
      { name: 'ptGhiChuHoiVien', path: '/pt/hoi-vien/:id/ghi-chu', component: { template: '<div />' } },
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

  it('returns from a revoked member to a forced authoritative list without restoring its link', async () => {
    const refreshed = taoDeferred()
    const memberA = { id: 1, member: { id: 7, name: 'Member A' }, is_current: true }
    const memberB = { id: 2, member: { id: 8, name: 'Member B' }, is_current: true }
    taiDanhSachHoiVienDuocPhanCong
      .mockResolvedValueOnce([memberA])
      .mockReturnValueOnce(refreshed.promise)
    taiChiTietHoiVien.mockRejectedValueOnce({ httpStatus: 404, code: 'MEMBER_NOT_FOUND' })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Member A')

    await wrapper.get('a[href="/pt/hoi-vien/7"]').trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('ptHoiVien')
    expect(taiDanhSachHoiVienDuocPhanCong).toHaveBeenCalledTimes(2)
    expect(wrapper.text()).not.toContain('Member A')
    expect(wrapper.find('a[href="/pt/hoi-vien/7"]').exists()).toBe(false)

    refreshed.resolve([memberB])
    await flushPromises()
    expect(wrapper.text()).toContain('Member B')
    expect(wrapper.text()).not.toContain('Member A')
    expect(wrapper.find('a[href="/pt/hoi-vien/8"]').exists()).toBe(true)
  })

  it.each([403, 404])('clears stale assignment cards after list-level %s and recovers on retry', async (httpStatus) => {
    taiDanhSachHoiVienDuocPhanCong.mockResolvedValueOnce([
      { id: 1, member: { id: 7, name: 'Member A' }, is_current: true },
    ])
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Member A')

    taiDanhSachHoiVienDuocPhanCong.mockRejectedValueOnce({ httpStatus, message: 'assignment list unavailable' })
    taiChiTietHoiVien.mockResolvedValueOnce({
      data: {
        member: { id: 7, name: 'Member A' },
        assignment: { id: 1, start_at: '2026-09-01T00:00:00Z', end_at: null },
      },
    })
    await router.push({ name: 'ptChiTietHoiVien', params: { id: '7' } })
    await flushPromises()
    await router.push({ name: 'ptHoiVien' })
    await flushPromises()

    expect(wrapper.text()).toContain('assignment list unavailable')
    expect(wrapper.text()).not.toContain('Member A')
    expect(wrapper.find('a[href="/pt/hoi-vien/7"]').exists()).toBe(false)
    const retry = wrapper.find('button.nut--phu')
    expect(retry.exists()).toBe(true)

    taiDanhSachHoiVienDuocPhanCong.mockResolvedValueOnce([
      { id: 2, member: { id: 8, name: 'Recovered B' }, is_current: true },
    ])
    await retry.trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Recovered B')
    expect(wrapper.text()).not.toContain('Member A')
  })
})
