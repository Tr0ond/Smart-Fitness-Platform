import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const api = vi.hoisted(() => ({
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import DungCu from './dung_cu.index.vue'

beforeEach(() => {
  setActivePinia(createPinia())
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
})

describe('dung_cu.index FE3', () => {
  it('render data, edit metadata omits status and keeps status confirmation separate', async () => {
    api.taiDanhSachDungCu.mockResolvedValueOnce({ data: [{ id: 2, code: 'DB', name: 'Tạ tay', description: null, status: 'HOAT_DONG' }] })
    api.capNhatDungCu.mockResolvedValueOnce({ data: { id: 2 } })
    const wrapper = mount(DungCu)
    await flushPromises()
    await wrapper.find('table button.nut--phu').trigger('click')
    expect(wrapper.find('#dung-cu-status').element.disabled).toBe(true)
    await wrapper.find('#dung-cu-name').setValue('Tạ mới')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatDungCu).toHaveBeenCalledWith(2, { name: 'Tạ mới', description: null })
  })

  it('unknown -> GET previous -> explicit retry PATCH -> GET intended', async () => {
    api.taiDanhSachDungCu
      .mockResolvedValueOnce({ data: [{ id: 2, code: 'DB', name: 'Tạ tay', status: 'HOAT_DONG' }] })
      .mockResolvedValueOnce({ data: [{ id: 2, code: 'DB', name: 'Tạ tay', status: 'HOAT_DONG' }] })
      .mockResolvedValueOnce({ data: [{ id: 2, code: 'DB', name: 'Tạ tay', status: 'NGUNG_SU_DUNG' }] })
    api.capNhatDungCu.mockRejectedValueOnce({ isNetworkError: true, message: 'Mạng không ổn định.' }).mockResolvedValueOnce({ data: { id: 2 } })
    const wrapper = mount(DungCu)
    await flushPromises()
    await wrapper.find('table button.nut--nguy-hiem').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Đối soát trạng thái')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Thử lại cập nhật')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatDungCu).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('unknown reconcile returning intended closes with one PATCH and fresh GET', async () => {
    const item = { id: 2, code: 'DB', name: 'Dumbbell', status: 'HOAT_DONG' }
    api.taiDanhSachDungCu
      .mockResolvedValueOnce({ data: [item] })
      .mockResolvedValueOnce({ data: [{ ...item, status: 'NGUNG_SU_DUNG' }] })
    api.capNhatDungCu.mockRejectedValueOnce({ isNetworkError: true, message: 'status result unknown' })

    const wrapper = mount(DungCu)
    await flushPromises()
    await wrapper.find('table button.nut--nguy-hiem').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()

    expect(api.capNhatDungCu).toHaveBeenCalledTimes(1)
    expect(api.taiDanhSachDungCu).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('missing reconcile stays open and closing it clears the next target state', async () => {
    const itemOne = { id: 2, code: 'DB', name: 'Dumbbell', status: 'HOAT_DONG' }
    const itemTwo = { id: 3, code: 'KB', name: 'Kettlebell', status: 'HOAT_DONG' }
    api.taiDanhSachDungCu
      .mockResolvedValueOnce({ data: [itemOne, itemTwo] })
      .mockResolvedValueOnce({ data: [itemTwo] })
      .mockResolvedValueOnce({ data: [{ ...itemTwo, status: 'NGUNG_SU_DUNG' }] })
    api.capNhatDungCu
      .mockRejectedValueOnce({ isNetworkError: true, message: 'first result unknown' })
      .mockResolvedValueOnce({ data: { id: 3 } })

    const wrapper = mount(DungCu)
    await flushPromises()
    await wrapper.findAll('table button.nut--nguy-hiem')[0].trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatDungCu).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)

    await wrapper.find('[role="dialog"] button.nut--phu').trigger('click')
    await wrapper.find('table button.nut--nguy-hiem').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatDungCu).toHaveBeenNthCalledWith(2, 3, { status: 'NGUNG_SU_DUNG' })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('loading/error/empty states are recoverable', async () => {
    api.taiDanhSachDungCu.mockRejectedValueOnce({ httpStatus: 503, message: 'Dịch vụ tạm thời không khả dụng.' })
    const wrapper = mount(DungCu)
    await flushPromises()
    expect(wrapper.text()).toContain('Dịch vụ tạm thời không khả dụng.')
    api.taiDanhSachDungCu.mockResolvedValueOnce({ data: [] })
    await wrapper.findAll('button').find((button) => button.text().includes('Thử lại')).trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có dụng cụ')
  })
})
