import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const route = { params: { id: '5' } }
const api = vi.hoisted(() => ({
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
}))
vi.mock('vue-router', () => ({ useRouter: () => router, useRoute: () => route }))
vi.mock('../../../services/giao_an_mau.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
import GiaoAnMauChiTiet from './giao_an_mau.chi_tiet.vue'

describe('giao_an_mau.chi_tiet FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
    api.taiChiTietGiaoAnMau.mockResolvedValue({ data: {
      id: 5, code: 'GA01', name: 'Sức mạnh', goal: 'Cơ bản', level: 'Beginner', sessions_per_week: 1, status: 'HOAT_DONG', description: '', days: [{ order: 1, name: 'Ngày 1', estimated_minutes: 45, exercises: [] }],
    } })
    api.capNhatGiaoAnMau.mockResolvedValue({ data: { id: 5 } })
  })

  it('mount detail, PATCH metadata không gửi days và mở luồng tạo phiên bản', async () => {
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    await wrapper.find('#giao-an-detail-name').setValue('Sức mạnh mới')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledWith(5, expect.not.objectContaining({ days: expect.anything() }))
    const taoPhienBan = wrapper.findAll('button').find((button) => button.text().includes('Tạo phiên bản'))
    await taoPhienBan.trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminTaoPhienBanGiaoAnMau', params: { id: 5 } })
  })
})
