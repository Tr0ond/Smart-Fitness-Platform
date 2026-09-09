import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import HuanLuyenVienChiTiet from './huan_luyen_vien.chi_tiet.vue'
import { taiChiTietHuanLuyenVien, taiDanhSachHuanLuyenVien } from '../../../services/tai_khoan.api.js'
import { onboardTaiKhoanHuanLuyenVien, taiHoSoHuanLuyenVien } from '../../../services/huan_luyen_vien.api.js'
import { useHuanLuyenVienStore } from '../../../stores/huan_luyen_vien.store.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'

vi.mock('../../../services/tai_khoan.api.js', async () => {
  const actual = await vi.importActual('../../../services/tai_khoan.api.js')
  return { ...actual, taiChiTietHuanLuyenVien: vi.fn(), taiDanhSachHuanLuyenVien: vi.fn() }
})
vi.mock('../../../services/huan_luyen_vien.api.js', async () => {
  const actual = await vi.importActual('../../../services/huan_luyen_vien.api.js')
  return { ...actual, taiHoSoHuanLuyenVien: vi.fn(), onboardTaiKhoanHuanLuyenVien: vi.fn() }
})

const ACCOUNT = {
  id: 7, name: 'PT A', email: 'pt@example.com', phone: null, status: 'HOAT_DONG',
  roles: [{ assignment_id: 1, code: 'PT', active: true }],
  trainer_profile: { id: 11, code: 'PT000011', status: 'HOAT_DONG' },
  branch: { id: 1, code: 'HN', name: 'Hà Nội' },
}
const PROFILE = {
  account_id: 7, trainer_profile_id: 11, trainer_code: 'PT000011', status: 'HOAT_DONG',
  introduction: 'Intro', specialties: 'Strength', updated_at: '2026-09-07T10:00:00.000Z',
}
const ACCOUNT_B = {
  id: 8, name: 'PT B', email: 'pt-b@example.com', phone: null, status: 'HOAT_DONG',
  roles: [{ assignment_id: 2, code: 'PT', active: true }],
  trainer_profile: { id: 12, code: 'PT000012', status: 'HOAT_DONG' },
  branch: { id: 1, code: 'HN', name: 'Hà Nội' },
}
const PROFILE_B = {
  account_id: 8, trainer_profile_id: 12, trainer_code: 'PT000012', status: 'HOAT_DONG',
  introduction: 'Intro B', specialties: 'Mobility', updated_at: '2026-09-07T11:00:00.000Z',
}

function taoDeferred() {
  let resolve
  let reject
  const promise = new Promise((resolvePromise, rejectPromise) => {
    resolve = resolvePromise
    reject = rejectPromise
  })
  return { promise, resolve, reject }
}

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/huan-luyen-vien/:id', name: 'adminChiTietHuanLuyenVien', component: HuanLuyenVienChiTiet },
      { path: '/admin/huan-luyen-vien', name: 'adminHuanLuyenVien', component: { template: '<h1>PT</h1>' } },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

