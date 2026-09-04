import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import HoiVien from './hoi_vien.index.vue'

const { taiDanhSachHoiVienApi } = vi.hoisted(() => ({
  taiDanhSachHoiVienApi: vi.fn(),
}))

vi.mock('../../../services/tai_khoan.api.js', () => ({
  taiDanhSachHoiVien: taiDanhSachHoiVienApi,
  taiChiTietHoiVien: vi.fn(),
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
  avatar: 'private-avatar-url',
  status: 'HOAT_DONG',
  roles: [{ assignment_id: 1, code: 'MEMBER', name: 'Hội viên', active: true }],
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
  member_profile: { id: 4, code: 'HV-004' },
  trainer_profile: { id: 10, code: 'PT-010', status: 'HOAT_DONG' },
  secret: 'khong-duoc-render',
}

function phanHoi(items = [HOI_VIEN], pagination = {}) {
  return {
    data: {
      items,
      pagination: {
        current_page: pagination.current_page ?? 1,
        per_page: pagination.per_page ?? 20,
        total: pagination.total ?? items.length,
        last_page: pagination.last_page ?? 1,
      },
    },
  }
}

function taoRouter(coRouteChiTiet = true) {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin/hoi-vien',
        name: 'adminHoiVien',
        component: HoiVien,
        meta: { duongDanPhanCap: [{ nhan: 'Hội viên' }] },
      },
      ...(coRouteChiTiet
        ? [{
          path: '/admin/hoi-vien/:id',
          name: 'adminChiTietHoiVien',
          component: { template: '<h1>Chi tiết Hội viên</h1>' },
        }]
        : []),
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang(ketQua = phanHoi(), coRouteChiTiet = true) {
  setActivePinia(createPinia())
  taiDanhSachHoiVienApi.mockResolvedValueOnce(ketQua)
  const router = taoRouter(coRouteChiTiet)
  await router.push({ name: 'adminHoiVien' })
  await router.isReady()
  const wrapper = mount(HoiVien, { global: { plugins: [router] } })
  await flushPromises()

  return { router, wrapper }
}

describe('hoi_vien.index FE1-T06', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render list Account-oriented, chi co search/status va khong lo profile khac', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('h1').text()).toBe('Danh sách Hội viên')
    expect(wrapper.get('h2').text()).toBe('Tìm kiếm và lọc')
    expect(wrapper.find('input[type="search"]').exists()).toBe(true)
    expect(wrapper.findAll('select')).toHaveLength(1)
    expect(wrapper.get('label[for="hoi-vien-trang-thai"]').text()).toContain('Trạng thái')
    expect(wrapper.get('table caption').text()).toBe('Danh sách Hội viên')
    expect(wrapper.findAll('thead th')).toHaveLength(8)
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.text()).toContain('HV-004')
    expect(wrapper.text()).toContain('Hoạt động')
    expect(wrapper.text()).not.toContain('PT-010')
    expect(wrapper.text()).not.toContain('private-avatar-url')
    expect(wrapper.text()).not.toContain('khong-duoc-render')
  })

  it('query ban dau dung scoped fields va service Member boundary', async () => {
    await mountTrang()

    expect(taiDanhSachHoiVienApi).toHaveBeenCalledWith({
      search: '', status: '', per_page: 20, page: 1,
    })
    expect(taiDanhSachHoiVienApi.mock.calls[0][0]).not.toHaveProperty('role')
    expect(taiDanhSachHoiVienApi.mock.calls[0][0]).not.toHaveProperty('branch_id')
    expect(taiDanhSachHoiVienApi.mock.calls[0][0]).not.toHaveProperty('membership')
  })

  it('submit search/status reset ve page 1 va khong cho chon role/branch', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi())

    await wrapper.get('input[type="search"]').setValue('  Anh  ')
    await wrapper.get('select').setValue('BI_KHOA')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(taiDanhSachHoiVienApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: 'BI_KHOA', per_page: 20, page: 1,
    })
    expect(wrapper.findAll('select')).toHaveLength(1)
    expect(wrapper.text()).not.toContain('Tất cả vai trò')
  })

  it('reset clears only Member draft and requests fixed-role default context', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi())

    await wrapper.get('input[type="search"]').setValue('Anh')
    await wrapper.get('select').setValue('PT')
    await wrapper.get('.bo-loc-danh-sach .nut--phu').trigger('click')
    await flushPromises()

    expect(wrapper.get('input[type="search"]').element.value).toBe('')
    expect(wrapper.get('select').element.value).toBe('')
    expect(taiDanhSachHoiVienApi).toHaveBeenLastCalledWith({
      search: '', status: '', per_page: 20, page: 1,
    })
  })

  it('pagination giu applied Member filter va metadata server', async () => {
    const { wrapper } = await mountTrang(phanHoi([HOI_VIEN], {
      total: 21,
      last_page: 2,
    }))
    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi([HOI_VIEN], {
      total: 21,
      last_page: 2,
    }))
    await wrapper.get('input[type="search"]').setValue('Anh')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi([HOI_VIEN], {
      current_page: 2,
      total: 21,
      last_page: 2,
    }))
    await wrapper.get('.thanh-phan-trang button:last-child').trigger('click')
    await flushPromises()

    expect(taiDanhSachHoiVienApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: '', per_page: 20, page: 2,
    })
    expect(wrapper.text()).toContain('Trang 2 / 2')
  })

  it('empty state phan biet list rong va filtered zero', async () => {
    const { wrapper } = await mountTrang(phanHoi([]))
    expect(wrapper.get('.bang-du-lieu [role="status"]').text()).toBe('Chưa có Hội viên.')

    await wrapper.get('input[type="search"]').setValue('chưa áp dụng')
    expect(wrapper.get('.bang-du-lieu [role="status"]').text()).toBe('Chưa có Hội viên.')

    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi([]))
    await wrapper.get('input[type="search"]').setValue('không có')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('.bang-du-lieu [role="status"]').text()).toBe('Không tìm thấy Hội viên phù hợp.')
  })

  it('422 hien field error khong tao retry mù va giu table truoc do', async () => {
    const { wrapper } = await mountTrang()
    document.body.appendChild(wrapper.element)
    taiDanhSachHoiVienApi.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { search: ['Từ khóa không hợp lệ.'] },
      isNetworkError: false,
    })
    await wrapper.get('input[type="search"]').setValue('???')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Từ khóa không hợp lệ.')
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
    expect(document.activeElement).toBe(wrapper.get('#hoi-vien-tim-kiem').element)
    wrapper.unmount()
  })

  it('5xx/network co retry thu cong va giu context', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachHoiVienApi.mockRejectedValueOnce({
      httpStatus: 503,
      message: 'Máy chủ đang gặp sự cố.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('Máy chủ đang gặp sự cố.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)

    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi())
    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(taiDanhSachHoiVienApi).toHaveBeenLastCalledWith({
      search: '', status: '', per_page: 20, page: 1,
    })
  })

  it('401 hien loi an toan, 403 clear Member scope va den trang quyen', async () => {
    const first = await mountTrang()
    taiDanhSachHoiVienApi.mockRejectedValueOnce({
      httpStatus: 401,
      message: 'Thông tin xác thực không hợp lệ.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await first.wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(first.router.currentRoute.value.name).toBe('adminHoiVien')
    expect(first.wrapper.text()).toContain('Thông tin xác thực không hợp lệ.')

    taiDanhSachHoiVienApi.mockRejectedValueOnce({
      httpStatus: 403,
      message: 'Bạn không có quyền thực hiện thao tác này.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await first.wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(first.router.currentRoute.value.name).toBe('khongCoQuyen')
  })

  it('named link chi hien khi route detail da dang ky', async () => {
    const { wrapper } = await mountTrang(phanHoi(), true)
    expect(wrapper.get('a[href="/admin/hoi-vien/7"]').text()).toBe('Xem chi tiết')

    const khongCoDetail = await mountTrang(phanHoi(), false)
    expect(khongCoDetail.wrapper.find('a[href="/admin/hoi-vien/7"]').exists()).toBe(false)
    expect(khongCoDetail.wrapper.findAll('thead th')).toHaveLength(7)
  })

  it('co loading accessible va khong co control mutation Account', async () => {
    let resolveRequest
    taiDanhSachHoiVienApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRequest = resolve
    }))
    setActivePinia(createPinia())
    const router = taoRouter()
    await router.push({ name: 'adminHoiVien' })
    await router.isReady()
    const wrapper = mount(HoiVien, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.get('.trang-thai-tai-du-lieu').text()).toContain('Đang tải danh sách Hội viên')
    expect(wrapper.get('section[aria-label="Danh sách Hội viên"]').attributes('aria-busy')).toBe('true')
    expect(wrapper.text()).not.toContain('Cấp vai trò')
    expect(wrapper.text()).not.toContain('Thu hồi vai trò')

    resolveRequest(phanHoi())
    await flushPromises()
  })
})
