import { mount } from '@vue/test-utils'
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

describe('nhom_co.index FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
  })

  it('mount inactive row and giữ dialog sau lỗi mutation M061', async () => {
    api.taiDanhSachNhomCo.mockResolvedValueOnce({ data: [{ id: 4, code: 'BACK', name: 'Lưng', status: 'NGUNG_SU_DUNG' }] })
    api.capNhatNhomCo.mockRejectedValueOnce({ isNetworkError: true, message: 'Mạng không ổn định.' })
    const wrapper = mount(NhomCo)
    await new Promise((resolve) => setTimeout(resolve, 0))
    await wrapper.vm.$nextTick()
    await wrapper.find('table button.nut--nguy-hiem').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await wrapper.vm.$nextTick()
    expect(api.capNhatNhomCo).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Không thể xác định kết quả')
  })
})
