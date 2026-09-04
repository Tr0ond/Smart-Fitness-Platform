import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import HoiVienChiTiet from './hoi_vien.chi_tiet.vue'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'

const { taiChiTietHoiVienApi } = vi.hoisted(() => ({
  taiChiTietHoiVienApi: vi.fn(),
}))

vi.mock('../../../services/tai_khoan.api.js', () => ({
  taiDanhSachHoiVien: vi.fn(),
  taiChiTietHoiVien: taiChiTietHoiVienApi,
  taiDanhSachTaiKhoan: vi.fn(),
  taiChiTietTaiKhoan: vi.fn(),
  capVaiTro: vi.fn(),
  capNhatTrangThaiTaiKhoan: vi.fn(),
  thuHoiVaiTro: vi.fn(),
  laIdTaiKhoanHopLe: (id) => (typeof id === 'number'
    ? Number.isSafeInteger(id) && id > 0
    : typeof id === 'string' && /^[1-9]\d*$/.test(id.trim())),
  CAC_TRANG_THAI_TAI_KHOAN: ['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'],
  CAC_VAI_TRO_TAI_KHOAN: ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'],
  CAC_CHUYEN_DOI_VAI_TRO: ['GRANTED', 'REGRANTED', 'REVOKED', 'UNCHANGED'],
}))

const HOI_VIEN = {
  id: 7,
  name: 'Nguyễn Minh Anh',
  email: 'anh@example.com',
  phone: '0900000000',
  status: 'HOAT_DONG',
  roles: [{ assignment_id: 1, code: 'MEMBER', name: 'Hội viên', active: true }],
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
  member_profile: { id: 4, code: 'HV-004' },
  email_verified_at: '2026-08-01T08:00:00.000000Z',
  last_login_at: '2026-09-01T08:00:00.000000Z',
  created_at: '2026-07-01T08:00:00.000000Z',
  updated_at: '2026-09-01T08:00:00.000000Z',
  trainer_profile: { id: 10, code: 'PT-010', status: 'HOAT_DONG' },
  secret: 'khong-duoc-render',
}

function phanHoi(taiKhoan = HOI_VIEN) {
  return { data: taiKhoan }
}

function taoRouter(id = '7') {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin/hoi-vien',
        name: 'adminHoiVien',
        component: { template: '<h1>Danh sách Hội viên</h1>' },
      },
      {
        path: '/admin/hoi-vien/:id',
        name: 'adminChiTietHoiVien',
        component: { template: '<h1>Chi tiết Hội viên</h1>' },
      },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang(ketQua = phanHoi(), id = '7') {
  setActivePinia(createPinia())
  taiChiTietHoiVienApi.mockResolvedValueOnce(ketQua)
  const router = taoRouter(id)
  await router.push({ name: 'adminChiTietHoiVien', params: { id } })
  await router.isReady()
  const wrapper = mount(HoiVienChiTiet, { global: { plugins: [router] } })
  await flushPromises()

  return { router, wrapper, store: useTaiKhoanStore() }
}

