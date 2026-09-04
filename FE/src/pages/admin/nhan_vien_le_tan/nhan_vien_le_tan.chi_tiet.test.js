import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import NhanVienLeTanChiTiet from './nhan_vien_le_tan.chi_tiet.vue'

const {
  taiChiTietNhanVienLeTanApi,
  taiDanhSachNhanVienLeTanApi,
  capNhatTrangThaiTaiKhoanApi,
  thuHoiVaiTroApi,
} = vi.hoisted(() => ({
  taiChiTietNhanVienLeTanApi: vi.fn(),
  taiDanhSachNhanVienLeTanApi: vi.fn(),
  capNhatTrangThaiTaiKhoanApi: vi.fn(),
  thuHoiVaiTroApi: vi.fn(),
}))

vi.mock('../../../services/tai_khoan.api.js', () => ({
  taiChiTietNhanVienLeTan: taiChiTietNhanVienLeTanApi,
  taiDanhSachNhanVienLeTan: taiDanhSachNhanVienLeTanApi,
  taiDanhSachHoiVien: vi.fn(),
  taiChiTietHoiVien: vi.fn(),
  taiDanhSachTaiKhoan: vi.fn(),
  taiChiTietTaiKhoan: vi.fn(),
  capVaiTro: vi.fn(),
  capNhatTrangThaiTaiKhoan: capNhatTrangThaiTaiKhoanApi,
  thuHoiVaiTro: thuHoiVaiTroApi,
  laIdTaiKhoanHopLe: (id) => (typeof id === 'number'
    ? Number.isSafeInteger(id) && id > 0
    : typeof id === 'string' && /^[1-9]\d*$/.test(id.trim())),
  CAC_TRANG_THAI_TAI_KHOAN: ['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'],
  CAC_VAI_TRO_TAI_KHOAN: ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'],
  CAC_CHUYEN_DOI_VAI_TRO: ['GRANTED', 'REGRANTED', 'REVOKED', 'UNCHANGED'],
}))

const NHAN_VIEN_LE_TAN = {
  id: 7,
  name: 'Nguyễn Minh Anh',
  email: 'anh@example.com',
  phone: '0900000000',
  status: 'HOAT_DONG',
  roles: [{ assignment_id: 1, code: 'RECEPTIONIST', name: 'Nhân viên lễ tân', active: true }],
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
  email_verified_at: '2026-08-01T08:00:00.000000Z',
  last_login_at: '2026-09-01T08:00:00.000000Z',
  created_at: '2026-07-01T08:00:00.000000Z',
  updated_at: '2026-09-01T08:00:00.000000Z',
  trainer_profile: { id: 10, code: 'PT-010', status: 'HOAT_DONG' },
  secret: 'khong-duoc-render',
}

function phanHoiChiTiet(taiKhoan = NHAN_VIEN_LE_TAN) {
  return { data: taiKhoan }
}

function phanHoiDanhSach(items = [NHAN_VIEN_LE_TAN]) {
  return {
    data: {
      items,
      pagination: { current_page: 1, per_page: 20, total: items.length, last_page: 1 },
    },
  }
}

function phanHoiVaiTro() {
  return {
    data: {
      assignment_id: 1,
      account_id: 7,
      role: 'RECEPTIONIST',
      active: false,
      changed: true,
      transition: 'REVOKED',
    },
  }
}

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin/nhan-vien-le-tan',
        name: 'adminNhanVienLeTan',
        component: { template: '<h1>Danh sách Nhân viên lễ tân</h1>' },
      },
      {
        path: '/admin/nhan-vien-le-tan/:id',
        name: 'adminChiTietNhanVienLeTan',
        component: { template: '<h1>Chi tiết Nhân viên lễ tân</h1>' },
      },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang(ketQua = phanHoiChiTiet()) {
  setActivePinia(createPinia())
  taiChiTietNhanVienLeTanApi.mockResolvedValueOnce(ketQua)
  const router = taoRouter()
  await router.push({ name: 'adminChiTietNhanVienLeTan', params: { id: '7' } })
  await router.isReady()
  const wrapper = mount(NhanVienLeTanChiTiet, { global: { plugins: [router] } })
  await flushPromises()
  return { router, wrapper }
}

