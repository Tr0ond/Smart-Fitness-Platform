import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import TaiKhoan from './tai_khoan.index.vue'

const { taiDanhSachTaiKhoanApi } = vi.hoisted(() => ({
  taiDanhSachTaiKhoanApi: vi.fn(),
}))

vi.mock('../../../services/tai_khoan.api.js', () => ({
  taiDanhSachTaiKhoan: taiDanhSachTaiKhoanApi,
  CAC_TRANG_THAI_TAI_KHOAN: ['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'],
  CAC_VAI_TRO_TAI_KHOAN: ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'],
}))

const TAI_KHOAN = {
  id: 7,
  name: 'Nguyễn Minh Anh',
  email: 'anh@example.com',
  phone: '0900000000',
  avatar: 'private-avatar-url',
  status: 'HOAT_DONG',
  roles: [
    { assignment_id: 1, code: 'MEMBER', name: 'Hội viên', active: true },
    { assignment_id: 2, code: 'PT', name: 'Huấn luyện viên', active: false },
  ],
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
  member_profile: { id: 4, code: 'HV-004' },
  secret: 'khong-duoc-render',
}

function phanHoi(items = [TAI_KHOAN], pagination = {}) {
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

function taoRouter(coRouteChiTiet = false) {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin/tai-khoan',
        name: 'adminTaiKhoan',
        component: TaiKhoan,
        meta: { duongDanPhanCap: [{ nhan: 'Tài khoản' }] },
      },
      ...(coRouteChiTiet
        ? [{
          path: '/admin/tai-khoan/:id',
          name: 'adminChiTietTaiKhoan',
          component: { template: '<h1>Chi tiết</h1>' },
        }]
        : []),
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang(ketQua = phanHoi(), coRouteChiTiet = false) {
  setActivePinia(createPinia())
  taiDanhSachTaiKhoanApi.mockResolvedValueOnce(ketQua)
  const router = taoRouter(coRouteChiTiet)
  await router.push({ name: 'adminTaiKhoan' })
  await router.isReady()
  const wrapper = mount(TaiKhoan, {
    global: { plugins: [router] },
  })
  await flushPromises()

  return { router, wrapper }
}

describe('tai_khoan.index FE1-T03', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render title, filter controls, semantic columns and safe allow-list DTO', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('h1').text()).toBe('Danh sách tài khoản')
    expect(wrapper.get('h2').text()).toBe('Tìm kiếm và lọc')
    expect(wrapper.find('input[type="search"]').exists()).toBe(true)
    expect(wrapper.findAll('select')).toHaveLength(2)
    expect(wrapper.find('caption').text()).toBe('Danh sách tài khoản')
    expect(wrapper.findAll('thead th')).toHaveLength(7)
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.text()).toContain('Hội viên')
    expect(wrapper.text()).toContain('Huấn luyện viên · Đã thu hồi')
    expect(wrapper.text()).toContain('Hoạt động')
    expect(wrapper.text()).not.toContain('khong-duoc-render')
    expect(wrapper.text()).not.toContain('private-avatar-url')
  })

  it('initial query uses exact Backend endpoint params and no action/detail link', async () => {
    const { wrapper } = await mountTrang()

    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: '', status: '', role: '', per_page: 20, page: 1,
    })
    expect(wrapper.findAll('a')).toHaveLength(0)
    expect(wrapper.text()).not.toContain('Chi tiết')
    expect(wrapper.text()).not.toContain('Khóa tài khoản')
  })

  it('submit search/status/role resets to page 1 and keeps filters applied', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())

    await wrapper.get('input[type="search"]').setValue('  Anh  ')
    await wrapper.findAll('select')[0].setValue('BI_KHOA')
    await wrapper.findAll('select')[1].setValue('MEMBER')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(taiDanhSachTaiKhoanApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: 'BI_KHOA', role: 'MEMBER', per_page: 20, page: 1,
    })
  })

  it('reset clears draft filters and requests default page 1', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())

    await wrapper.get('input[type="search"]').setValue('Anh')
    await wrapper.findAll('select')[1].setValue('PT')
    await wrapper.get('.bo-loc-danh-sach .nut--phu').trigger('click')
    await flushPromises()

    expect(wrapper.get('input[type="search"]').element.value).toBe('')
    expect(wrapper.findAll('select')[0].element.value).toBe('')
    expect(wrapper.findAll('select')[1].element.value).toBe('')
    expect(taiDanhSachTaiKhoanApi).toHaveBeenLastCalledWith({
      search: '', status: '', role: '', per_page: 20, page: 1,
    })
  })

  it('pagination preserves applied filters and uses server page metadata', async () => {
    const { wrapper } = await mountTrang(phanHoi([TAI_KHOAN], {
      current_page: 1,
      total: 21,
      last_page: 2,
    }))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([], {
      current_page: 1,
      total: 21,
      last_page: 2,
    }))
    await wrapper.get('input[type="search"]').setValue('Anh')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([], {
      current_page: 2,
      total: 21,
      last_page: 2,
    }))
    await wrapper.get('.thanh-phan-trang button:last-child').trigger('click')
    await flushPromises()

    expect(taiDanhSachTaiKhoanApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: '', role: '', per_page: 20, page: 2,
    })
    expect(wrapper.text()).toContain('Trang 2 / 2')
  })

  it('shows initial loading without empty state before first response', async () => {
    let resolveRequest
    taiDanhSachTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRequest = resolve
    }))
    setActivePinia(createPinia())
    const router = taoRouter()
    await router.push({ name: 'adminTaiKhoan' })
    await router.isReady()
    const wrapper = mount(TaiKhoan, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.get('.trang-thai-tai-du-lieu').text()).toContain('Đang tải danh sách tài khoản')
    expect(wrapper.find('.bang-du-lieu').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Chưa có tài khoản nào để hiển thị.')

    resolveRequest(phanHoi())
    await flushPromises()
  })

  it('shows no-result empty state only after successful response', async () => {
    const { wrapper } = await mountTrang(phanHoi([]))

    expect(wrapper.get('.bang-du-lieu [role="status"]').text()).toBe('Chưa có tài khoản nào để hiển thị.')

    await wrapper.get('input[type="search"]').setValue('chưa áp dụng')
    expect(wrapper.get('.bang-du-lieu [role="status"]').text()).toBe('Chưa có tài khoản nào để hiển thị.')
  })

  it('hydrate draft tu applied filter cua Store khi remount', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useTaiKhoanStore(pinia)
    store.boLoc = { search: 'Anh', status: 'BI_KHOA', role: 'MEMBER', per_page: 20 }
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([]))
    const router = taoRouter()
    await router.push({ name: 'adminTaiKhoan' })
    await router.isReady()

    const wrapper = mount(TaiKhoan, { global: { plugins: [pinia, router] } })
    await flushPromises()

    expect(wrapper.get('#tai-khoan-tim-kiem').element.value).toBe('Anh')
    expect(wrapper.get('#tai-khoan-trang-thai').element.value).toBe('BI_KHOA')
    expect(wrapper.get('#tai-khoan-vai-tro').element.value).toBe('MEMBER')
    expect(taiDanhSachTaiKhoanApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: 'BI_KHOA', role: 'MEMBER', per_page: 20, page: 1,
    })
  })

  it('maps 422 field error, focus first invalid field and does not offer blind retry', async () => {
    const { wrapper } = await mountTrang()
    document.body.appendChild(wrapper.element)
    taiDanhSachTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { role: ['Vai trò không hợp lệ.'] },
      isNetworkError: false,
    })
    await wrapper.findAll('select')[1].setValue('MEMBER')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Dữ liệu gửi lên chưa hợp lệ.')
    expect(wrapper.text()).toContain('Vai trò không hợp lệ.')
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
    expect(document.activeElement).toBe(wrapper.get('#tai-khoan-vai-tro').element)
    wrapper.unmount()
  })

  it('maps 5xx/network error to safe retry and keeps query state', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 503,
      message: 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('Máy chủ đang gặp sự cố')

    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(taiDanhSachTaiKhoanApi).toHaveBeenLastCalledWith({
      search: '', status: '', role: '', per_page: 20, page: 1,
    })
  })

  it('403 redirects to permission page without logout behavior', async () => {
    taiDanhSachTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 403,
      message: 'Bạn không có quyền thực hiện thao tác này.',
      fieldErrors: {},
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push({ name: 'adminTaiKhoan' })
    await router.isReady()
    mount(TaiKhoan, { global: { plugins: [router] } })
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })

  it('401 is rendered as safe error and does not invent logout or redirect', async () => {
    const { router, wrapper } = await mountTrang()
    taiDanhSachTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 401,
      message: 'Thông tin xác thực không hợp lệ.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('adminTaiKhoan')
    expect(wrapper.text()).toContain('Thông tin xác thực không hợp lệ.')
  })

  it('responsive and accessibility hooks are present on form/table/pagination', async () => {
    const { wrapper } = await mountTrang(phanHoi([TAI_KHOAN], {
      current_page: 1,
      total: 21,
      last_page: 2,
    }))

    expect(wrapper.get('section[aria-label="Danh sách tài khoản"]').attributes('aria-busy')).toBe('false')
    expect(wrapper.get('form').attributes('aria-busy')).toBe('false')
    expect(wrapper.get('label[for="tai-khoan-tim-kiem"]').exists()).toBe(true)
    expect(wrapper.get('input[type="search"]').attributes('maxlength')).toBe('150')
    expect(wrapper.get('table caption').text()).toBe('Danh sách tài khoản')
    expect(wrapper.get('.thanh-phan-trang nav, nav.thanh-phan-trang').exists()).toBe(true)
  })

  it('chi hien named detail link khi route detail da duoc dang ky', async () => {
    const { wrapper } = await mountTrang(phanHoi(), true)

    expect(wrapper.findAll('thead th')).toHaveLength(8)
    const lienKet = wrapper.get('a[href="/admin/tai-khoan/7"]')
    expect(lienKet.text()).toBe('Xem chi tiết')
    expect(lienKet.attributes('href')).toBe('/admin/tai-khoan/7')
  })

  it('khong tao link detail hong khi router chua dang ky route', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.find('a[href="/admin/tai-khoan/7"]').exists()).toBe(false)
    expect(wrapper.findAll('thead th')).toHaveLength(7)
  })
})
