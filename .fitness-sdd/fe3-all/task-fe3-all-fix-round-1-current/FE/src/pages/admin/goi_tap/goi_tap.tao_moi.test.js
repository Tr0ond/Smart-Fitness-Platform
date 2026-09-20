import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const api = vi.hoisted(() => ({
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('vue-router', () => ({ useRouter: () => router }))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import GoiTapTaoMoi from './goi_tap.tao_moi.vue'

describe('goi_tap.tao_moi FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
  })

  it('mount form, gửi metadata và điều hướng sau khi tạo thành công', async () => {
    api.taoGoiTap.mockResolvedValueOnce({ data: { id: 9 } })
    const wrapper = mount(GoiTapTaoMoi)
    await wrapper.find('[name="code"]').setValue('BASIC')
    await wrapper.find('[name="name"]').setValue('Gói cơ bản')
    await wrapper.find('[name="price"]').setValue(100)
    await wrapper.find('[name="duration_days"]').setValue(30)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taoGoiTap).toHaveBeenCalledTimes(1)
    expect(api.taoGoiTap.mock.calls[0][0]).toMatchObject({ code: 'BASIC', name: 'Gói cơ bản', benefits: expect.any(Object) })
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGoiTap', params: { id: 9 } })
  })
})
