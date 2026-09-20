import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const route = { params: { id: '7' } }
const api = vi.hoisted(() => ({
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('vue-router', () => ({ useRouter: () => router, useRoute: () => route }))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import GoiTapChiTiet from './goi_tap.chi_tiet.vue'

describe('goi_tap.chi_tiet FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
    api.taiChiTietGoiTap.mockResolvedValue({ data: { id: 7, code: 'BASIC', name: 'Cơ bản', price: 100, duration_days: 30, status: 'DANG_BAN', benefits: { gym_access: true } } })
    api.capNhatGoiTap.mockResolvedValue({ data: { id: 7 } })
    api.thayTheQuyenLoiGoiTap.mockResolvedValue({ data: { id: 7 } })
  })

  it('mount chi tiết, PATCH metadata không gửi days và lưu quyền lợi Q01', async () => {
    const wrapper = mount(GoiTapChiTiet)
    await flushPromises()
    await wrapper.find('#goi-tap-detail-name').setValue('Cơ bản mới')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenCalledWith(7, expect.not.objectContaining({ days: expect.anything(), benefits: expect.anything() }))
    await wrapper.find('button.nut--chinh[type="button"]').trigger('click')
    await flushPromises()
    expect(api.thayTheQuyenLoiGoiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('snapshot')
  })
})