describe('huan_luyen_vien.chi_tiet FE2-T04', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    taiChiTietHuanLuyenVien.mockResolvedValue({ data: ACCOUNT })
    taiHoSoHuanLuyenVien.mockResolvedValue({ data: PROFILE })
  })

  it('prefill tu GET authoritative va chi render field editable cho profile', async () => {
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/7')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienChiTiet, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.get('#trainer-profile-introduction').element.value).toBe('Intro')
    expect(wrapper.get('#trainer-profile-specialties').element.value).toBe('Strength')
    expect(wrapper.get('#trainer-profile-status').element.value).toBe('HOAT_DONG')
    expect(wrapper.text()).not.toContain('BLOCKER-01')
    expect(wrapper.text()).not.toContain('assignment')
    expect(taiHoSoHuanLuyenVien).toHaveBeenCalledWith(7)
  })

  it('unknown profile status khong tu dong render form de resubmit', async () => {
    taiHoSoHuanLuyenVien.mockResolvedValue({ data: { ...PROFILE, status: 'MOI' } })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/7')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienChiTiet, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.text()).toContain('Không xác định')
    expect(wrapper.find('#trainer-profile-status').exists()).toBe(false)
  })

  it('profile update 422 render du field va focus field dau tien', async () => {
    onboardTaiKhoanHuanLuyenVien.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu hồ sơ chưa hợp lệ.',
      fieldErrors: {
        introduction: ['Giới thiệu không hợp lệ.'],
        specialties: ['Chuyên môn không hợp lệ.'],
        status: ['Trạng thái không hợp lệ.'],
      },
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/7')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienChiTiet, { attachTo: document.body, global: { plugins: [router] } })
    await flushPromises()

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('#trainer-profile-introduction-error').text()).toContain('Giới thiệu không hợp lệ.')
    expect(wrapper.get('#trainer-profile-specialties-error').text()).toContain('Chuyên môn không hợp lệ.')
    expect(wrapper.get('#trainer-profile-status-error').text()).toContain('Trạng thái không hợp lệ.')
    expect(document.activeElement).toBe(wrapper.get('#trainer-profile-introduction').element)
    expect(wrapper.get('#trainer-profile-introduction').element.value).toBe('Intro')
    wrapper.unmount()
  })

  it('profile update 403 clear PT scope va route named forbidden', async () => {
    onboardTaiKhoanHuanLuyenVien.mockRejectedValueOnce({
      httpStatus: 403,
      message: 'Bạn không có quyền thực hiện thao tác này.',
      fieldErrors: {},
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/7')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienChiTiet, { global: { plugins: [router] } })
    await flushPromises()
    const taiKhoanStore = useTaiKhoanStore()
    const hoSoStore = useHuanLuyenVienStore()

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
    expect(taiKhoanStore.huanLuyenVienDaChon).toBeNull()
    expect(hoSoStore.hoSoDaChon).toBeNull()
    wrapper.unmount()
  })

  it('A pending, B wins va response A khong duoc mo profile hoac draft', async () => {
    const accountA = taoDeferred()
    const accountB = taoDeferred()
    taiChiTietHuanLuyenVien.mockImplementation((id) => (id === 7 ? accountA.promise : accountB.promise))
    taiHoSoHuanLuyenVien.mockResolvedValue({ data: PROFILE_B })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/7')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienChiTiet, { global: { plugins: [router] } })
    await vi.waitFor(() => expect(taiChiTietHuanLuyenVien).toHaveBeenCalledWith(7))

    await router.push('/admin/huan-luyen-vien/8')
    accountB.resolve({ data: ACCOUNT_B })
    await vi.waitFor(() => expect(taiHoSoHuanLuyenVien).toHaveBeenCalledWith(8))
    await flushPromises()
    accountA.resolve({ data: ACCOUNT })
    await flushPromises()

    const hoSoStore = useHuanLuyenVienStore()
    expect(taiHoSoHuanLuyenVien).toHaveBeenCalledTimes(1)
    expect(taiHoSoHuanLuyenVien).not.toHaveBeenCalledWith(7)
    expect(router.currentRoute.value.params.id).toBe('8')
    expect(wrapper.text()).toContain('PT B')
    expect(wrapper.get('#trainer-profile-introduction').element.value).toBe('Intro B')
    expect(hoSoStore.hoSoDaChon.account_id).toBe(8)
    wrapper.unmount()
  })

  it('unmount khi POST profile pending chan GET tiep theo va moi state phuc hoi', async () => {
    const pendingPost = taoDeferred()
    onboardTaiKhoanHuanLuyenVien.mockReturnValueOnce(pendingPost.promise)
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/7')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienChiTiet, { global: { plugins: [router] } })
    await flushPromises()
    const hoSoStore = useHuanLuyenVienStore()

    await wrapper.get('form').trigger('submit')
    await vi.waitFor(() => expect(onboardTaiKhoanHuanLuyenVien).toHaveBeenCalledTimes(1))
    wrapper.unmount()
    pendingPost.resolve({ data: {} })
    await flushPromises()

    expect(onboardTaiKhoanHuanLuyenVien).toHaveBeenCalledTimes(1)
    expect(taiHoSoHuanLuyenVien).toHaveBeenCalledTimes(1)
    expect(taiChiTietHuanLuyenVien).toHaveBeenCalledTimes(1)
    expect(taiDanhSachHuanLuyenVien).not.toHaveBeenCalled()
    expect(hoSoStore.hoSoDaChon).toBeNull()
    expect(hoSoStore.dangTaiHoSo).toBe(false)
    expect(hoSoStore.dangCapNhatHoSo).toBe(false)
    expect(hoSoStore.khoaCapNhatHoSo).toBeNull()
    expect(hoSoStore.loiCapNhatHoSo).toBeNull()
    expect(hoSoStore.thongBaoCapNhatHoSo).toBeNull()
  })

  it('A POST xong GET authoritative pending khong duoc ghi de form B hoac refetch A', async () => {
    const pendingAuthoritativeGet = taoDeferred()
    taiChiTietHuanLuyenVien.mockReset()
    taiChiTietHuanLuyenVien
      .mockResolvedValueOnce({ data: ACCOUNT })
      .mockResolvedValueOnce({ data: ACCOUNT_B })
    taiHoSoHuanLuyenVien.mockReset()
    taiHoSoHuanLuyenVien
      .mockResolvedValueOnce({ data: PROFILE })
      .mockReturnValueOnce(pendingAuthoritativeGet.promise)
      .mockResolvedValueOnce({ data: PROFILE_B })
    onboardTaiKhoanHuanLuyenVien.mockResolvedValueOnce({ data: {} })
    const router = taoRouter()
    await router.push('/admin/huan-luyen-vien/7')
    await router.isReady()
    const wrapper = mount(HuanLuyenVienChiTiet, { global: { plugins: [router] } })
    await flushPromises()
    await wrapper.get('#trainer-profile-introduction').setValue('A submitted')
    await wrapper.get('form').trigger('submit')
    await vi.waitFor(() => expect(taiHoSoHuanLuyenVien).toHaveBeenCalledTimes(2))

    await router.push('/admin/huan-luyen-vien/8')
    await vi.waitFor(() => expect(taiHoSoHuanLuyenVien).toHaveBeenCalledWith(8))
    await flushPromises()
    pendingAuthoritativeGet.resolve({ data: { ...PROFILE, introduction: 'A late response' } })
    await flushPromises()

    expect(onboardTaiKhoanHuanLuyenVien).toHaveBeenCalledTimes(1)
    expect(onboardTaiKhoanHuanLuyenVien).toHaveBeenCalledWith(7, {
      introduction: 'A submitted', specialties: 'Strength', status: 'HOAT_DONG',
    }, expect.any(String))
    expect(taiChiTietHuanLuyenVien).toHaveBeenCalledTimes(2)
    expect(taiChiTietHuanLuyenVien.mock.calls.filter(([id]) => id === 7)).toHaveLength(1)
    expect(taiChiTietHuanLuyenVien).toHaveBeenNthCalledWith(2, 8)
    expect(taiDanhSachHuanLuyenVien).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('PT B')
    expect(wrapper.get('#trainer-profile-introduction').element.value).toBe('Intro B')
    expect(useHuanLuyenVienStore().hoSoDaChon.account_id).toBe(8)
    wrapper.unmount()
  })
})
