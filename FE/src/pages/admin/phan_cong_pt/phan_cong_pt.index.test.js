import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import PhanCongPt from './phan_cong_pt.index.vue'
import * as assignmentApi from '../../../services/phan_cong_pt.api.js'
import * as accountApi from '../../../services/tai_khoan.api.js'

vi.mock('../../../services/phan_cong_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/phan_cong_pt.api.js')
  return {
    ...actual,
    taiDanhSachPhanCong: vi.fn(),
    taiChiTietPhanCong: vi.fn(),
    ketThucPhanCong: vi.fn(),
    phanCongLai: vi.fn(),
    taoPhanCong: vi.fn(),
  }
})
vi.mock('../../../services/tai_khoan.api.js', async () => {
  const actual = await vi.importActual('../../../services/tai_khoan.api.js')
  return {
    ...actual,
    taiDanhSachHoiVien: vi.fn(),
    taiDanhSachHuanLuyenVien: vi.fn(),
  }
})

const ASSIGNMENT = {
  id: 1,
  member: { id: 7, code: 'HV1', name: 'Member' },
  trainer: { id: 11, code: 'PT1', name: 'Trainer', status: 'HOAT_DONG' },
  start_at: '2026-09-07T10:00:00.000Z', end_at: null, reason: null, is_current: true,
  created_at: '2026-09-07T09:00:00.000Z', updated_at: '2026-09-07T09:00:00.000Z',
}

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/phan-cong-pt', name: 'adminPhanCongPt', component: PhanCongPt },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

