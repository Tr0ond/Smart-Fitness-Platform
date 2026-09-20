import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const route = { params: { id: '21' } }
const api = vi.hoisted(() => ({
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))

vi.mock('vue-router', () => ({ useRouter: () => router, useRoute: () => route }))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import BaiTapChiTiet from './bai_tap.chi_tiet.vue'

describe('bai_tap.chi_tiet FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
    api.taiChiTietBaiTap.mockResolvedValue({ data: {
      id: 21,
      code: 'SQUAT',
      name: 'Squat',
      difficulty: 'Beginner',
      status: 'HOAT_DONG',
      equipment: [{ id: 3, code: 'DB', name: 'Tạ tay' }],
      muscle_groups: [{ id: 8, code: 'NGUC', name: 'Ngực', status: 'NGUNG_SU_DUNG', role: 'PHU' }],
    } })
    api.taiDanhSachDungCu.mockResolvedValue({ data: [{ id: 3, code: 'DB', name: 'Tạ tay', status: 'HOAT_DONG' }] })
    api.taiDanhSachNhomCo.mockResolvedValue({ data: [{ id: 8, code: 'NGUC', name: 'Ngực', status: 'NGUNG_SU_DUNG' }, { id: 9, code: 'LUNG', name: 'Lưng', status: 'HOAT_DONG' }] })
    api.capNhatBaiTap.mockResolvedValue({ data: { id: 21 } })
  })

  it('mount khóa M061, giữ exact id/role và PATCH không phá quan hệ', async () => {
    const wrapper = mount(BaiTapChiTiet)
    await flushPromises()
    const inactiveCheckbox = wrapper.findAll('input[type="checkbox"]')[0]
    const inactiveRole = wrapper.findAll('.danh-muc-quan-he__nhom-co select')[0]
    expect(inactiveCheckbox.element.disabled).toBe(true)
    expect(inactiveRole.element.disabled).toBe(true)

    inactiveCheckbox.element.checked = false
    await inactiveCheckbox.trigger('change')
    inactiveRole.element.value = 'CHINH'
    await inactiveRole.trigger('change')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.capNhatBaiTap).toHaveBeenCalledWith(21, expect.objectContaining({
      muscle_groups: [{ id: 8, role: 'PHU' }],
      equipment_ids: [3],
    }))
  })
})
