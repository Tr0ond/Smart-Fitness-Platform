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

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
})

describe('bai_tap.index FE3', () => {
  it('render data, filter server/local and navigate detail/create', async () => {
    const result = { data: [{ id: 3, code: 'SQUAT', name: 'Squat', difficulty: 'Beginner', equipment: [{ id: 2 }], muscle_groups: [{ id: 8 }], status: 'HOAT_DONG' }] }
    api.taiDanhSachBaiTap.mockResolvedValueOnce(result).mockResolvedValueOnce(result)
    const wrapper = mount(BaiTap)
    await flushPromises()
    expect(wrapper.text()).toContain('Squat')
    await wrapper.find('#bai-tap-search').setValue('squat')
    await wrapper.find('#bai-tap-difficulty').setValue('Beginner')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taiDanhSachBaiTap).toHaveBeenCalledWith({ search: 'squat', status: '' })
    await wrapper.find('button.nut--lien-ket').trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietBaiTap', params: { id: 3 } })
    await wrapper.find('button.nut--chinh[type="button"]').trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminTaoBaiTap' })
  })

  it('lọc tại chỗ trả trạng thái không có kết quả và reset lại', async () => {
    const result = { data: [{ id: 3, code: 'SQUAT', name: 'Squat', difficulty: 'Beginner', equipment: [], muscle_groups: [], status: 'HOAT_DONG' }] }
    api.taiDanhSachBaiTap.mockResolvedValueOnce(result).mockResolvedValueOnce(result)
    const wrapper = mount(BaiTap)
    await flushPromises()
    await wrapper.find('#bai-tap-difficulty').setValue('Advanced')
    expect(wrapper.text()).toContain('Không có kết quả phù hợp')
    await wrapper.findAll('button').find((button) => button.text() === 'Đặt lại').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Squat')
  })

  it('loading/error/empty states support retry', async () => {
    api.taiDanhSachBaiTap.mockRejectedValueOnce({ httpStatus: 503, message: 'Không thể tải bài tập.' })
    const wrapper = mount(BaiTap)
    await flushPromises()
    expect(wrapper.text()).toContain('Không thể tải bài tập.')
    api.taiDanhSachBaiTap.mockResolvedValueOnce({ data: [] })
    await wrapper.findAll('button').find((button) => button.text().includes('Thử lại')).trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có bài tập')
  })
})