describe('phan_cong_pt.index FE2-T06/T07', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    assignmentApi.taiDanhSachPhanCong.mockResolvedValue({ data: {
      items: [ASSIGNMENT], pagination: { current_page: 1, per_page: 20, total: 1, last_page: 1 },
    } })
    assignmentApi.taiChiTietPhanCong.mockResolvedValue({ data: ASSIGNMENT })
    assignmentApi.ketThucPhanCong.mockResolvedValue({ data: { ...ASSIGNMENT, end_at: '2026-09-08T10:00:00.000Z', is_current: false } })
    assignmentApi.phanCongLai.mockResolvedValue({ data: { ...ASSIGNMENT, id: 2, trainer: { ...ASSIGNMENT.trainer, id: 12, code: 'PT2', name: 'Trainer 2' } } })
    assignmentApi.taoPhanCong.mockResolvedValue({ data: ASSIGNMENT })
    accountApi.taiDanhSachHoiVien.mockResolvedValue({ data: {
      items: [{ id: 17, name: 'Member', email: 'm@example.com', status: 'HOAT_DONG',
        roles: [{ code: 'MEMBER', active: true }], member_profile: { id: 7, code: 'HV1' } }],
      pagination: { current_page: 1, per_page: 20, total: 1, last_page: 1 },
    } })
    accountApi.taiDanhSachHuanLuyenVien.mockResolvedValue({ data: {
       items: [
         { id: 19, name: 'Trainer', email: 'pt@example.com', status: 'HOAT_DONG',
           roles: [{ code: 'PT', active: true }], trainer_profile: { id: 11, code: 'PT1', status: 'HOAT_DONG' } },
         { id: 20, name: 'Trainer 2', email: 'pt2@example.com', status: 'HOAT_DONG',
           roles: [{ code: 'PT', active: true }], trainer_profile: { id: 12, code: 'PT2', status: 'HOAT_DONG' } },
       ],
       pagination: { current_page: 1, per_page: 20, total: 2, last_page: 1 },
    } })
  })

  it('initial GET hien history authoritative va selector dung profile ids', async () => {
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.text()).toContain('Member')
    expect(wrapper.text()).toContain('Trainer')
    expect(wrapper.text()).toContain('Đang hiệu lực')
    expect(wrapper.find('#assignment-create-member').find('option[value="7"]').exists()).toBe(true)
    expect(wrapper.find('#assignment-create-trainer').find('option[value="11"]').exists()).toBe(true)
    expect(assignmentApi.taiDanhSachPhanCong).toHaveBeenCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('confirmation dialog neu ro Q05 va khong xoa history', async () => {
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { global: { plugins: [router] } })
    await flushPromises()
    await wrapper.find('tbody .nut--nguy-hiem').trigger('click')
    expect(wrapper.text()).toContain('Chat history vẫn được giữ')
    expect(wrapper.text()).toContain('PT hiện tại mất scope')
  })

  it('yeu cau xac nhan rieng truoc khi tao assignment', async () => {
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { global: { plugins: [router] } })
    await flushPromises()

    await wrapper.find('#assignment-create-member').setValue('7')
    await wrapper.find('#assignment-create-trainer').setValue('11')
    const nutTao = wrapper.findAll('button').find((nut) => nut.text().includes('Tạo phân công'))
    await nutTao.trigger('click')
    expect(wrapper.find('[role="dialog"]').text()).toContain('Tạo phân công?')
    expect(assignmentApi.taoPhanCong).not.toHaveBeenCalled()

    await wrapper.find('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()
    expect(assignmentApi.taoPhanCong).toHaveBeenCalledWith({
      member_id: 7,
      trainer_id: 11,
      start_at: undefined,
      end_at: undefined,
    })
  })

  it('reassign dung draft rieng, slot trong dialog va payload mot lan', async () => {
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()

    await wrapper.get('#assignment-create-trainer').setValue('11')
    await wrapper.get('#assignment-create-start').setValue('2026-09-08T09:00')
    await wrapper.get('tbody .nut--phu:last-child').trigger('click')
    expect(wrapper.get('[role="dialog"] #assignment-reassign-trainer').exists()).toBe(true)
    expect(wrapper.get('[role="dialog"] #assignment-reassign-start').element.value).toBe('')
    expect(wrapper.get('[role="dialog"] #assignment-reassign-trainer').element.value).toBe('')

    await wrapper.get('#assignment-reassign-trainer').setValue('12')
    await wrapper.get('#assignment-reassign-start').setValue('2026-09-09T12:00')
    await wrapper.get('#assignment-reassign-reason').setValue('Đổi lịch')
    wrapper.get('[role="dialog"] .nut--chinh').element.focus()
    await wrapper.get('[role="dialog"] .nut--chinh').trigger('keydown', { key: 'Tab' })
    expect(document.activeElement).toBe(wrapper.get('#assignment-reassign-trainer').element)

    await wrapper.get('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(assignmentApi.phanCongLai).toHaveBeenCalledTimes(1)
    expect(assignmentApi.phanCongLai).toHaveBeenCalledWith(1, {
      trainer_id: 12,
      start_at: '2026-09-09T05:00:00.000Z',
      reason: 'Đổi lịch',
    })
    expect(assignmentApi.taoPhanCong).not.toHaveBeenCalled()

    await wrapper.get('tbody .nut--phu:last-child').trigger('click')
    expect(wrapper.get('#assignment-reassign-trainer').element.value).toBe('')
    expect(wrapper.get('#assignment-reassign-start').element.value).toBe('')
    expect(wrapper.get('#assignment-reassign-reason').element.value).toBe('')
    await wrapper.get('[role="dialog"] .nut--phu').trigger('click')
    await flushPromises()
    wrapper.unmount()
  })

  it('create 422 hien du field va dong dialog focus member external', async () => {
    assignmentApi.taoPhanCong.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu phân công chưa hợp lệ.',
      fieldErrors: {
        member_id: ['Hội viên lỗi.'],
        trainer_id: ['PT lỗi.'],
        start_at: ['Bắt đầu lỗi.'],
        end_at: ['Kết thúc lỗi.'],
      },
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()
    await wrapper.get('#assignment-create-member').setValue('7')
    await wrapper.get('#assignment-create-trainer').setValue('11')
    await wrapper.get('#assignment-create-start').setValue('2026-09-08T09:00')
    await wrapper.get('#assignment-create-end').setValue('2026-09-08T10:00')
    await wrapper.findAll('button').find((button) => button.text().includes('Tạo phân công')).trigger('click')
    await wrapper.get('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    for (const [field, text] of Object.entries({
      member_id: 'Hội viên lỗi.',
      trainer_id: 'PT lỗi.',
      start_at: 'Bắt đầu lỗi.',
      end_at: 'Kết thúc lỗi.',
    })) {
      expect(wrapper.text()).toContain(text)
      expect(wrapper.get(`[name="${field}"]`).attributes('aria-invalid')).toBe('true')
    }
    expect(document.activeElement).toBe(wrapper.get('#assignment-create-member').element)
    wrapper.unmount()
  })

  it('create baseline loi hien alert focus va khong POST', async () => {
    assignmentApi.taiDanhSachPhanCong.mockReset()
    assignmentApi.taiDanhSachPhanCong
      .mockResolvedValueOnce({ data: {
        items: [ASSIGNMENT], pagination: { current_page: 1, per_page: 20, total: 1, last_page: 1 },
      } })
      .mockRejectedValueOnce({ isNetworkError: true, message: 'Không thể đọc baseline.' })
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()
    await wrapper.get('#assignment-create-member').setValue('7')
    await wrapper.get('#assignment-create-trainer').setValue('11')
    await wrapper.findAll('button').find((button) => button.text().includes('Tạo phân công')).trigger('click')
    await wrapper.get('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(assignmentApi.taoPhanCong).not.toHaveBeenCalled()
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(wrapper.get('#phan-cong-pt-loi-mutation').text()).toContain('Không thể đọc baseline.')
    expect(document.activeElement).toBe(wrapper.get('#phan-cong-pt-loi-mutation').element)
    wrapper.unmount()
  })

  it('create 409 dung mot POST hien aggregate focus va giu draft', async () => {
    assignmentApi.taoPhanCong.mockRejectedValueOnce({
      httpStatus: 409,
      message: 'Phân công bị xung đột.',
      fieldErrors: {},
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()
    await wrapper.get('#assignment-create-member').setValue('7')
    await wrapper.get('#assignment-create-trainer').setValue('11')
    await wrapper.findAll('button').find((button) => button.text().includes('Tạo phân công')).trigger('click')
    await wrapper.get('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(assignmentApi.taoPhanCong).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(wrapper.get('#phan-cong-pt-loi-mutation').text()).toContain('Phân công bị xung đột.')
    expect(document.activeElement).toBe(wrapper.get('#phan-cong-pt-loi-mutation').element)
    expect(wrapper.get('#assignment-create-member').element.value).toBe('7')
    expect(wrapper.get('#assignment-create-trainer').element.value).toBe('11')
    wrapper.unmount()
  })

  it('create malformed 2xx doi soat that bai chi POST mot lan va dong confirm', async () => {
    assignmentApi.taoPhanCong.mockResolvedValueOnce({ data: { id: 99 } })
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()
    await wrapper.get('#assignment-create-member').setValue('7')
    await wrapper.get('#assignment-create-trainer').setValue('11')
    await wrapper.findAll('button').find((button) => button.text().includes('Tạo phân công')).trigger('click')
    await wrapper.get('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(assignmentApi.taoPhanCong).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(wrapper.get('#phan-cong-pt-unknown').text()).toContain('chưa xác định')
    expect(wrapper.get('#phan-cong-pt-loi-mutation').exists()).toBe(true)
    expect(document.activeElement).toBe(wrapper.get('#phan-cong-pt-loi-mutation').element)
    expect(wrapper.find('[role="dialog"] .nut--chinh').exists()).toBe(false)
    wrapper.unmount()
  })

  it('reassign 422 giu dialog va focus trainer dau tien', async () => {
    assignmentApi.phanCongLai.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu đổi PT chưa hợp lệ.',
      fieldErrors: {
        trainer_id: ['PT mới lỗi.'],
        start_at: ['Bắt đầu mới lỗi.'],
        reason: ['Lý do lỗi.'],
      },
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()
    await wrapper.get('tbody .nut--phu:last-child').trigger('click')
    await wrapper.get('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(wrapper.get('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.get('#assignment-reassign-trainer-error').text()).toContain('PT mới lỗi.')
    expect(wrapper.get('#assignment-reassign-start-error').text()).toContain('Bắt đầu mới lỗi.')
    expect(wrapper.get('#assignment-reassign-reason-error').text()).toContain('Lý do lỗi.')
    expect(document.activeElement).toBe(wrapper.get('#assignment-reassign-trainer').element)
    wrapper.unmount()
  })

  it('end 422 hien reason va focus control trong dialog', async () => {
    assignmentApi.ketThucPhanCong.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Lý do chưa hợp lệ.',
      fieldErrors: { reason: ['Lý do kết thúc lỗi.'] },
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/phan-cong-pt')
    await router.isReady()
    const wrapper = mount(PhanCongPt, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()
    await wrapper.get('tbody .nut--nguy-hiem').trigger('click')
    await wrapper.get('[role="dialog"] .nut--nguy-hiem').trigger('click')
    await flushPromises()

    expect(wrapper.get('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.get('#assignment-end-reason-error').text()).toContain('Lý do kết thúc lỗi.')
    expect(document.activeElement).toBe(wrapper.get('#assignment-end-reason').element)
    wrapper.unmount()
  })
})
