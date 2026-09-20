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
import BaiTap from './bai_tap.index.vue'

describe('bai_tap.index FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
  })

  it('mount data, áp dụng bộ lọc và mở form tạo', async () => {
    api.taiDanhSachBaiTap.mockResolvedValue({ data: [{ id: 3, code: 'SQUAT', name: 'Squat', difficulty: 'Beginner', equipment: [], muscle_groups: [], status: 'HOAT_DONG' }] })
    const wrapper = mount(BaiTap)
    await flushPromises()
    expect(wrapper.text()).toContain('Squat')
    await wrapper.find('#bai-tap-search').setValue('squat')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taiDanhSachBaiTap).toHaveBeenCalledWith({ search: 'squat', status: '' })
    await wrapper.find('button.nut--chinh[type="button"]').trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminTaoBaiTap' })
  })
})
