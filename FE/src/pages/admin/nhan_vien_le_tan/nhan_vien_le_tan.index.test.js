import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import NhanVienLeTan from './nhan_vien_le_tan.index.vue'

const { taiDanhSachNhanVienLeTanApi } = vi.hoisted(() => ({
  taiDanhSachNhanVienLeTanApi: vi.fn(),
}))

vi.mock('../../../services/tai_khoan.api.js', () => ({
  taiDanhSachNhanVienLeTan: taiDanhSachNhanVienLeTanApi,
  taiChiTietNhanVienLeTan: vi.fn(),
  taiDanhSachHoiVien: vi.fn(),
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

const NHAN_VIEN_LE_TAN = {
  id: 7,
  name: 'Nguyễn Minh Anh',
  email: 'anh@example.com',
  phone: '0900000000',
  status: 'HOAT_DONG',
  roles: [{ assignment_id: 1, code: 'RECEPTIONIST', name: 'Nhân viên lễ tân', active: true }],
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
  trainer_profile: { id: 10, code: 'PT-010', status: 'HOAT_DONG' },
  secret: 'khong-duoc-render',
}

function phanHoi(items = [NHAN_VIEN_LE_TAN], pagination = {}) {
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

async function mountTrang(phanHoiDanhSach = phanHoi()) {
  setActivePinia(createPinia())
  taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoiDanhSach)
  const router = taoRouter()
  await router.push({ name: 'adminNhanVienLeTan' })
  await router.isReady()
  const wrapper = mount(NhanVienLeTan, { global: { plugins: [router] } })
  await flushPromises()
  return { router, wrapper }
}

describe('nhan_vien_le_tan.index FE1-T07', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render list Account Receptionist, fixed role, no profile/role selector', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('h1').text()).toBe('Danh sách Nhân viên lễ tân')
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.text()).toContain('anh@example.com')
    expect(wrapper.text()).toContain('Nhân viên lễ tân')
    expect(wrapper.text()).toContain('Chi nhánh Hà Nội')
    expect(wrapper.text()).not.toContain('PT-010')
    expect(wrapper.text()).not.toContain('khong-duoc-render')
    expect(wrapper.findAll('select')).toHaveLength(1)
    expect(wrapper.find('a[href="/admin/nhan-vien-le-tan/7"]').exists()).toBe(true)
    expect(taiDanhSachNhanVienLeTanApi).toHaveBeenCalledWith({
      search: '', status: '', per_page: 20, page: 1,
    })
  })

  it('phan biet empty list va empty result theo filter', async () => {
    const { wrapper } = await mountTrang(phanHoi([]))
    expect(wrapper.text()).toContain('Chưa có Nhân viên lễ tân')

    await wrapper.get('input[type="search"]').setValue('chưa áp dụng')
    expect(wrapper.text()).toContain('Chưa có Nhân viên lễ tân')
    expect(wrapper.text()).not.toContain('Không tìm thấy Nhân viên lễ tân phù hợp')

    await wrapper.get('input[type="search"]').setValue('Anh')
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoi([]))
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('Không tìm thấy Nhân viên lễ tân phù hợp')
  })

  it('422 focus field dau va khong hien retry cho query validation', async () => {
    const { wrapper } = await mountTrang()
    document.body.appendChild(wrapper.element)
    taiDanhSachNhanVienLeTanApi.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { search: ['Từ khóa không hợp lệ.'] },
      isNetworkError: false,
    })

    await wrapper.get('input[type="search"]').setValue('???')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Từ khóa không hợp lệ.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
    expect(document.activeElement).toBe(wrapper.get('#nhan-vien-le-tan-tim-kiem').element)
    wrapper.unmount()
  })

  it('403 clear scope va redirect, 5xx co retry thu cong', async () => {
    setActivePinia(createPinia())
    taiDanhSachNhanVienLeTanApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ lỗi.' })
    const router = taoRouter()
    await router.push({ name: 'adminNhanVienLeTan' })
    await router.isReady()
    const wrapper = mount(NhanVienLeTan, { global: { plugins: [router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Máy chủ lỗi.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)

    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoi())
    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')

    taiDanhSachNhanVienLeTanApi.mockRejectedValueOnce({ httpStatus: 403, message: 'Không có quyền.' })
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })
})