describe('hoi_vien.chi_tiet FE1-T06', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render basic Account/member fields, role MEMBER va read-only boundary', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('h1').text()).toBe('Chi tiết Hội viên')
    expect(wrapper.get('h2').text()).toBe('Nguyễn Minh Anh')
    expect(wrapper.text()).toContain('anh@example.com')
    expect(wrapper.text()).toContain('0900000000')
    expect(wrapper.text()).toContain('HV-004')
    expect(wrapper.text()).toContain('Hội viên')
    expect(wrapper.text()).toContain('Hoạt động')
    expect(wrapper.text()).toContain('Quay lại danh sách Hội viên')
    expect(wrapper.findAll('button')).toHaveLength(0)
    expect(wrapper.text()).not.toContain('PT-010')
    expect(wrapper.text()).not.toContain('khong-duoc-render')
    expect(wrapper.text()).not.toContain('Membership')
    expect(wrapper.find('a[href="/admin/hoi-vien"]').exists()).toBe(true)
  })

  it('non-MEMBER detail khong duoc render nhu Hoi vien va hien unavailable generic', async () => {
    const { wrapper, store } = await mountTrang(phanHoi({
      ...HOI_VIEN,
      roles: [{ assignment_id: 3, code: 'PT', name: 'Huấn luyện viên', active: true }],
    }))

    expect(store.hoiVienDaChon).toBeNull()
    expect(wrapper.text()).toContain('Không thể truy cập dữ liệu này.')
    expect(wrapper.text()).not.toContain('Nguyễn Minh Anh')
    expect(wrapper.text()).not.toContain('HV-004')
    expect(wrapper.text()).not.toContain('7')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
  })

  it('member profile null hien empty profile an toan, khong goi API bo sung', async () => {
    const { wrapper } = await mountTrang(phanHoi({ ...HOI_VIEN, member_profile: null }))

    expect(wrapper.text()).toContain('Chưa có thông tin hồ sơ Hội viên.')
    expect(taiChiTietHoiVienApi).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).not.toContain('Gói tập')
  })

  it('404 hoac orientation invalid khong retry va khong lo raw route id', async () => {
    const { wrapper } = await mountTrang({
      httpStatus: 404,
      code: 'ACCOUNT_NOT_FOUND',
      message: 'Không tìm thấy tài khoản 7.',
      fieldErrors: {},
      isNetworkError: false,
    })

    expect(wrapper.text()).toContain('Không thể truy cập dữ liệu này.')
    expect(wrapper.text()).not.toContain('Không tìm thấy tài khoản 7.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
  })

  it('5xx/network co retry thu cong voi cung id va giu detail cu neu co', async () => {
    const { wrapper, store } = await mountTrang()
    taiChiTietHoiVienApi.mockRejectedValueOnce({
      httpStatus: 503,
      message: 'Máy chủ đang gặp sự cố.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await store.taiChiTietHoiVien(7)
    await flushPromises()

    expect(wrapper.text()).toContain('Máy chủ đang gặp sự cố.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)
    taiChiTietHoiVienApi.mockResolvedValueOnce(phanHoi())
    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()
    expect(taiChiTietHoiVienApi).toHaveBeenLastCalledWith(7)
  })

  it('401 hien loi normalized ma khong tu logout, 403 clear scope va redirect', async () => {
    const first = await mountTrang()
    taiChiTietHoiVienApi.mockRejectedValueOnce({
      httpStatus: 401,
      message: 'Thông tin xác thực không hợp lệ.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await first.store.taiChiTietHoiVien(7)
    await flushPromises()
    expect(first.router.currentRoute.value.name).toBe('adminChiTietHoiVien')
    expect(first.wrapper.text()).toContain('Thông tin xác thực không hợp lệ.')

    taiChiTietHoiVienApi.mockRejectedValueOnce({
      httpStatus: 403,
      message: 'Bạn không có quyền thực hiện thao tác này.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await first.router.push({ name: 'adminChiTietHoiVien', params: { id: '8' } })
    await flushPromises()
    expect(first.router.currentRoute.value.name).toBe('khongCoQuyen')
    expect(first.store.hoiVienDaChon).toBeNull()
  })

  it('route detail race A-B chi giu response B', async () => {
    let resolveA
    let resolveB
    taiChiTietHoiVienApi
      .mockImplementationOnce(() => new Promise((resolve) => { resolveA = resolve }))
      .mockImplementationOnce(() => new Promise((resolve) => { resolveB = resolve }))
    setActivePinia(createPinia())
    const router = taoRouter()
    await router.push({ name: 'adminChiTietHoiVien', params: { id: '7' } })
    await router.isReady()
    const wrapper = mount(HoiVienChiTiet, { global: { plugins: [router] } })
    await flushPromises()
    await router.push({ name: 'adminChiTietHoiVien', params: { id: '8' } })
    resolveB(phanHoi({ ...HOI_VIEN, id: 8, name: 'Hội viên B' }))
    await flushPromises()
    resolveA(phanHoi({ ...HOI_VIEN, id: 7, name: 'Hội viên A' }))
    await flushPromises()

    expect(wrapper.text()).toContain('Hội viên B')
    expect(wrapper.text()).not.toContain('Hội viên A')
  })

  it('loading state co accessible label va khong hien detail truoc response', async () => {
    let resolveRequest
    taiChiTietHoiVienApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRequest = resolve
    }))
    setActivePinia(createPinia())
    const router = taoRouter()
    await router.push({ name: 'adminChiTietHoiVien', params: { id: '7' } })
    await router.isReady()
    const wrapper = mount(HoiVienChiTiet, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.get('.trang-thai-tai-du-lieu').text()).toContain('Đang tải chi tiết Hội viên')
    expect(wrapper.get('section[aria-label="Chi tiết Hội viên"]').attributes('aria-busy')).toBe('true')
    resolveRequest(phanHoi())
    await flushPromises()
  })
})
