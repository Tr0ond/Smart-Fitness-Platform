import { mount } from '@vue/test-utils'
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

describe('goi_tap.index FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
  })

  it('mount dữ liệu, mở detail và giữ dialog khi mutation thất bại', async () => {
    api.taiDanhSachGoiTap.mockResolvedValueOnce({ data: [{ id: 1, code: 'BASIC', name: 'Cơ bản', price: 100, duration_days: 30, status: 'DANG_BAN' }] })
    api.capNhatGoiTap.mockRejectedValueOnce({ httpStatus: 422, message: 'Không thể cập nhật trạng thái.' })
    const wrapper = mount(GoiTap)
    await wrapper.vm.$nextTick()
    await new Promise((resolve) => setTimeout(resolve, 0))
    await wrapper.find('button.nut--lien-ket').trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGoiTap', params: { id: 1 } })
    await wrapper.findAll('button').find((button) => button.text() === 'Ngừng bán').trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    expect(api.capNhatGoiTap).toHaveBeenCalledTimes(1)
  })
})
