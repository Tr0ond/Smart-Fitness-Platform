import { createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import BuoiHuanLuyen from './buoi_huan_luyen.index.vue'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { hoanTatBuoiHuanLuyen, taiLichSuBuoiHuanLuyen } from '../../../services/buoi_huan_luyen.api.js'
import { taiChiTietHoiVien } from '../../../services/hoi_vien_pt.api.js'

vi.mock('../../../services/buoi_huan_luyen.api.js', () => ({
  hoanTatBuoiHuanLuyen: vi.fn(),
  taiLichSuBuoiHuanLuyen: vi.fn(),
}))

vi.mock('../../../services/hoi_vien_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/hoi_vien_pt.api.js')
  return { ...actual, taiChiTietHoiVien: vi.fn() }
})

vi.mock('../../../components/PT/thanh_dieu_huong_hoi_vien.vue', () => ({
  default: { template: '<nav aria-label="Điều hướng hồ sơ hội viên" />' },
}))

vi.mock('../../../utils/khoa_idempotency.js', () => ({
  taoKhoaIdempotency: vi.fn(() => '9c87e6ae-69e1-4a15-8330-9848fe48c7d2'),
}))

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
      { path: '/pt/hoi-vien', name: 'ptHoiVien', component: { template: '<div>Danh sách</div>' } },
      { path: '/pt/hoi-vien/:id/buoi-huan-luyen', name: 'ptBuoiHuanLuyen', component: BuoiHuanLuyen },
    ],
  })
  await router.push({ name: 'ptBuoiHuanLuyen', params: { id: '7' } })
  await router.isReady()
  return router
}

describe('buoi_huan_luyen.index FE6', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiChiTietHoiVien.mockResolvedValue({ data: { member: { id: 7 }, assignment: { id: 71, is_current: true } } })
    taiLichSuBuoiHuanLuyen.mockResolvedValue({ data: [] })
    hoanTatBuoiHuanLuyen.mockResolvedValue({ data: { history_id: 91, member_id: 7, status: 'HOAN_THANH', replayed: false } })
  })

  it('loads assignment before showing direct-session action and tells truth about latest-100 filtering', async () => {
    const router = await taoRouter()
    const wrapper = mount(BuoiHuanLuyen, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    expect(taiChiTietHoiVien).toHaveBeenCalledWith(7)
    expect(taiLichSuBuoiHuanLuyen).toHaveBeenCalledTimes(1)
    expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeUndefined()
    expect(wrapper.text()).toContain('100 buổi gần nhất')
    expect(wrapper.text()).toContain('Danh sách trống không chứng minh')
  })

  it('leaves the direct-session route when profile has no current assignment', async () => {
    taiChiTietHoiVien.mockResolvedValue({ data: { member: { id: 7 }, assignment: { id: 71, is_current: false } } })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('ptHoiVien')
    expect(wrapper.text()).toContain('Danh sách')
    expect(taiLichSuBuoiHuanLuyen).not.toHaveBeenCalled()
    expect(hoanTatBuoiHuanLuyen).not.toHaveBeenCalled()
  })

  it('uses current assignment, disables duplicate submit while pending and reconciles returned history once', async () => {
    const pending = taoDeferred()
    hoanTatBuoiHuanLuyen.mockReturnValue(pending.promise)
    const pinia = createPinia()
    const router = await taoRouter()
    const wrapper = mount(BuoiHuanLuyen, { global: { plugins: [pinia, router] } })
    await flushPromises()
    await wrapper.get('#pt-buoi-ghi-chu').setValue('Tốt')
    const submit = wrapper.get('button[type="submit"]')

    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(1)
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledWith(71, 'Tốt', '9c87e6ae-69e1-4a15-8330-9848fe48c7d2')
    expect(submit.attributes('aria-busy')).toBe('true')
    expect(submit.attributes('disabled')).toBeDefined()

    pending.resolve({ data: { history_id: 91, member_id: 7, status: 'HOAN_THANH', replayed: false } })
    await flushPromises()
    const store = useHoiVienPtStore(pinia)
    expect(store.lichSuBuoiHuanLuyen.filter((item) => item.history_id === 91)).toHaveLength(1)
    expect(wrapper.text()).toContain('Buổi tập đã được xác nhận hoàn tất')
  })

  it('keeps unknown request frozen, refetches and retries same assignment, notes and generated UUID', async () => {
    hoanTatBuoiHuanLuyen
      .mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
      .mockResolvedValueOnce({ data: { history_id: 92, member_id: 7, status: 'HOAN_THANH', replayed: true } })
    const wrapper = mount(BuoiHuanLuyen, { global: { plugins: [createPinia(), await taoRouter()] } })
    await flushPromises()
    await wrapper.get('#pt-buoi-ghi-chu').setValue('Giữ nguyên ghi chú')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('chưa thể kết luận')
    expect(wrapper.get('#pt-buoi-ghi-chu').element.value).toBe('Giữ nguyên ghi chú')

    await wrapper.get('button[type="button"]').trigger('click')
    await flushPromises()
    expect(taiLichSuBuoiHuanLuyen).toHaveBeenCalledTimes(3)
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(2)
    expect(hoanTatBuoiHuanLuyen.mock.calls[1]).toEqual(hoanTatBuoiHuanLuyen.mock.calls[0])
    expect(wrapper.text()).toContain('Backend xác nhận thao tác trước')
  })

  it('purges assignment scope on 403/404 and preserves draft on 409', async () => {
    taiLichSuBuoiHuanLuyen.mockRejectedValueOnce({ httpStatus: 403, message: 'forbidden' })
    const router = await taoRouter()
    const wrapper = mount(BuoiHuanLuyen, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(router.currentRoute.value.name).toBe('ptHoiVien')

    const router2 = await taoRouter()
    hoanTatBuoiHuanLuyen.mockRejectedValueOnce({ httpStatus: 409, message: 'conflict' })
    const wrapper2 = mount(BuoiHuanLuyen, { global: { plugins: [createPinia(), router2] } })
    await flushPromises()
    const note = wrapper2.get('#pt-buoi-ghi-chu')
    await note.setValue('Giữ lại sau conflict')
    await wrapper2.get('form').trigger('submit')
    await flushPromises()
    expect(note.element.value).toBe('Giữ lại sau conflict')
    expect(wrapper2.text()).toContain('conflict')
  })

  it('ignores a late POST response after switching Member', async () => {
    const pending = taoDeferred()
    hoanTatBuoiHuanLuyen.mockReturnValueOnce(pending.promise)
    const router = await taoRouter()
    const pinia = createPinia()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [pinia, router] } })
    await flushPromises()
    await wrapper.get('#pt-buoi-ghi-chu').setValue('Private A')
    await wrapper.get('form').trigger('submit')
    await vi.waitFor(() => expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(1))

    await router.push({ name: 'ptBuoiHuanLuyen', params: { id: '8' } })
    await flushPromises()
    pending.resolve({ data: { history_id: 99, member_id: 7, status: 'HOAN_THANH' } })
    await flushPromises()
    const store = useHoiVienPtStore(pinia)
    expect(store.hoiVienDaChonId).toBe(8)
    expect(store.lichSuBuoiHuanLuyen).toEqual([])
    expect(store.ketQuaBuoiHuanLuyen).toBeNull()
  })
})
