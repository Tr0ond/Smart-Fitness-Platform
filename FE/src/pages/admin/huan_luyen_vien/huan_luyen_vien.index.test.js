import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import HuanLuyenVien from './huan_luyen_vien.index.vue'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'

const { taiDanhSachHuanLuyenVienApi } = vi.hoisted(() => ({
  taiDanhSachHuanLuyenVienApi: vi.fn(),
}))

vi.mock('../../../services/tai_khoan.api.js', () => ({
  taiDanhSachHuanLuyenVien: taiDanhSachHuanLuyenVienApi,
  taiDanhSachHoiVien: vi.fn(),
  taiDanhSachNhanVienLeTan: vi.fn(),
  taiDanhSachTaiKhoan: vi.fn(),
  taiChiTietHuanLuyenVien: vi.fn(),
  taiChiTietHoiVien: vi.fn(),
  taiChiTietNhanVienLeTan: vi.fn(),
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

const HUAN_LUYEN_VIEN = {
  id: 7,
  name: 'Nguyễn Minh Anh',
  email: 'anh@example.com',
  phone: '0900000000',
  status: 'HOAT_DONG',
  roles: [{ assignment_id: 1, code: 'PT', name: 'Huấn luyện viên', active: true }],
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
  trainer_profile: { id: 10, code: 'PT-010', status: 'HOAT_DONG' },
  membership: { status: 'DANG_HOAT_DONG' },
  assignment: { id: 20, member_id: 99 },
  invitation: 'QUEUED',
  secret: 'khong-duoc-render',
}

function phanHoi(items = [HUAN_LUYEN_VIEN], pagination = {}) {
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
        path: '/admin/huan-luyen-vien',
        name: 'adminHuanLuyenVien',
        component: HuanLuyenVien,
      },
      ...(coRouteChiTiet
        ? [{
          path: '/admin/huan-luyen-vien/:id',
          name: 'adminChiTietHuanLuyenVien',
          component: { template: '<h1>Chi tiết Huấn luyện viên</h1>' },
        }]
        : []),
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang(
  ketQua = phanHoi(),
  coRouteChiTiet = false,
  pinia = createPinia(),
) {
  setActivePinia(pinia)
  taiDanhSachHuanLuyenVienApi.mockResolvedValueOnce(ketQua)
  const router = taoRouter(coRouteChiTiet)
  await router.push({ name: 'adminHuanLuyenVien' })
  await router.isReady()
  const wrapper = mount(HuanLuyenVien, { global: { plugins: [router] } })
  await flushPromises()

  return { router, wrapper, store: useTaiKhoanStore(pinia) }
}

describe('huan_luyen_vien.index FE2-T01', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render Account PT an toan, profile null/fallback va khong co control ngoai scope', async () => {
    const ptKhongHoSo = { ...HUAN_LUYEN_VIEN, id: 8, trainer_profile: null, phone: '' }
    const ptTrangThaiLa = {
      ...HUAN_LUYEN_VIEN,
      id: 9,
      trainer_profile: { id: 12, code: 'PT-012', status: 'STATUS_MOI' },
      branch: null,
    }
    const { wrapper } = await mountTrang(phanHoi([HUAN_LUYEN_VIEN, ptKhongHoSo, ptTrangThaiLa]))

    expect(wrapper.get('h1').text()).toBe('Danh sách Huấn luyện viên')
    expect(wrapper.get('h2').text()).toBe('Tìm kiếm và lọc')
    expect(wrapper.find('input[type="search"]').exists()).toBe(true)
    expect(wrapper.findAll('select')).toHaveLength(1)
    expect(wrapper.get('label[for="huan-luyen-vien-trang-thai"]').text()).toContain('Trạng thái')
    expect(wrapper.get('table caption').text()).toBe('Danh sách Huấn luyện viên')
    expect(wrapper.findAll('thead th')).toHaveLength(8)
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.text()).toContain('PT-010')
    expect(wrapper.text()).toContain('Đang nhận phân công')
    expect(wrapper.text()).toContain('Chưa có hồ sơ PT')
    expect(wrapper.text()).toContain('Chưa cập nhật')
    expect(wrapper.text()).toContain('Chưa gán')
    expect(wrapper.text()).toContain('Không xác định')
    expect(wrapper.text()).not.toContain('STATUS_MOI')
    expect(wrapper.text()).not.toContain('DANG_HOAT_DONG')
    expect(wrapper.text()).not.toContain('QUEUED')
    expect(wrapper.text()).not.toContain('khong-duoc-render')
    expect(wrapper.findAll('button').map((nut) => nut.text())).not.toContain('Phân công PT')
    expect(wrapper.findAll('button').map((nut) => nut.text())).not.toContain('Mời PT')
    expect(wrapper.find('.bang-du-lieu__cuon').exists()).toBe(true)
  })

  it('query ban dau chi dung filter scoped, khong co role/branch authority tu page', async () => {
    await mountTrang()

    expect(taiDanhSachHuanLuyenVienApi).toHaveBeenCalledWith({
      search: '', status: '', per_page: 20, page: 1,
    })
    expect(taiDanhSachHuanLuyenVienApi.mock.calls[0][0]).not.toHaveProperty('role')
    expect(taiDanhSachHuanLuyenVienApi.mock.calls[0][0]).not.toHaveProperty('branch_id')
    expect(taiDanhSachHuanLuyenVienApi.mock.calls[0][0]).not.toHaveProperty('authority')
  })

  it('hydrate draft tu applied filter va submit giu query server', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const store = useTaiKhoanStore(pinia)
    store.boLocHuanLuyenVien = { search: 'Anh', status: 'HOAT_DONG', per_page: 20 }
    const { wrapper } = await mountTrang(phanHoi(), false, pinia)

    expect(wrapper.get('input[type="search"]').element.value).toBe('Anh')
    expect(wrapper.get('select').element.value).toBe('HOAT_DONG')

    taiDanhSachHuanLuyenVienApi.mockResolvedValueOnce(phanHoi())
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(taiDanhSachHuanLuyenVienApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: 'HOAT_DONG', per_page: 20, page: 1,
    })
  })

  it('submit filter reset page va pagination giu applied filter', async () => {
    const { wrapper } = await mountTrang(phanHoi([HUAN_LUYEN_VIEN], {
      total: 21,
      last_page: 2,
    }))
    taiDanhSachHuanLuyenVienApi.mockResolvedValueOnce(phanHoi([HUAN_LUYEN_VIEN], {
      total: 21,
      last_page: 2,
    }))

    await wrapper.get('input[type="search"]').setValue('  Anh  ')
    await wrapper.get('select').setValue('BI_KHOA')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    taiDanhSachHuanLuyenVienApi.mockResolvedValueOnce(phanHoi([HUAN_LUYEN_VIEN], {
      current_page: 2,
      total: 21,
      last_page: 2,
    }))
    await wrapper.get('.thanh-phan-trang button:last-child').trigger('click')
    await flushPromises()

    expect(taiDanhSachHuanLuyenVienApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: 'BI_KHOA', per_page: 20, page: 2,
    })
    expect(wrapper.text()).toContain('Trang 2 / 2')
  })

  it('empty state phan biet list rong va ket qua rong theo applied filter', async () => {
    const { wrapper } = await mountTrang(phanHoi([]))
    expect(wrapper.get('.bang-du-lieu [role="status"]').text()).toBe('Chưa có Huấn luyện viên.')

    await wrapper.get('input[type="search"]').setValue('chưa áp dụng')
    expect(wrapper.get('.bang-du-lieu [role="status"]').text()).toBe('Chưa có Huấn luyện viên.')

    taiDanhSachHuanLuyenVienApi.mockResolvedValueOnce(phanHoi([]))
    await wrapper.get('input[type="search"]').setValue('không có')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('.bang-du-lieu [role="status"]').text())
      .toBe('Không tìm thấy Huấn luyện viên phù hợp.')
  })

  it('422 focus field dau, khong retry va giu rows tot truoc do', async () => {
    const { wrapper } = await mountTrang()
    document.body.appendChild(wrapper.element)
    taiDanhSachHuanLuyenVienApi.mockRejectedValueOnce({
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
    expect(document.activeElement).toBe(wrapper.get('#huan-luyen-vien-tim-kiem').element)
    wrapper.unmount()
  })

  it('5xx co retry thu cong va giu applied filter/page', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachHuanLuyenVienApi.mockRejectedValueOnce({
      httpStatus: 503,
      message: 'Máy chủ đang gặp sự cố.',
      fieldErrors: {},
      isNetworkError: false,
    })

    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('Máy chủ đang gặp sự cố.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)

    taiDanhSachHuanLuyenVienApi.mockResolvedValueOnce(phanHoi())
    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(taiDanhSachHuanLuyenVienApi).toHaveBeenLastCalledWith({
      search: '', status: '', per_page: 20, page: 1,
    })
  })

  it('controlled response-shape error co retry nhung khong render payload raw', async () => {
    const { wrapper } = await mountTrang()
    taiDanhSachHuanLuyenVienApi.mockResolvedValueOnce({ data: { items: [], pagination: {} } })

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Không thể tải danh sách Huấn luyện viên. Vui lòng thử lại sau.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('pagination')
  })

  it('403 clear PT scope va redirect named khongCoQuyen, 401 khong page-logout', async () => {
    const first = await mountTrang()
    taiDanhSachHuanLuyenVienApi.mockRejectedValueOnce({
      httpStatus: 401,
      message: 'Thông tin xác thực không hợp lệ.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await first.wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(first.router.currentRoute.value.name).toBe('adminHuanLuyenVien')
    expect(first.wrapper.text()).toContain('Thông tin xác thực không hợp lệ.')
    expect(first.store.danhSachHuanLuyenVien).toEqual([HUAN_LUYEN_VIEN])

    taiDanhSachHuanLuyenVienApi.mockRejectedValueOnce({
      httpStatus: 403,
      message: 'Bạn không có quyền thực hiện thao tác này.',
      fieldErrors: {},
      isNetworkError: false,
    })
    await first.wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(first.router.currentRoute.value.name).toBe('khongCoQuyen')
    expect(first.store.danhSachHuanLuyenVien).toEqual([])
    expect(first.store.boLocHuanLuyenVien).toEqual({ search: '', status: '', per_page: 20 })
  })

  it('loading co aria state va khong co mutation/assignment controls', async () => {
    let resolveRequest
    taiDanhSachHuanLuyenVienApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRequest = resolve
    }))
    setActivePinia(createPinia())
    const router = taoRouter()
    await router.push({ name: 'adminHuanLuyenVien' })
    await router.isReady()
    const wrapper = mount(HuanLuyenVien, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.get('.trang-thai-tai-du-lieu').text())
      .toContain('Đang tải danh sách Huấn luyện viên')
    expect(wrapper.get('section[aria-label="Danh sách Huấn luyện viên"]')
      .attributes('aria-busy')).toBe('true')
    expect(wrapper.findAll('button').map((nut) => nut.text())).not.toContain('Cấp vai trò')
    expect(wrapper.findAll('button').map((nut) => nut.text())).not.toContain('Thu hồi vai trò')

    resolveRequest(phanHoi())
    await flushPromises()
  })

  it('chi hien named detail link khi route detail ton tai', async () => {
    const coDetail = await mountTrang(phanHoi(), true)
    expect(coDetail.wrapper.get('a[href="/admin/huan-luyen-vien/7"]').text()).toBe('Xem chi tiết')
    expect(coDetail.wrapper.findAll('thead th')).toHaveLength(9)

    const khongCoDetail = await mountTrang(phanHoi(), false)
    expect(khongCoDetail.wrapper.find('a[href="/admin/huan-luyen-vien/7"]').exists()).toBe(false)
    expect(khongCoDetail.wrapper.findAll('thead th')).toHaveLength(8)
  })
})
