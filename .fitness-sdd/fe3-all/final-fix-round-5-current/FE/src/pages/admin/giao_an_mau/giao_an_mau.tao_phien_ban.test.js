import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const route = { params: { id: '7' } }
const api = vi.hoisted(() => ({
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  xuLyGiaoAnMauDaCu: vi.fn(),
}))
vi.mock('vue-router', () => ({ useRouter: () => router, useRoute: () => route }))
vi.mock('../../../services/giao_an_mau.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
import GiaoAnMauTaoPhienBan from './giao_an_mau.tao_phien_ban.vue'

describe('giao_an_mau.tao_phien_ban FE3', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    router.push.mockReset()
    Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
    api.xuLyGiaoAnMauDaCu.mockImplementation((error) => ({
      laXungDot: error?.httpStatus === 409 && error?.code === 'WORKOUT_TEMPLATE_STALE',
    }))
    api.taiChiTietGiaoAnMau
      .mockResolvedValueOnce({ data: { id: 7, code: 'GA01', name: 'Nền cũ', goal: 'Mục tiêu', level: 'Beginner', sessions_per_week: 1, content_version: 1, status: 'HOAT_DONG', days: [{ order: 1, name: 'Ngày 1', estimated_minutes: 45, exercises: [] }] } })
      .mockResolvedValueOnce({ data: { id: 7, code: 'GA01', name: 'Nền mới', goal: 'Mục tiêu mới', level: 'Beginner', sessions_per_week: 1, content_version: 2, status: 'HOAT_DONG', days: [{ order: 1, name: 'Ngày 1', estimated_minutes: 50, exercises: [] }] } })
    api.taoPhienBanGiaoAnMau.mockRejectedValue({ httpStatus: 409, code: 'WORKOUT_TEMPLATE_STALE', message: 'Nội dung đã thay đổi.' })
  })

  it('giữ bản nháp khi stale, hiển thị so sánh và chỉ đổi version sau đối soát explicit', async () => {
    const wrapper = mount(GiaoAnMauTaoPhienBan)
    await flushPromises()
    await wrapper.find('#giao-an-revision-name').setValue('Bản nháp của tôi')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.taoPhienBanGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('#giao-an-revision-name').element.value).toBe('Bản nháp của tôi')
    expect(wrapper.text()).toContain('So sánh bản nháp và nền mới nhất')
    expect(wrapper.text()).toContain('Phiên bản đã gửi 1')
    await wrapper.find('[role="dialog"] button.nut--chinh').trigger('click')
    await flushPromises()
    expect(api.taoPhienBanGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Đang dùng content_version 2')
    expect(wrapper.find('#giao-an-revision-name').element.value).toBe('Bản nháp của tôi')
  })

  it('tạo COW thành công với nullable metadata/notes, payload đầy đủ và điều hướng theo response', async () => {
    const detail = {
      id: 7,
      code: 'GA01',
      name: 'Nền cũ',
      goal: 'Mục tiêu',
      level: 'Beginner',
      sessions_per_week: 1,
      description: null,
      content_version: 1,
      status: 'HOAT_DONG',
      days: [{
        order: 1,
        name: 'Ngày 1',
        estimated_minutes: 45,
        exercises: [{
          exercise_id: 11,
          order: 1,
          target_sets: 3,
          min_reps: 8,
          max_reps: 12,
          rest_seconds: 60,
          notes: null,
        }],
      }],
    }
    api.taiChiTietGiaoAnMau.mockReset().mockResolvedValueOnce({ data: detail })
    api.taoPhienBanGiaoAnMau.mockReset().mockResolvedValueOnce({ data: { new_template_id: 18 } })
    api.xuLyGiaoAnMauDaCu.mockImplementation(() => ({ laXungDot: false }))

    const wrapper = mount(GiaoAnMauTaoPhienBan)
    await flushPromises()
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.taoPhienBanGiaoAnMau).toHaveBeenCalledWith(7, {
      new_code: 'GA01-V2',
      name: 'Nền cũ',
      goal: 'Mục tiêu',
      level: 'Beginner',
      sessions_per_week: 1,
      description: null,
      status: 'HOAT_DONG',
      expected_content_version: 1,
      days: [{
        order: 1,
        name: 'Ngày 1',
        estimated_minutes: 45,
        exercises: [{
          exercise_id: 11,
          order: 1,
          target_sets: 3,
          min_reps: 8,
          max_reps: 12,
          rest_seconds: 60,
          notes: null,
        }],
      }],
    })
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGiaoAnMau', params: { id: 18 } })
  })

  it('lỗi 422 revision giữ bản nháp, hiển thị field/global và không điều hướng', async () => {
    api.taoPhienBanGiaoAnMau.mockReset().mockRejectedValueOnce({
      httpStatus: 422,
      code: 'VALIDATION_ERROR',
      message: 'Dữ liệu phiên bản chưa hợp lệ.',
      fieldErrors: { new_code: ['Mã phiên bản đã tồn tại.'] },
    })
    api.xuLyGiaoAnMauDaCu.mockImplementation(() => ({ laXungDot: false }))

    const wrapper = mount(GiaoAnMauTaoPhienBan)
    await flushPromises()
    await wrapper.find('#giao-an-revision-name').setValue('Bản nháp giữ lại')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.taoPhienBanGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('#giao-an-revision-name').element.value).toBe('Bản nháp giữ lại')
    expect(wrapper.find('#giao-an-revision-code-loi').text()).toContain('Mã phiên bản đã tồn tại.')
    expect(wrapper.text()).toContain('Dữ liệu phiên bản chưa hợp lệ.')
    expect(wrapper.find('button[type="submit"]').element.disabled).toBe(false)
    expect(router.push).not.toHaveBeenCalled()
  })

  it('thay thế bài tập đã ngừng và giữ prescription đầy đủ trong revision', async () => {
    const detail = {
      id: 7,
      code: 'GA01',
      name: 'Nền cũ',
      goal: 'Mục tiêu',
      level: 'Beginner',
      sessions_per_week: 1,
      content_version: 1,
      status: 'HOAT_DONG',
      days: [{
        order: 1,
        name: 'Ngày 1',
        estimated_minutes: 45,
        exercises: [{
          exercise_id: 77,
          exercise_name: 'Bài tập đã ngừng',
          order: 1,
          target_sets: 3,
          min_reps: 8,
          max_reps: 12,
          rest_seconds: 60,
          notes: null,
        }],
      }],
    }
    api.taiChiTietGiaoAnMau.mockReset().mockResolvedValueOnce({ data: detail })
    api.taiDanhSachBaiTap.mockReset().mockResolvedValueOnce({ data: [{ id: 88, name: 'Bài tập mới' }] })
    api.taoPhienBanGiaoAnMau.mockReset().mockResolvedValueOnce({ data: { new_template_id: 18 } })
    api.xuLyGiaoAnMauDaCu.mockImplementation(() => ({ laXungDot: false }))

    const wrapper = mount(GiaoAnMauTaoPhienBan)
    await flushPromises()
    expect(wrapper.find('#giao-an-exercise-0-0 option[value="77"]').text()).toContain('Bài tập đã ngừng')
    await wrapper.find('#giao-an-exercise-0-0').setValue('88')
    await wrapper.find('#giao-an-rest-0-0').setValue(75)
    await wrapper.find('#giao-an-notes-0-0').setValue('Thay thế chủ động')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.taoPhienBanGiaoAnMau).toHaveBeenCalledTimes(1)
    const payload = api.taoPhienBanGiaoAnMau.mock.calls[0][1]
    expect(payload.days[0].exercises[0]).toEqual({
      exercise_id: 88,
      order: 1,
      target_sets: 3,
      min_reps: 8,
      max_reps: 12,
      rest_seconds: 75,
      notes: 'Thay thế chủ động',
    })
    expect(JSON.stringify(payload.days)).not.toContain('77')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGiaoAnMau', params: { id: 18 } })
  })

  it('khóa double-submit khi POST revision đang chờ và chỉ điều hướng sau khi hoàn tất', async () => {
    let resolveMutation
    api.taoPhienBanGiaoAnMau.mockReset().mockReturnValueOnce(new Promise((resolve) => { resolveMutation = resolve }))
    api.xuLyGiaoAnMauDaCu.mockImplementation(() => ({ laXungDot: false }))

    const wrapper = mount(GiaoAnMauTaoPhienBan)
    await flushPromises()
    const form = wrapper.find('form')
    await form.trigger('submit')
    await form.trigger('submit')

    expect(api.taoPhienBanGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('button[type="submit"]').element.disabled).toBe(true)
    expect(router.push).not.toHaveBeenCalled()
    resolveMutation({ data: { new_template_id: 19 } })
    await flushPromises()
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGiaoAnMau', params: { id: 19 } })
  })

  it('initial GET thất bại có thể retry lại bằng GET và không POST', async () => {
    api.taiChiTietGiaoAnMau.mockReset()
      .mockRejectedValueOnce({ httpStatus: 503, message: 'Không thể tải nền.' })
      .mockResolvedValueOnce({ data: { id: 7, code: 'GA01', name: 'Nền', goal: 'Mục tiêu', level: 'Beginner', sessions_per_week: 1, content_version: 1, status: 'HOAT_DONG', days: [{ order: 1, name: 'Ngày 1', estimated_minutes: 45, exercises: [] }] } })
    const wrapper = mount(GiaoAnMauTaoPhienBan)
    await flushPromises()
    expect(wrapper.text()).toContain('Không thể tải nền.')
    await wrapper.findAll('button').find((button) => button.text().includes('Thử lại')).trigger('click')
    await flushPromises()
    expect(api.taiChiTietGiaoAnMau).toHaveBeenCalledTimes(2)
    expect(api.taoPhienBanGiaoAnMau).not.toHaveBeenCalled()
    expect(wrapper.find('#giao-an-revision-name').element.value).toBe('Nền')
  })
})
