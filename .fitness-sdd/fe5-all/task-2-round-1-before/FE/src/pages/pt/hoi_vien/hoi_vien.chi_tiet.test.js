import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import ChiTietHoiVienPt from './hoi_vien.chi_tiet.vue'
import { taiChiTietHoiVien } from '../../../services/hoi_vien_pt.api.js'

vi.mock('../../../services/hoi_vien_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/hoi_vien_pt.api.js')
  return { ...actual, taiChiTietHoiVien: vi.fn() }
})

async function taoRouter() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { name: 'ptHoiVien', path: '/pt/hoi-vien', component: { template: '<div />' } },
      { name: 'ptChiTietHoiVien', path: '/pt/hoi-vien/:id', component: ChiTietHoiVienPt },
      { name: 'ptTienDoHoiVien', path: '/pt/hoi-vien/:id/tien-do', component: { template: '<div />' } },
      { name: 'ptKeHoachTapHoiVien', path: '/pt/hoi-vien/:id/ke-hoach-tap', component: { template: '<div />' } },
      { name: 'ptLichSuTapHoiVien', path: '/pt/hoi-vien/:id/lich-su-tap', component: { template: '<div />' } },
      { name: 'ptGhiChuHoiVien', path: '/pt/hoi-vien/:id/ghi-chu', component: { template: '<div />' } },
    ],
  })
  await router.push({ name: 'ptChiTietHoiVien', params: { id: '7' } })
  return router
}

describe('hoi_vien.chi_tiet FE5', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiChiTietHoiVien.mockResolvedValue({
      member: {
        id: 7, code: 'HV-07', name: 'Member 7', email: 'm@example.com',
        training_goal: 'Sức mạnh', status: 'HOAT_DONG',
      },
      assignment: {
        id: 3, start_at: '2026-09-01T00:00:00Z', end_at: null,
        trainer: { name: 'PT A' },
      },
    })
  })

  it('renders safe coaching profile, assignment metadata and aria-current subnav', async () => {
    const router = await taoRouter()
    const wrapper = mount(ChiTietHoiVienPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Member 7')
    expect(wrapper.text()).toContain('Đang trong phạm vi')
    expect(wrapper.find('a[aria-current="page"]').text()).toContain('Tổng quan')
    expect(wrapper.text()).not.toContain('token')
  })

  it('redirects a scope-loss response back to assigned member list', async () => {
    taiChiTietHoiVien.mockRejectedValue({ httpStatus: 404, code: 'MEMBER_NOT_FOUND', message: 'not found' })
    const router = await taoRouter()
    mount(ChiTietHoiVienPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(router.currentRoute.value.name).toBe('ptHoiVien')
  })
})
