import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
vi.mock('vue-router', () => ({ useRouter: () => router }))
const api = vi.hoisted(() => ({
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import GoiTap from './goi_tap.index.vue'

const rows = (status = 'DANG_BAN') => [{ id: 1, code: 'BASIC', name: 'Cơ bản', price: 100, duration_days: 30, status }]

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
})

describe('goi_tap.index FE3', () => {
  it('render data, mở detail và giữ dialog sau lỗi 422', async () => {
    api.taiDanhSachGoiTap.mockResolvedValueOnce({ data: rows() })
    api.capNhatGoiTap.mockRejectedValueOnce({ httpStatus: 422, message: 'Không thể cập nhật trạng thái.' })
    const wrapper = mount(GoiTap)
    await flushPromises()
    await wrapper.find('button.nut--lien-ket').trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGoiTap', params: { id: 1 } })
    await wrapper.findAll('button').find((button) => button.text() === 'Ngừng bán').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Không thể cập nhật trạng thái.')
  })

  it('unknown -> GET previous -> retry PATCH -> GET intended là một flow hai bước đầy đủ', async () => {
    api.taiDanhSachGoiTap
      .mockResolvedValueOnce({ data: rows('DANG_BAN') })
      .mockResolvedValueOnce({ data: rows('DANG_BAN') })
      .mockResolvedValueOnce({ data: rows('NGUNG_BAN') })
    api.capNhatGoiTap
      .mockRejectedValueOnce({ isNetworkError: true, message: 'Mạng không ổn định.' })
      .mockResolvedValueOnce({ data: { id: 1 } })
    const wrapper = mount(GoiTap)
    await flushPromises()
    await wrapper.findAll('button').find((button) => button.text() === 'Ngừng bán').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Đối soát trạng thái')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Thử lại cập nhật')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(api.taiDanhSachGoiTap).toHaveBeenCalledTimes(3)
  })

  it('unknown reconcile returning intended closes with exactly one PATCH', async () => {
    api.taiDanhSachGoiTap
      .mockResolvedValueOnce({ data: rows('DANG_BAN') })
      .mockResolvedValueOnce({ data: rows('NGUNG_BAN') })
    api.capNhatGoiTap.mockRejectedValueOnce({ isNetworkError: true, message: 'status result unknown' })

    const wrapper = mount(GoiTap)
    await flushPromises()
    await wrapper.find('table button.nut--phu').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()

    expect(api.capNhatGoiTap).toHaveBeenCalledTimes(1)
    expect(api.taiDanhSachGoiTap).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('missing reconcile stays unresolved and a new target starts a clean transition', async () => {
    const itemOne = rows('DANG_BAN')[0]
    const itemTwo = { ...itemOne, id: 2, code: 'PLUS', name: 'Plus' }
    api.taiDanhSachGoiTap
      .mockResolvedValueOnce({ data: [itemOne, itemTwo] })
      .mockResolvedValueOnce({ data: [itemTwo] })
      .mockResolvedValueOnce({ data: [{ ...itemTwo, status: 'NGUNG_BAN' }] })
    api.capNhatGoiTap
      .mockRejectedValueOnce({ isNetworkError: true, message: 'first result unknown' })
      .mockResolvedValueOnce({ data: { id: 2 } })

    const wrapper = mount(GoiTap)
    await flushPromises()
    await wrapper.findAll('table button.nut--phu')[0].trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()

    expect(api.capNhatGoiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    await wrapper.find('[role="dialog"] button.nut--phu').trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)

    await wrapper.find('table button.nut--phu').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenNthCalledWith(2, 2, { status: 'NGUNG_BAN' })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('hiển thị loading/error/empty và retry danh sách', async () => {
    api.taiDanhSachGoiTap.mockRejectedValueOnce({ httpStatus: 503, message: 'Dịch vụ tạm thời không khả dụng.' })
    const wrapper = mount(GoiTap)
    await flushPromises()
    expect(wrapper.text()).toContain('Dịch vụ tạm thời không khả dụng.')
    api.taiDanhSachGoiTap.mockResolvedValueOnce({ data: [] })
    await wrapper.findAll('button').find((button) => button.text().includes('Thử lại')).trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có gói tập')
  })
})
