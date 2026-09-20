import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const api = vi.hoisted(() => ({
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import NhomCo from './nhom_co.index.vue'

beforeEach(() => {
  setActivePinia(createPinia())
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
})

describe('nhom_co.index FE3', () => {
  it('render inactive row, edit metadata omits status and keeps M061 lifecycle', async () => {
    api.taiDanhSachNhomCo.mockResolvedValueOnce({ data: [{ id: 4, code: 'BACK', name: 'Lưng', description: null, status: 'NGUNG_SU_DUNG' }] })
    api.capNhatNhomCo.mockResolvedValueOnce({ data: { id: 4 } })
    const wrapper = mount(NhomCo)
    await flushPromises()
    await wrapper.find('table button.nut--phu').trigger('click')
    expect(wrapper.find('#nhom-co-status').element.disabled).toBe(true)
    await wrapper.find('#nhom-co-name').setValue('Lưng mới')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatNhomCo).toHaveBeenCalledWith(4, { name: 'Lưng mới', description: null })
  })

  it('unknown -> GET previous -> explicit retry PATCH -> GET intended', async () => {
    api.taiDanhSachNhomCo
      .mockResolvedValueOnce({ data: [{ id: 4, code: 'BACK', name: 'Lưng', status: 'NGUNG_SU_DUNG' }] })
      .mockResolvedValueOnce({ data: [{ id: 4, code: 'BACK', name: 'Lưng', status: 'NGUNG_SU_DUNG' }] })
      .mockResolvedValueOnce({ data: [{ id: 4, code: 'BACK', name: 'Lưng', status: 'HOAT_DONG' }] })
    api.capNhatNhomCo.mockRejectedValueOnce({ isNetworkError: true, message: 'Mạng không ổn định.' }).mockResolvedValueOnce({ data: { id: 4 } })
    const wrapper = mount(NhomCo)
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
    expect(api.capNhatNhomCo).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('unknown reconcile returning intended closes with one PATCH and cache bypass', async () => {
    const item = { id: 4, code: 'BACK', name: 'Back', status: 'NGUNG_SU_DUNG' }
    api.taiDanhSachNhomCo
      .mockResolvedValueOnce({ data: [item] })
      .mockResolvedValueOnce({ data: [{ ...item, status: 'HOAT_DONG' }] })
    api.capNhatNhomCo.mockRejectedValueOnce({ isNetworkError: true, message: 'status result unknown' })

    const wrapper = mount(NhomCo)
    await flushPromises()
    await wrapper.find('table button.nut--nguy-hiem').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()

    expect(api.capNhatNhomCo).toHaveBeenCalledTimes(1)
    expect(api.taiDanhSachNhomCo).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('other authoritative status remains unresolved and a new target can mutate independently', async () => {
    const itemOne = { id: 4, code: 'BACK', name: 'Back', status: 'NGUNG_SU_DUNG' }
    const itemTwo = { id: 5, code: 'CHEST', name: 'Chest', status: 'NGUNG_SU_DUNG' }
    api.taiDanhSachNhomCo
      .mockResolvedValueOnce({ data: [itemOne, itemTwo] })
      .mockResolvedValueOnce({ data: [itemTwo] })
      .mockResolvedValueOnce({ data: [{ ...itemTwo, status: 'HOAT_DONG' }] })
    api.capNhatNhomCo.mockRejectedValueOnce({ isNetworkError: true, message: 'first result unknown' }).mockResolvedValueOnce({ data: { id: 5 } })

    const wrapper = mount(NhomCo)
    await flushPromises()
    await wrapper.findAll('table button.nut--nguy-hiem')[0].trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Chưa xác định')

    await wrapper.find('[role="dialog"] button.nut--phu').trigger('click')
    await wrapper.find('table button.nut--nguy-hiem').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatNhomCo).toHaveBeenNthCalledWith(2, 5, { status: 'HOAT_DONG' })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('loading/error/empty states support retry', async () => {
    api.taiDanhSachNhomCo.mockRejectedValueOnce({ httpStatus: 503, message: 'Không thể tải nhóm cơ.' })
    const wrapper = mount(NhomCo)
    await flushPromises()
    expect(wrapper.text()).toContain('Không thể tải nhóm cơ.')
    api.taiDanhSachNhomCo.mockResolvedValueOnce({ data: [] })
    await wrapper.findAll('button').find((button) => button.text().includes('Thử lại')).trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có nhóm cơ')
  })
})
