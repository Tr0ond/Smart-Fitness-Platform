import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'

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

const detail = (status = 'HOAT_DONG', overrides = {}) => ({ id: 5, code: 'GA01', name: 'Sức mạnh', goal: 'Cơ bản', level: 'Beginner', sessions_per_week: 1, status, description: null, days: [{ order: 1, name: 'Ngày 1', estimated_minutes: 45, exercises: [{ exercise_id: 1, order: 1, target_sets: 3, min_reps: 8, max_reps: 12, rest_seconds: 60, notes: null }] }], ...overrides })

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

  it('status detail chỉ hiển thị read-only và metadata không gửi status', async () => {
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    expect(wrapper.get('#giao-an-detail-status').element.disabled).toBe(true)
    expect(wrapper.find('[data-testid="giao-an-status-action"]').exists()).toBe(false)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('PATCH tại chi tiết chỉ sửa metadata')
    expect(wrapper.text()).toContain('chuyển trạng thái thực hiện từ danh sách giáo án mẫu')
    expect(wrapper.text()).toContain('nội dung days cần tạo phiên bản mới')
    await wrapper.find('#giao-an-detail-name').setValue('Sức mạnh chỉnh sửa')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledWith(5, expect.not.objectContaining({ status: expect.anything() }))
  })

  it('hiển thị trạng thái loading trước khi GET detail hoàn tất', async () => {
    let resolveInitial
    api.taiChiTietGiaoAnMau.mockReset().mockReturnValueOnce(new Promise((resolve) => { resolveInitial = resolve }))
    const wrapper = mount(GiaoAnMauChiTiet)
    await nextTick()

    expect(wrapper.text()).toContain('Đang tải chi tiết giáo án mẫu…')
    expect(wrapper.find('form').exists()).toBe(false)
    resolveInitial({ data: detail() })
    await flushPromises()
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('GET detail lỗi lần đầu có thể retry bằng GET và không PATCH', async () => {
    api.taiChiTietGiaoAnMau.mockReset()
      .mockRejectedValueOnce({ httpStatus: 503, message: 'Không thể tải giáo án mẫu.' })
      .mockResolvedValueOnce({ data: detail() })
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()

    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.text()).toContain('Không thể tải giáo án mẫu.')
    await wrapper.find('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(api.taiChiTietGiaoAnMau).toHaveBeenCalledTimes(2)
    expect(api.capNhatGiaoAnMau).not.toHaveBeenCalled()
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('metadata 422 hiển thị lỗi field/global, giữ form và không refresh thành công', async () => {
    api.capNhatGiaoAnMau.mockRejectedValueOnce({
      httpStatus: 422,
      code: 'VALIDATION_ERROR',
      message: 'Dữ liệu giáo án chưa hợp lệ.',
      fieldErrors: { name: ['Tên giáo án đã tồn tại.'] },
    })
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    await wrapper.find('#giao-an-detail-name').setValue('Bản nháp chưa lưu')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.capNhatGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(api.taiChiTietGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('#giao-an-detail-name').element.value).toBe('Bản nháp chưa lưu')
    expect(wrapper.text()).toContain('Tên giáo án đã tồn tại.')
    expect(wrapper.text()).toContain('Dữ liệu giáo án chưa hợp lệ.')
    expect(wrapper.find('button[type="submit"]').element.disabled).toBe(false)
  })

  it('khóa double-submit khi PATCH metadata đang chờ', async () => {
    let resolveMutation
    api.capNhatGiaoAnMau.mockReset().mockReturnValueOnce(new Promise((resolve) => { resolveMutation = resolve }))
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    await wrapper.find('#giao-an-detail-name').setValue('Đang chỉnh sửa')
    const form = wrapper.find('form')
    await form.trigger('submit')
    await form.trigger('submit')

    expect(api.capNhatGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('button[type="submit"]').element.disabled).toBe(true)
    resolveMutation({ data: { id: 5 } })
    await flushPromises()
    expect(api.taiChiTietGiaoAnMau).toHaveBeenCalledTimes(2)
  })

  it('PATCH metadata thành công rồi hydrate lại metadata authoritative từ GET', async () => {
    api.taiChiTietGiaoAnMau.mockReset()
      .mockResolvedValueOnce({ data: detail() })
      .mockResolvedValueOnce({ data: detail('HOAT_DONG', { name: 'Tên authoritative', description: 'Mô tả mới' }) })
    api.capNhatGiaoAnMau.mockReset().mockResolvedValueOnce({ data: { id: 5 } })
    const wrapper = mount(GiaoAnMauChiTiet)
    await flushPromises()
    await wrapper.find('#giao-an-detail-name').setValue('Tên đang nhập')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.capNhatGiaoAnMau).toHaveBeenCalledWith(5, {
      name: 'Tên đang nhập',
      goal: 'Cơ bản',
      level: 'Beginner',
      sessions_per_week: 1,
      description: null,
    })
    expect(api.capNhatGiaoAnMau.mock.calls[0][1]).not.toHaveProperty('days')
    expect(api.capNhatGiaoAnMau.mock.calls[0][1]).not.toHaveProperty('status')
    expect(api.taiChiTietGiaoAnMau).toHaveBeenCalledTimes(2)
    expect(wrapper.find('#giao-an-detail-name').element.value).toBe('Tên authoritative')
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
