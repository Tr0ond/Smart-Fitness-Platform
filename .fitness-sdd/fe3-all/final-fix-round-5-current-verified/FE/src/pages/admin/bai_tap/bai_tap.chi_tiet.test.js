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
      instructions: 'Keep the back straight.',
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

  it('initial detail error is retryable and hydrates nullable DTO fields', async () => {
    api.taiChiTietBaiTap.mockReset()
      .mockRejectedValueOnce({ httpStatus: 503, message: 'initial detail unavailable' })
      .mockResolvedValueOnce({ data: {
        id: 21,
        code: 'SQUAT',
        name: 'Squat',
        difficulty: 'Beginner',
        instructions: 'Keep the back straight.',
        image_path: null,
        video_path: null,
        status: 'HOAT_DONG',
        equipment: [],
        muscle_groups: [],
      } })

    const wrapper = mount(BaiTapChiTiet)
    await flushPromises()
    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.text()).toContain('initial detail unavailable')

    await wrapper.find('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.find('#bai-tap-detail-instructions').element.value).toBe('Keep the back straight.')
    expect(wrapper.find('#bai-tap-detail-image').element.value).toBe('')
    expect(wrapper.find('#bai-tap-detail-video').element.value).toBe('')
    expect(api.taiChiTietBaiTap).toHaveBeenCalledTimes(2)
  })

  it('giu metadata null khi sua truong khac va tai lai authoritative', async () => {
    const detail = {
      id: 21,
      code: 'SQUAT',
      name: 'Squat',
      difficulty: 'Beginner',
      instructions: 'Keep the back straight.',
      image_path: null,
      video_path: null,
      metadata: null,
      status: 'HOAT_DONG',
      equipment: [],
      muscle_groups: [],
    }
    api.taiChiTietBaiTap.mockReset()
      .mockResolvedValueOnce({ data: detail })
      .mockResolvedValueOnce({ data: { ...detail, name: 'Squat authoritative', metadata: null } })
    api.capNhatBaiTap.mockReset().mockResolvedValueOnce({ data: { id: 21 } })

    const wrapper = mount(BaiTapChiTiet)
    await flushPromises()
    await wrapper.find('#bai-tap-detail-name').setValue('Squat draft')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.capNhatBaiTap).toHaveBeenCalledWith(21, expect.objectContaining({ metadata: null }))
    expect(api.capNhatBaiTap.mock.calls[0][1]).not.toHaveProperty('metadata', {})
    expect(wrapper.vm.form.metadata).toBeNull()
    expect(wrapper.find('#bai-tap-detail-name').element.value).toBe('Squat authoritative')
  })

  it('chặn instructions blank ở detail trước store/PATCH và giữ bản nháp', async () => {
    const wrapper = mount(BaiTapChiTiet)
    await flushPromises()
    await wrapper.find('#bai-tap-detail-instructions').setValue('   ')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.capNhatBaiTap).not.toHaveBeenCalled()
    expect(api.taiChiTietBaiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.find('#bai-tap-detail-instructions').element.value).toBe('   ')
    expect(wrapper.find('#bai-tap-detail-instructions').attributes('aria-invalid')).toBe('true')
    expect(wrapper.text()).toContain('Hướng dẫn là bắt buộc.')
    expect(wrapper.text()).toContain('Dữ liệu bài tập không hợp lệ.')
    expect(wrapper.find('form button[type="submit"]').element.disabled).toBe(false)
    expect(router.push).not.toHaveBeenCalled()
  })

  it('metadata save excludes status, cancel sends nothing, confirm sends status only', async () => {
    const detail = (status = 'HOAT_DONG') => ({
      id: 21,
      code: 'SQUAT',
      name: 'Squat',
      difficulty: 'Beginner',
      instructions: 'Keep the back straight.',
      image_path: null,
      video_path: null,
      status,
      equipment: [],
      muscle_groups: [],
    })
    api.taiChiTietBaiTap.mockReset()
      .mockResolvedValueOnce({ data: detail() })
      .mockResolvedValueOnce({ data: detail() })
      .mockResolvedValueOnce({ data: detail('NGUNG_SU_DUNG') })
    api.capNhatBaiTap.mockReset().mockResolvedValue({ data: { id: 21 } })

    const wrapper = mount(BaiTapChiTiet)
    await flushPromises()
    await wrapper.find('#bai-tap-detail-name').setValue('Squat updated')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.capNhatBaiTap).toHaveBeenNthCalledWith(1, 21, expect.not.objectContaining({ status: expect.anything() }))
    api.capNhatBaiTap.mockClear()

    await wrapper.get('[data-testid="bai-tap-status-action"]').trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    await wrapper.find('[role="dialog"] button.nut--phu').trigger('click')
    expect(api.capNhatBaiTap).not.toHaveBeenCalled()
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)

    await wrapper.get('[data-testid="bai-tap-status-action"]').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatBaiTap).toHaveBeenCalledWith(21, { status: 'NGUNG_SU_DUNG' })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('unknown status result reconciles with GET before allowing one explicit retry', async () => {
    const active = {
      id: 21,
      code: 'SQUAT',
      name: 'Squat',
      difficulty: 'Beginner',
      instructions: 'Keep the back straight.',
      image_path: null,
      video_path: null,
      status: 'HOAT_DONG',
      equipment: [],
      muscle_groups: [],
    }
    api.taiChiTietBaiTap.mockReset()
      .mockResolvedValueOnce({ data: active })
      .mockResolvedValueOnce({ data: active })
      .mockResolvedValueOnce({ data: { ...active, status: 'NGUNG_SU_DUNG' } })
    api.capNhatBaiTap.mockReset()
      .mockRejectedValueOnce({ isNetworkError: true, message: 'status result unknown' })
      .mockResolvedValueOnce({ data: { id: 21 } })

    const wrapper = mount(BaiTapChiTiet)
    await flushPromises()
    await wrapper.get('[data-testid="bai-tap-status-action"]').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatBaiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)

    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.taiChiTietBaiTap).toHaveBeenCalledTimes(2)
    expect(api.capNhatBaiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)

    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatBaiTap).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('status 422 remains visible and pending metadata submit cannot duplicate', async () => {
    api.capNhatBaiTap.mockRejectedValueOnce({ httpStatus: 422, message: 'status validation rejected' })
    const wrapper = mount(BaiTapChiTiet)
    await flushPromises()
    await wrapper.get('[data-testid="bai-tap-status-action"]').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatBaiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('status validation rejected')

    let resolveMutation
    api.capNhatBaiTap.mockReset().mockReturnValueOnce(new Promise((resolve) => { resolveMutation = resolve }))
    await wrapper.find('#bai-tap-detail-name').setValue('Pending update')
    await wrapper.find('form').trigger('submit')
    await wrapper.find('form').trigger('submit')
    expect(api.capNhatBaiTap).toHaveBeenCalledTimes(1)
    expect(wrapper.find('form button[type="submit"]').element.disabled).toBe(true)
    resolveMutation({ data: { id: 21 } })
    await flushPromises()
  })
})