describe('nhan_vien_le_tan.chi_tiet FE1-T07', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render Account fields and only active Receptionist role action', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('h1').text()).toBe('Chi tiết Nhân viên lễ tân')
    expect(wrapper.get('h2').text()).toBe('Nguyễn Minh Anh')
    expect(wrapper.text()).toContain('anh@example.com')
    expect(wrapper.text()).toContain('0900000000')
    expect(wrapper.text()).toContain('Chi nhánh Hà Nội')
    expect(wrapper.text()).toContain('Nhân viên lễ tân · Đang hoạt động')
    expect(wrapper.text()).not.toContain('PT-010')
    expect(wrapper.text()).not.toContain('khong-duoc-render')
    expect(wrapper.findAll('select')).toHaveLength(1)
    expect(wrapper.findAll('button').filter((button) => button.text().includes('Cấp')).length).toBe(0)
    expect(wrapper.findAll('button').filter((button) => button.text().includes('Cấp lại')).length).toBe(0)
  })

  it('status confirmation then mutation uses generic Account status contract and refetches', async () => {
    const { wrapper } = await mountTrang()
    await wrapper.get('select').setValue('BI_KHOA')
    await wrapper.get('form').trigger('submit')

    expect(wrapper.text()).toContain('Chuyển trạng thái tài khoản này sang “Bị khóa”')
    capNhatTrangThaiTaiKhoanApi.mockResolvedValueOnce({ data: { id: 7, status: 'BI_KHOA' } })
    taiChiTietNhanVienLeTanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, status: 'BI_KHOA' }))
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoiDanhSach([{ ...NHAN_VIEN_LE_TAN, status: 'BI_KHOA' }]))
    await wrapper.findAll('.hop-thoai-xac-nhan button').at(-1).trigger('click')
    await flushPromises()

    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledWith(7, 'BI_KHOA')
    expect(wrapper.text()).toContain('Đã cập nhật trạng thái tài khoản.')
  })

  it('status 422 giu detail, focus select loi va khong retry mutation', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { status: ['Trạng thái không hợp lệ.'] },
      isNetworkError: false,
    })
    const { wrapper } = await mountTrang()
    document.body.appendChild(wrapper.element)
    await wrapper.get('select').setValue('BI_KHOA')
    await wrapper.get('form').trigger('submit')
    await wrapper.findAll('.hop-thoai-xac-nhan button').at(-1).trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Trạng thái không hợp lệ.')
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(document.activeElement).toBe(wrapper.get('#nhan-vien-le-tan-chi-tiet-trang-thai').element)
    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledTimes(1)
    expect(taiChiTietNhanVienLeTanApi).toHaveBeenCalledTimes(1)
    wrapper.unmount()
  })

  it('revoke confirmation exact, DELETE fixed role and navigate after scope exit', async () => {
    const { router, wrapper } = await mountTrang()
    await wrapper.findAll('button').find((button) => button.text() === 'Thu hồi vai trò Nhân viên lễ tân').trigger('click')
    expect(wrapper.text()).toContain('Thu hồi vai trò Nhân viên lễ tân?')

    thuHoiVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro())
    taiChiTietNhanVienLeTanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, roles: [] }))
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoiDanhSach([]))
    await wrapper.findAll('.hop-thoai-xac-nhan button').at(-1).trigger('click')
    await flushPromises()

    expect(thuHoiVaiTroApi).toHaveBeenCalledWith(7, 'RECEPTIONIST')
    expect(router.currentRoute.value.name).toBe('adminNhanVienLeTan')
  })

  it('orientation invalid and 404 show generic unavailable without retry', async () => {
    const { wrapper } = await mountTrang(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, roles: [] }))
    expect(wrapper.text()).toContain('Không thể truy cập dữ liệu này.')
    expect(wrapper.text()).not.toContain('7')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
  })

  it('422 khi tai detail hien loi nhung khong cho retry request khong hop le', async () => {
    const { wrapper } = await mountTrang(Promise.reject({
      httpStatus: 422,
      message: 'Dữ liệu định danh chưa hợp lệ.',
      fieldErrors: { id: ['Định danh không hợp lệ.'] },
      isNetworkError: false,
    }))

    expect(wrapper.text()).toContain('Dữ liệu định danh chưa hợp lệ.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
  })
})
