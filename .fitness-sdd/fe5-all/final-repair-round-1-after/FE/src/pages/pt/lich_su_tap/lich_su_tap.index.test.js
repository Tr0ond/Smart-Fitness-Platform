import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import LichSuTapPt from './lich_su_tap.index.vue'
import {
  taiChiTietLichSuTapHoiVien,
  taiLichSuTapHoiVien,
} from '../../../services/lich_su_tap_pt.api.js'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'

vi.mock('../../../services/lich_su_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/lich_su_tap_pt.api.js')
  return {
    ...actual,
    taiChiTietLichSuTapHoiVien: vi.fn(),
    taiLichSuTapHoiVien: vi.fn(),
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

  it('hides A snapshot while B loads and fails, then retries B without showing A', async () => {
    const pendingB = taoDeferred()
    let requestsForB = 0
    taiLichSuTapHoiVien.mockResolvedValueOnce([
      { id: 4, name: 'Session A', status: 'HOAN_THANH', ended_at: '2026-09-20T11:00:00Z' },
      { id: 5, name: 'Session B', status: 'HOAN_THANH', ended_at: '2026-09-21T11:00:00Z' },
    ])
    taiChiTietLichSuTapHoiVien.mockImplementation((_memberId, sessionId) => {
      if (sessionId === 4) {
        return Promise.resolve({
          id: 4,
          name: 'Session A',
          status: 'HOAN_THANH',
          revision: 1,
          exercises: [{ id: 40, name: 'Exercise A' }],
        })
      }
      requestsForB += 1
      return requestsForB === 1
        ? pendingB.promise
        : Promise.resolve({
          id: 5,
          name: 'Session B',
          status: 'HOAN_THANH',
          revision: 2,
          exercises: [{ id: 50, name: 'Exercise B' }],
        })
    })
    const router = await taoRouter()
    const wrapper = mount(LichSuTapPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    const sessionButtons = wrapper.findAll('.pt-danh-sach-phien__nut')
    await sessionButtons[0].trigger('click')
    await flushPromises()
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).toContain('Session A')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).toContain('Exercise A')

    await sessionButtons[1].trigger('click')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).not.toContain('Session A')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).not.toContain('Exercise A')

    pendingB.reject({ httpStatus: 503, message: 'Session B detail unavailable' })
    await flushPromises()
    expect(wrapper.text()).toContain('Session B detail unavailable')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).not.toContain('Session A')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).not.toContain('Exercise A')

    const retry = wrapper.find('section[aria-labelledby="pt-lich-su-chi-tiet"] button.nut--phu')
    expect(retry.exists()).toBe(true)
    await retry.trigger('click')
    await flushPromises()
    expect(taiChiTietLichSuTapHoiVien).toHaveBeenLastCalledWith(7, 5)
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).toContain('Session B')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).toContain('Exercise B')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).not.toContain('Session A')
    expect(wrapper.get('section[aria-labelledby="pt-lich-su-chi-tiet"]').text()).not.toContain('Exercise A')
  })

  it.each([403, 404])('purges and replaces the history route after load-more scope loss %s', async (httpStatus) => {
    taiLichSuTapHoiVien
      .mockResolvedValueOnce({
        items: [{ id: 4, name: 'Revoked session', status: 'HOAN_THANH', ended_at: '2026-09-20T11:00:00Z' }],
        next_cursor: { before_id: 3 },
      })
      .mockRejectedValueOnce({ httpStatus, message: 'Assignment is no longer current' })
    const router = await taoRouter()
    const routerReplace = vi.spyOn(router, 'replace')
    const pinia = createPinia()
    const store = useHoiVienPtStore(pinia)
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [pinia, router] } })
    await flushPromises()
    expect(wrapper.find('nav.pt-thanh-dieu-huong-hoi-vien').exists()).toBe(true)
    expect(store.hoiVienDaChonId).toBe(7)

    await wrapper.get('.pt-danh-sach-phien__nut').trigger('click')
    await flushPromises()
    expect(store.chiTietPhien?.name).toBe('Buổi sức mạnh')
    expect(wrapper.text()).toContain('Revoked session')

    await wrapper.get('button.nut--phu').trigger('click')
    await flushPromises()

    expect(taiLichSuTapHoiVien).toHaveBeenNthCalledWith(2, 7, { limit: 20, before_id: 3 })
    expect(routerReplace).toHaveBeenCalledWith({ name: 'ptHoiVien' })
    expect(router.currentRoute.value.name).toBe('ptHoiVien')
    expect(store.hoiVienDaChonId).toBeNull()
    expect(store.lichSuTap.items).toEqual([])
    expect(store.lichSuTheoHoiVien['7']).toBeUndefined()
    expect(store.chiTietPhien).toBeNull()
    expect(wrapper.find('nav.pt-thanh-dieu-huong-hoi-vien').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Revoked session')
    expect(wrapper.text()).not.toContain('Buổi sức mạnh')
  })

  it('appends the next cursor page without redirecting', async () => {
    taiLichSuTapHoiVien
      .mockResolvedValueOnce({
        items: [{ id: 4, name: 'Newer session', status: 'HOAN_THANH' }],
        next_cursor: { before_id: 3 },
      })
      .mockResolvedValueOnce({
        items: [{ id: 2, name: 'Older session', status: 'HOAN_THANH' }],
        next_cursor: null,
      })
    const router = await taoRouter()
    const pinia = createPinia()
    const store = useHoiVienPtStore(pinia)
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [pinia, router] } })
    await flushPromises()

    await wrapper.get('button.nut--phu').trigger('click')
    await flushPromises()

    expect(taiLichSuTapHoiVien).toHaveBeenNthCalledWith(2, 7, { limit: 20, before_id: 3 })
    expect(store.lichSuTap.items.map((session) => session.id)).toEqual([4, 2])
    expect(wrapper.text()).toContain('Older session')
    expect(router.currentRoute.value.name).toBe('ptLichSuTapHoiVien')
  })

  it('keeps a manual retry after transient load-more failure without redirecting', async () => {
    taiLichSuTapHoiVien
      .mockResolvedValueOnce({
        items: [{ id: 4, name: 'Current page session', status: 'HOAN_THANH' }],
        next_cursor: { before_id: 3 },
      })
      .mockRejectedValueOnce({ httpStatus: 503, message: 'History temporarily unavailable' })
      .mockResolvedValueOnce({
        items: [{ id: 4, name: 'Current page session', status: 'HOAN_THANH' }],
        next_cursor: null,
      })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    await wrapper.get('button.nut--phu').trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.name).toBe('ptLichSuTapHoiVien')
    expect(wrapper.text()).toContain('History temporarily unavailable')
    expect(wrapper.text()).toContain('Current page session')
    const retry = wrapper.find('.trang-thai-loi button.nut--phu')
    expect(retry.exists()).toBe(true)

    await retry.trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('ptLichSuTapHoiVien')
    expect(wrapper.text()).not.toContain('History temporarily unavailable')
    expect(wrapper.text()).toContain('Current page session')
  })

  it('does not redirect B when a superseded A load-more later loses scope', async () => {
    const lateA = taoDeferred()
    taiLichSuTapHoiVien
      .mockResolvedValueOnce({
        items: [{ id: 4, name: 'Member A session', status: 'HOAN_THANH' }],
        next_cursor: { before_id: 3 },
      })
      .mockReturnValueOnce(lateA.promise)
      .mockResolvedValueOnce({
        items: [{ id: 8, name: 'Member B session', status: 'HOAN_THANH' }],
        next_cursor: null,
      })
    const router = await taoRouter()
    const routerReplace = vi.spyOn(router, 'replace')
    const pinia = createPinia()
    const store = useHoiVienPtStore(pinia)
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [pinia, router] } })
    await flushPromises()

    await wrapper.get('button.nut--phu').trigger('click')
    await router.push({ name: 'ptLichSuTapHoiVien', params: { id: '8' } })
    await flushPromises()
    expect(store.hoiVienDaChonId).toBe(8)
    expect(wrapper.text()).toContain('Member B session')

    lateA.reject({ httpStatus: 404, message: 'Old assignment is no longer current' })
    await flushPromises()

    expect(routerReplace).not.toHaveBeenCalled()
    expect(router.currentRoute.value.name).toBe('ptLichSuTapHoiVien')
    expect(router.currentRoute.value.params.id).toBe('8')
    expect(store.hoiVienDaChonId).toBe(8)
    expect(wrapper.text()).toContain('Member B session')
    expect(wrapper.text()).not.toContain('Member A session')
  })
})
