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
})
