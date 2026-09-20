import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const api = vi.hoisted(() => ({
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
}))
const router = { push: vi.fn() }
vi.mock('vue-router', () => ({ useRouter: () => router }))
vi.mock('../../../services/giao_an_mau.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
import GiaoAnMau from './giao_an_mau.index.vue'

describe('giao_an_mau.index FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
    api.taiDanhSachGiaoAnMau.mockResolvedValue({ data: [{ id: 6, code: 'GA01', name: 'Sức mạnh', goal: 'Cơ bản', level: 'Beginner', sessions_per_week: 2, day_count: 2, status: 'HOAT_DONG' }] })
    api.capNhatGiaoAnMau.mockRejectedValue({ isNetworkError: true, message: 'Mạng không ổn định.' })
  })

  it('mount dữ liệu, mở dialog trạng thái và giữ flow khi outcome chưa rõ', async () => {
    const wrapper = mount(GiaoAnMau)
    await flushPromises()
    expect(wrapper.text()).toContain('Sức mạnh')
    await wrapper.find('table button.nut--phu').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Không thể xác định kết quả')
  })
})
