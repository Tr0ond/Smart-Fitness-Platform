import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import GhiChuPt from './ghi_chu.index.vue'
import { taiDanhSachGhiChu, themGhiChuHuanLuyen } from '../../../services/ghi_chu.api.js'

vi.mock('../../../services/ghi_chu.api.js', async () => {
  const actual = await vi.importActual('../../../services/ghi_chu.api.js')
  return { ...actual, taiDanhSachGhiChu: vi.fn(), themGhiChuHuanLuyen: vi.fn() }
})

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
})
