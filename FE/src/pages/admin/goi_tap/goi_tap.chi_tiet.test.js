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

const detail = (configuration_version, overrides = {}) => ({
  id: 7,
  code: 'BASIC',
  name: 'Cơ bản',
  price: 100,
  duration_days: 30,
  status: 'DANG_BAN',
  description: null,
  configuration_version,
  benefits: { gym_access: true, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 0 },
  ...overrides,
})

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
  api.taiChiTietGoiTap.mockResolvedValue(detail(1))
  api.capNhatGoiTap.mockResolvedValue({ data: { id: 7 } })
  api.thayTheQuyenLoiGoiTap.mockResolvedValue({ data: { id: 7 } })
})

describe('goi_tap.chi_tiet FE3', () => {
  it('render nullable detail and refresh authoritative configuration_version 1 -> 2 -> 3', async () => {
    api.taiChiTietGoiTap
      .mockResolvedValueOnce(detail(1))
      .mockResolvedValueOnce(detail(2, { name: 'Cơ bản mới', description: null }))
      .mockResolvedValueOnce(detail(3, { name: 'Cơ bản mới', benefits: { gym_access: true, fitness_assistant: true, fitness_assistant_limit: null, trainer_chat: false, direct_trainer_sessions: 0 } }))
    const wrapper = mount(GoiTapChiTiet)
    await flushPromises()
    expect(wrapper.get('[data-testid="configuration-version"]').text()).toContain('1')
    expect(wrapper.get('#goi-tap-detail-status').element.disabled).toBe(true)
    await wrapper.find('#goi-tap-detail-name').setValue('Cơ bản mới')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenCalledWith(7, expect.not.objectContaining({ status: expect.anything(), days: expect.anything(), benefits: expect.anything() }))
    expect(wrapper.get('[data-testid="configuration-version"]').text()).toContain('2')
    await wrapper.findAll('button').find((button) => button.text().includes('Lưu quyền lợi')).trigger('click')
    await flushPromises()
    expect(api.thayTheQuyenLoiGoiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.get('[data-testid="configuration-version"]').text()).toContain('3')
  })

  it('giữ description null trong metadata PATCH và chặn price ngoài Backend bounds', async () => {
    const wrapper = mount(GoiTapChiTiet)
    await flushPromises()
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGoiTap.mock.calls[0][1]).toEqual(expect.objectContaining({ description: null }))
    api.capNhatGoiTap.mockClear()
    await wrapper.find('#goi-tap-detail-price').setValue('0')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGoiTap).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Giá phải là số nguyên')
  })

  it('failed refresh keeps last confirmed version and exposes retryable error', async () => {
    api.taiChiTietGoiTap.mockResolvedValueOnce(detail(1)).mockRejectedValueOnce({ httpStatus: 503, message: 'Không thể tải phiên bản mới.' })
    const wrapper = mount(GoiTapChiTiet)
    await flushPromises()
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[data-testid="configuration-version"]').text()).toContain('1')
    expect(wrapper.text()).toContain('Không thể tải phiên bản mới.')
    expect(wrapper.find('[data-testid="configuration-version"]').exists()).toBe(true)
  })

  it('status detail chỉ hiển thị read-only và metadata không gửi status', async () => {
    const wrapper = mount(GoiTapChiTiet)
    await flushPromises()
    expect(wrapper.get('#goi-tap-detail-status').element.disabled).toBe(true)
    expect(wrapper.find('[data-testid="goi-tap-status-action"]').exists()).toBe(false)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    await wrapper.find('#goi-tap-detail-name').setValue('Cơ bản chỉnh sửa')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGoiTap).toHaveBeenCalledWith(7, expect.not.objectContaining({ status: expect.anything() }))
  })
})
