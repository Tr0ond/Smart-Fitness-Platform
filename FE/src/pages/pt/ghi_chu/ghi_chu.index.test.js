import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import GhiChuPt from './ghi_chu.index.vue'
import { taiDanhSachGhiChu, themGhiChuHuanLuyen } from '../../../services/ghi_chu.api.js'

vi.mock('../../../services/ghi_chu.api.js', async () => {
  const actual = await vi.importActual('../../../services/ghi_chu.api.js')
  return { ...actual, taiDanhSachGhiChu: vi.fn(), themGhiChuHuanLuyen: vi.fn() }
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
    ['ptHoiVien', '/pt/hoi-vien'], ['ptChiTietHoiVien', '/pt/hoi-vien/:id'],
    ['ptTienDoHoiVien', '/pt/hoi-vien/:id/tien-do'], ['ptKeHoachTapHoiVien', '/pt/hoi-vien/:id/ke-hoach-tap'],
    ['ptLichSuTapHoiVien', '/pt/hoi-vien/:id/lich-su-tap'], ['ptGhiChuHoiVien', '/pt/hoi-vien/:id/ghi-chu'],
  ]
  const router = createRouter({ history: createMemoryHistory(), routes: routes.map(([name, path]) => ({
    name, path, component: name === 'ptGhiChuHoiVien' ? GhiChuPt : { template: '<div />' },
  })) })
  await router.push({ name: 'ptGhiChuHoiVien', params: { id: '7' } })
  return router
}

describe('ghi_chu.index FE5', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiDanhSachGhiChu.mockResolvedValue([
      { id: 2, content: 'Ghi chú mới nhất', created_at: '2026-09-22T10:00:00Z' },
    ])
    themGhiChuHuanLuyen.mockResolvedValue({ id: 3, content: 'Ghi chú thêm' })
  })

  it('renders newest-first append-only notes and clears draft only after success', async () => {
    const router = await taoRouter()
    const wrapper = mount(GhiChuPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Ghi chú mới nhất')
    const textarea = wrapper.get('#pt-ghi-chu-noi-dung')
    await textarea.setValue('  Note mới  ')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(themGhiChuHuanLuyen).toHaveBeenCalledWith(7, { content: '  Note mới  ' })
    expect(textarea.element.value).toBe('')
    expect(wrapper.text()).toContain('Append-only')
  })

  it('retains draft and communicates unknown outcome for timeout without blind retry', async () => {
    themGhiChuHuanLuyen.mockRejectedValue({ httpStatus: 503, message: 'temporary' })
    const router = await taoRouter()
    const wrapper = mount(GhiChuPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    const textarea = wrapper.get('#pt-ghi-chu-noi-dung')
    await textarea.setValue('Draft giữ lại')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(textarea.element.value).toBe('Draft giữ lại')
    expect(wrapper.text()).toContain('Chưa xác định được kết quả lưu')
    expect(themGhiChuHuanLuyen).toHaveBeenCalledTimes(1)
  })

  it('clears A draft on reused A-to-B route and submits only B draft to B', async () => {
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    const textarea = wrapper.get('#pt-ghi-chu-noi-dung')
    await textarea.setValue('A private draft')

    await router.push({ name: 'ptGhiChuHoiVien', params: { id: '8' } })
    await nextTick()
    expect(textarea.element.value).toBe('')

    await textarea.setValue('B own draft')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(themGhiChuHuanLuyen).toHaveBeenCalledTimes(1)
    expect(themGhiChuHuanLuyen).toHaveBeenCalledWith(8, { content: 'B own draft' })
    expect(textarea.element.value).toBe('')
  })

  it('keeps B draft when A note GET resolves after route reuse', async () => {
    const lateReadA = taoDeferred()
    taiDanhSachGhiChu.mockImplementation((id) => id === 7
      ? lateReadA.promise
      : Promise.resolve([{ id: 80, content: 'B note', created_at: '2026-09-22T10:00:00Z' }]))
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    const textarea = wrapper.get('#pt-ghi-chu-noi-dung')
    await textarea.setValue('A draft during read')

    await router.push({ name: 'ptGhiChuHoiVien', params: { id: '8' } })
    await nextTick()
    expect(textarea.element.value).toBe('')
    await textarea.setValue('B draft survives late A read')
    await flushPromises()

    lateReadA.resolve([{ id: 70, content: 'A late notes' }])
    await flushPromises()
    expect(textarea.element.value).toBe('B draft survives late A read')
    expect(wrapper.text()).toContain('B note')
    expect(wrapper.text()).not.toContain('A late notes')
    expect(themGhiChuHuanLuyen).not.toHaveBeenCalled()
  })

  it('keeps B draft when A POST resolves after route reuse', async () => {
    const pendingPostA = taoDeferred()
    themGhiChuHuanLuyen.mockReturnValueOnce(pendingPostA.promise)
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    const textarea = wrapper.get('#pt-ghi-chu-noi-dung')
    await textarea.setValue('A pending post')
    await wrapper.get('form').trigger('submit')
    await vi.waitFor(() => expect(themGhiChuHuanLuyen).toHaveBeenCalledTimes(1))

    await router.push({ name: 'ptGhiChuHoiVien', params: { id: '8' } })
    await nextTick()
    expect(textarea.element.value).toBe('')
    await textarea.setValue('B draft survives late A post')
    await flushPromises()

    pendingPostA.resolve({ id: 70, content: 'A pending post' })
    await flushPromises()
    expect(themGhiChuHuanLuyen).toHaveBeenCalledWith(7, { content: 'A pending post' })
    expect(themGhiChuHuanLuyen).toHaveBeenCalledTimes(1)
    expect(textarea.element.value).toBe('B draft survives late A post')
  })

  it('retains same-member draft after a 422 POST rejection', async () => {
    themGhiChuHuanLuyen.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'content invalid',
      fieldErrors: { content: ['invalid'] },
    })
    const router = await taoRouter()
    const wrapper = mount(GhiChuPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    const textarea = wrapper.get('#pt-ghi-chu-noi-dung')
    await textarea.setValue('Same member draft')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(themGhiChuHuanLuyen).toHaveBeenCalledTimes(1)
    expect(textarea.element.value).toBe('Same member draft')
    expect(wrapper.text()).toContain('content invalid')
  })
})
