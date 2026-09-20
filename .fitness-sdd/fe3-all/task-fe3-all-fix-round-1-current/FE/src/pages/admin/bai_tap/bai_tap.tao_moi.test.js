import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const api = vi.hoisted(() => ({
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))

vi.mock('vue-router', () => ({ useRouter: () => router }))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import BaiTapTaoMoi from './bai_tap.tao_moi.vue'

describe('bai_tap.tao_moi FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
    api.taiDanhSachDungCu.mockResolvedValue({ data: [{ id: 3, code: 'DB', name: 'Tạ tay', status: 'HOAT_DONG' }] })
    api.taiDanhSachNhomCo.mockResolvedValue({ data: [{ id: 8, code: 'NGUC', name: 'Ngực', status: 'HOAT_DONG' }] })
    api.taoBaiTap.mockResolvedValue({ data: { id: 12 } })
  })

  it('mount form, gửi đủ quan hệ Q11 và điều hướng sau khi tạo', async () => {
    const wrapper = mount(BaiTapTaoMoi)
    await flushPromises()
    await wrapper.find('#bai-tap-create-code').setValue('SQUAT')
    await wrapper.find('#bai-tap-create-name').setValue('Squat')
    await wrapper.find('#bai-tap-create-difficulty').setValue('Beginner')
    await wrapper.find('#bai-tap-equipment').setValue(['3'])
    await wrapper.find('input[type="checkbox"]').setValue(true)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.taoBaiTap).toHaveBeenCalledTimes(1)
    expect(api.taoBaiTap.mock.calls[0][0]).toMatchObject({
      code: 'SQUAT',
      equipment_ids: [3],
      muscle_groups: [{ id: 8, role: 'CHINH' }],
    })
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietBaiTap', params: { id: 12 } })
  })
})
