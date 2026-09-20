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

const detail = (status = 'HOAT_DONG') => ({ id: 5, code: 'GA01', name: 'Sức mạnh', goal: 'Cơ bản', level: 'Beginner', sessions_per_week: 1, status, description: null, days: [{ order: 1, name: 'Ngày 1', estimated_minutes: 45, exercises: [{ exercise_id: 1, order: 1, target_sets: 3, min_reps: 8, max_reps: 12, rest_seconds: 60, notes: null }] }] })

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
  api.taiChiTietGiaoAnMau.mockResolvedValue(detail())
  api.capNhatGiaoAnMau.mockResolvedValue({ data: { id: 5 } })
})

describe('giao_an_mau.chi_tiet FE3', () => {
  it('mount nullable DTO, PATCH excludes days/status and opens revision', async () => {
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    expect(wrapper.find('#giao-an-detail-status').element.disabled).toBe(true)
    await wrapper.find('#giao-an-detail-name').setValue('Sức mạnh mới')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledWith(5, expect.not.objectContaining({ days: expect.anything(), status: expect.anything() }))
    const taoPhienBan = wrapper.findAll('button').find((button) => button.text().includes('Tạo phiên bản'))
    await taoPhienBan.trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminTaoPhienBanGiaoAnMau', params: { id: 5 } })
  })

  it('status detail entry point confirmation sends status-only PATCH', async () => {
    api.taiChiTietGiaoAnMau.mockResolvedValueOnce(detail()).mockResolvedValueOnce(detail('NGUNG_SU_DUNG'))
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    await wrapper.get('[data-testid="giao-an-status-action"]').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledWith(5, { status: 'NGUNG_SU_DUNG' })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('failed detail refresh keeps existing form visible and exposes retry state', async () => {
    api.taiChiTietGiaoAnMau.mockResolvedValueOnce(detail()).mockRejectedValueOnce({ httpStatus: 503, message: 'Không tải được dữ liệu mới.' })
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.text()).toContain('Không tải được dữ liệu mới.')
    expect(wrapper.find('button').exists()).toBe(true)
  })
})
