import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import HuanLuyenVienTaoMoi from './huan_luyen_vien.tao_moi.vue'
import { taoHuanLuyenVien } from '../../../services/huan_luyen_vien.api.js'
import { useHuanLuyenVienStore } from '../../../stores/huan_luyen_vien.store.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'

const { taiDanhSachTaiKhoanApi, taiDanhSachHuanLuyenVienApi } = vi.hoisted(() => ({
  taiDanhSachTaiKhoanApi: vi.fn(),
  taiDanhSachHuanLuyenVienApi: vi.fn(),
}))

vi.mock('../../../services/huan_luyen_vien.api.js', async () => {
  const actual = await vi.importActual('../../../services/huan_luyen_vien.api.js')
  return { ...actual, taoHuanLuyenVien: vi.fn(), onboardTaiKhoanHuanLuyenVien: vi.fn() }
})
vi.mock('../../../services/tai_khoan.api.js', async () => {
  const actual = await vi.importActual('../../../services/tai_khoan.api.js')
  return {
    ...actual,
    taiDanhSachTaiKhoan: taiDanhSachTaiKhoanApi,
    taiDanhSachHuanLuyenVien: taiDanhSachHuanLuyenVienApi,
  }
})

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/huan-luyen-vien/tao-moi', name: 'adminTaoHuanLuyenVien', component: HuanLuyenVienTaoMoi },
      { path: '/admin/tai-khoan/:id', name: 'adminChiTietTaiKhoan', component: { template: '<h1>Account</h1>' } },
      { path: '/admin/huan-luyen-vien', name: 'adminHuanLuyenVien', component: { template: '<h1>PT</h1>' } },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

describe('huan_luyen_vien.tao_moi FE2-T02', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    taiDanhSachTaiKhoanApi.mockResolvedValue({ data: {
      items: [{ id: 8, name: 'Existing', email: 'existing@example.com', roles: [] }],
      pagination: { current_page: 1, per_page: 20, total: 1, last_page: 1 },
    } })
    taiDanhSachHuanLuyenVienApi.mockResolvedValue({ data: {
      items: [], pagination: { current_page: 1, per_page: 20, total: 0, last_page: 1 },
    } })
    taoHuanLuyenVien.mockResolvedValue({ data: {
      account: { id: 7, email: 'pt@example.com', status: 'HOAT_DONG' },
      trainer_profile: { id: 11, trainer_code: 'PT000011', status: 'HOAT_DONG' },
      role: { code: 'PT', active: true }, invitation: 'QUEUED',
    } })
  })

  it('render form onboarding va trang thai invitation tu Backend', async () => {
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/tao-moi')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienTaoMoi, { global: { plugins: [router] } })

    expect(wrapper.get('h1').text()).toBe('Tạo mới Huấn luyện viên')
    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('BLOCKER-01')
    expect(wrapper.text()).not.toMatch(/gửi lại|resend/i)
    await wrapper.get('#trainer-name').setValue('PT A')
    await wrapper.get('#trainer-email').setValue('pt@example.com')
    await wrapper.get('form').trigger('submit')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    await wrapper.find('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()
    expect(taoHuanLuyenVien).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('QUEUED')
  })

  it('422 render du tat ca field new va focus theo thu tu form', async () => {
    taoHuanLuyenVien.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu onboarding chưa hợp lệ.',
      fieldErrors: {
        name: ['Tên lỗi.'],
        email: ['Email lỗi.'],
        phone: ['Số điện thoại lỗi.'],
        introduction: ['Giới thiệu lỗi.'],
        specialties: ['Chuyên môn lỗi.'],
        status: ['Trạng thái lỗi.'],
      },
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/tao-moi')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienTaoMoi, { attachTo: document.body, global: { plugins: [router] } })

    await wrapper.get('#trainer-name').setValue('PT A')
    await wrapper.get('#trainer-email').setValue('pt@example.com')
    await wrapper.get('form').trigger('submit')
    await wrapper.find('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    for (const [field, text] of Object.entries({
      name: 'Tên lỗi.',
      email: 'Email lỗi.',
      phone: 'Số điện thoại lỗi.',
      introduction: 'Giới thiệu lỗi.',
      specialties: 'Chuyên môn lỗi.',
      status: 'Trạng thái lỗi.',
    })) {
      expect(wrapper.text()).toContain(text)
      expect(wrapper.get(`[name="${field}"]`).attributes('aria-invalid')).toBe('true')
    }
    expect(document.activeElement).toBe(wrapper.get('#trainer-name').element)
    wrapper.unmount()
  })

  it('existing mode 422 focus account_id va hien loi an toan', async () => {
    const { onboardTaiKhoanHuanLuyenVien } = await import('../../../services/huan_luyen_vien.api.js')
    onboardTaiKhoanHuanLuyenVien.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Account không hợp lệ.',
      fieldErrors: { account_id: ['Account đã có hồ sơ PT.'] },
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/tao-moi')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienTaoMoi, { attachTo: document.body, global: { plugins: [router] } })

    await wrapper.findAll('button').find((button) => button.text() === 'Account hiện hữu').trigger('click')
    await flushPromises()
    await wrapper.get('#trainer-account').setValue('8')
    await wrapper.get('form').trigger('submit')
    await wrapper.find('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(wrapper.get('#trainer-account-error').text()).toContain('Account đã có hồ sơ PT.')
    expect(document.activeElement).toBe(wrapper.get('#trainer-account').element)
    wrapper.unmount()
  })

  it('onboarding mutation 403 clear scoped stores va route named forbidden', async () => {
    taoHuanLuyenVien.mockRejectedValueOnce({
      httpStatus: 403,
      message: 'Bạn không có quyền thực hiện thao tác này.',
      fieldErrors: {},
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/tao-moi')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienTaoMoi, { global: { plugins: [router] } })
    const hoSoStore = useHuanLuyenVienStore()
    const taiKhoanStore = useTaiKhoanStore()
    hoSoStore.ketQuaOnboarding = { account: { id: 7 } }
    taiKhoanStore.danhSachTaiKhoan = [{ id: 8 }]

    await wrapper.get('#trainer-name').setValue('PT A')
    await wrapper.get('#trainer-email').setValue('pt@example.com')
    await wrapper.get('form').trigger('submit')
    await wrapper.find('[role="dialog"] .nut--chinh').trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
    expect(hoSoStore.ketQuaOnboarding).toBeNull()
    expect(taiKhoanStore.danhSachTaiKhoan).toEqual([])
    wrapper.unmount()
  })
})
