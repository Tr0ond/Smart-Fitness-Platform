import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import TaiKhoanChiTiet from './tai_khoan.chi_tiet.vue'

const {
  taiChiTietTaiKhoanApi,
  capNhatTrangThaiTaiKhoanApi,
  taiDanhSachTaiKhoanApi,
  capVaiTroApi,
  thuHoiVaiTroApi,
} = vi.hoisted(() => ({
  taiChiTietTaiKhoanApi: vi.fn(),
  capNhatTrangThaiTaiKhoanApi: vi.fn(),
  taiDanhSachTaiKhoanApi: vi.fn(),
  capVaiTroApi: vi.fn(),
  thuHoiVaiTroApi: vi.fn(),
}))

vi.mock('../../../services/tai_khoan.api.js', () => ({
  CAC_TRANG_THAI_TAI_KHOAN: ['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'],
  CAC_VAI_TRO_TAI_KHOAN: ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'],
  CAC_CHUYEN_DOI_VAI_TRO: ['GRANTED', 'REGRANTED', 'REVOKED', 'UNCHANGED'],
  laIdTaiKhoanHopLe: (id) => (typeof id === 'number'
    ? Number.isSafeInteger(id) && id > 0
    : typeof id === 'string' && /^[1-9]\d*$/.test(id.trim())),
  taiChiTietTaiKhoan: taiChiTietTaiKhoanApi,
  capNhatTrangThaiTaiKhoan: capNhatTrangThaiTaiKhoanApi,
  capVaiTro: capVaiTroApi,
  thuHoiVaiTro: thuHoiVaiTroApi,
  taiDanhSachTaiKhoan: taiDanhSachTaiKhoanApi,
}))

const TAI_KHOAN = {
  id: 7,
  name: 'Nguyễn Minh Anh',
  email: 'member.with.a.very.long.email.address@example.com',
  phone: '0900000000',
  avatar: 'private-avatar-url',
  status: 'HOAT_DONG',
  email_verified_at: '2026-08-31T08:00:00.000000Z',
  last_login_at: '2026-08-31T09:00:00.000000Z',
  created_at: '2026-08-01T08:00:00.000000Z',
  updated_at: '2026-08-31T09:00:00.000000Z',
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
  member_profile: { id: 4, code: 'HV-004' },
  trainer_profile: { id: 5, code: 'PT-005', status: 'HOAT_DONG' },
  roles: [
    { assignment_id: 1, code: 'MEMBER', name: 'Hội viên', active: true, granted_at: '2026-08-01T08:00:00.000000Z' },
    { assignment_id: 2, code: 'PT', name: 'Huấn luyện viên', active: false, revoked_at: '2026-08-20T08:00:00.000000Z' },
  ],
  secret: 'khong-duoc-render',
  password: 'khong-duoc-render',
}

function phanHoiChiTiet(taiKhoan = TAI_KHOAN) {
  return { data: taiKhoan }
}

function phanHoiDanhSach(taiKhoan = TAI_KHOAN) {
  return {
    data: {
      items: [taiKhoan],
      pagination: { current_page: 1, per_page: 20, total: 1, last_page: 1 },
    },
  }
}

function phanHoiVaiTro({ role = 'RECEPTIONIST', active = true, transition = 'GRANTED' } = {}) {
  return {
    data: {
      assignment_id: 12,
      account_id: 7,
      role,
      active,
      changed: transition !== 'UNCHANGED',
      transition,
    },
  }
}

function taoRouter(id = '7') {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin/tai-khoan/:id',
        name: 'adminChiTietTaiKhoan',
        component: TaiKhoanChiTiet,
        meta: {
          duongDanPhanCap: [
            { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
            { nhan: 'Chi tiết tài khoản' },
          ],
        },
      },
      { path: '/admin/tai-khoan', name: 'adminTaiKhoan', component: { template: '<h1>Tài khoản</h1>' } },
      { path: '/khong-co-quyen', name: 'khongCoQuyen', component: { template: '<h1>403</h1>' } },
    ],
  })
}

async function mountTrang({ id = '7', chiTiet = phanHoiChiTiet() } = {}) {
  setActivePinia(createPinia())
  if (chiTiet instanceof Promise) {
    taiChiTietTaiKhoanApi.mockImplementationOnce(() => chiTiet)
  } else {
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(chiTiet)
  }
  const router = taoRouter(id)
  await router.push({ name: 'adminChiTietTaiKhoan', params: { id } })
  await router.isReady()
  const wrapper = mount(TaiKhoanChiTiet, { global: { plugins: [router] } })
  await flushPromises()

  return { router, wrapper }
}

describe('tai_khoan.chi_tiet FE1-T04', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('render H1, identity, branch, profile and safe allow-list fields', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('h1').text()).toBe('Chi tiết tài khoản')
    expect(wrapper.get('h2').text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.text()).toContain('member.with.a.very.long.email.address@example.com')
    expect(wrapper.text()).toContain('Chi nhánh Hà Nội')
    expect(wrapper.text()).toContain('HV-004')
    expect(wrapper.text()).toContain('PT-005')
    expect(wrapper.text()).toContain('Đang nhận phân công')
    expect(wrapper.text()).not.toContain('· HOAT_DONG')
    expect(wrapper.text()).not.toContain('khong-duoc-render')
    expect(wrapper.text()).not.toContain('private-avatar-url')
  })

  it('trainer profile status ngoai contract hien fail-safe, khong lo raw enum', async () => {
    const taiKhoan = {
      ...TAI_KHOAN,
      trainer_profile: { ...TAI_KHOAN.trainer_profile, status: 'TRANG_THAI_MOI' },
    }
    const { wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(taiKhoan) })

    expect(wrapper.text()).toContain('PT-005')
    expect(wrapper.text()).toContain('Không xác định')
    expect(wrapper.text()).not.toContain('TRANG_THAI_MOI')
  })

  it('GET detail dung id va khong goi API profile bo sung', async () => {
    await mountTrang()

    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledWith(7)
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledTimes(1)
    expect(taiDanhSachTaiKhoanApi).not.toHaveBeenCalled()
  })

  it('hien thi status hien tai va exact enum options', async () => {
    const { wrapper } = await mountTrang()
    const select = wrapper.get('#tai-khoan-chi-tiet-trang-thai')

    expect(select.element.value).toBe('HOAT_DONG')
    expect(select.findAll('option').map((option) => option.element.value)).toEqual([
      'HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG',
    ])
    expect(wrapper.text()).toContain('Hoạt động')
  })

  it('hien thi tat ca role active va revoked voi action dung trang thai', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.text()).toContain('Hội viên')
    expect(wrapper.text()).toContain('Huấn luyện viên · Đã thu hồi')
    expect(wrapper.text()).toContain('Thu hồi vai trò')
    expect(wrapper.text()).toContain('Cấp lại vai trò')
    expect(wrapper.text()).toContain('Cấp vai trò chưa gán')
    expect(wrapper.findAll('button').filter((button) => /vai trò|role/i.test(button.text())).length).toBe(3)
  })

  it('invalid route id hien unavailable va khong gui GET NaN/negative/object', async () => {
    const { wrapper } = await mountTrang({ id: 'abc', chiTiet: phanHoiChiTiet() })

    expect(taiChiTietTaiKhoanApi).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Không thể truy cập dữ liệu tài khoản này.')
    expect(wrapper.text()).not.toContain('abc')
  })

  it('initial loading co accessible state va khong hien empty gia', async () => {
    let resolveRequest
    taiChiTietTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRequest = resolve
    }))
    setActivePinia(createPinia())
    const router = taoRouter('7')
    await router.push({ name: 'adminChiTietTaiKhoan', params: { id: '7' } })
    await router.isReady()
    const wrapper = mount(TaiKhoanChiTiet, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.get('.trang-thai-tai-du-lieu').text()).toContain('Đang tải chi tiết tài khoản')
    expect(wrapper.get('[aria-label="Chi tiết tài khoản"]').attributes('aria-busy')).toBe('true')
    resolveRequest(phanHoiChiTiet())
    await flushPromises()
  })

  it('404 hien generic unavailable va co retry bi tat', async () => {
    const { wrapper } = await mountTrang({
      chiTiet: Promise.reject({ httpStatus: 404, message: 'Không tìm thấy tài khoản #7.' }),
    })

    expect(wrapper.text()).toContain('Không thể truy cập dữ liệu này.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('#7')
  })

  it('422 khi tai detail hien loi nhung khong mo retry vo nghia', async () => {
    const { wrapper } = await mountTrang({
      chiTiet: Promise.reject({
        httpStatus: 422,
        message: 'Dữ liệu định danh chưa hợp lệ.',
        fieldErrors: { id: ['Định danh không hợp lệ.'] },
        isNetworkError: false,
      }),
    })

    expect(wrapper.text()).toContain('Dữ liệu định danh chưa hợp lệ.')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(false)
  })

  it('403 clear detail va dieu huong khong co quyen khong logout', async () => {
    taiChiTietTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 403, message: 'Không có quyền.' })
    setActivePinia(createPinia())
    const router = taoRouter('7')
    await router.push({ name: 'adminChiTietTaiKhoan', params: { id: '7' } })
    await router.isReady()
    mount(TaiKhoanChiTiet, { global: { plugins: [router] } })
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })

  it('5xx detail co retry thu cong cung id', async () => {
    const { wrapper } = await mountTrang({
      chiTiet: Promise.reject({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' }),
    })
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet())

    expect(wrapper.text()).toContain('Máy chủ đang gặp sự cố')
    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(taiChiTietTaiKhoanApi).toHaveBeenLastCalledWith(7)
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
  })

  it('same current status disable submit va khong mo confirmation', async () => {
    const { wrapper } = await mountTrang()
    const nut = wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"] button[type="submit"]')

    expect(nut.attributes('disabled')).toBeDefined()
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(capNhatTrangThaiTaiKhoanApi).not.toHaveBeenCalled()
  })

  it('status change mo confirm truoc PATCH va dialog khong lo id/email', async () => {
    const { wrapper } = await mountTrang()
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')

    expect(wrapper.get('[role="dialog"]').text()).toContain('Xác nhận cập nhật trạng thái')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain('7')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain('member.with.a.very.long.email.address@example.com')
    expect(capNhatTrangThaiTaiKhoanApi).not.toHaveBeenCalled()
  })

  it('status success PATCH then authoritative GET and list refetch', async () => {
    const updated = { ...TAI_KHOAN, status: 'BI_KHOA' }
    capNhatTrangThaiTaiKhoanApi.mockResolvedValueOnce({ data: updated })
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoiDanhSach(updated))
    const { wrapper } = await mountTrang()
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledWith(7, 'BI_KHOA')
    expect(taiChiTietTaiKhoanApi).toHaveBeenLastCalledWith(7)
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: '', status: '', role: '', per_page: 20, page: 1,
    })
    expect(wrapper.text()).toContain('Đã cập nhật trạng thái tài khoản.')
    expect(wrapper.get('#tai-khoan-chi-tiet-trang-thai').element.value).toBe('BI_KHOA')
  })

  it('mutation pending disable select/button/dialog controls', async () => {
    let resolvePatch
    capNhatTrangThaiTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolvePatch = resolve
    }))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoiDanhSach())
    const { wrapper } = await mountTrang()
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...TAI_KHOAN, status: 'BI_KHOA' }))
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    const request = wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.get('#tai-khoan-chi-tiet-trang-thai').attributes('disabled')).toBeDefined()
    expect(wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"] button').attributes('disabled')).toBeDefined()
    resolvePatch({ data: {} })
    await request
    await flushPromises()
  })

  it('422 map field error va giu detail status hien tai', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { status: ['Trạng thái không hợp lệ.'] },
    })
    const { wrapper } = await mountTrang()
    document.body.appendChild(wrapper.element)
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Trạng thái không hợp lệ.')
    expect(wrapper.get('.huy-hieu-trang-thai').text()).toContain('Hoạt động')
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledTimes(1)
    expect(document.activeElement).toBe(wrapper.get('#tai-khoan-chi-tiet-trang-thai').element)
    wrapper.unmount()
  })

  it('409 last-admin hien controlled message va refetch', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 409,
      code: 'LAST_ACTIVE_ADMIN_PROTECTED',
      message: 'Không thể vô hiệu hóa quản trị viên hoạt động cuối cùng.',
    })
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet())
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoiDanhSach())
    const { wrapper } = await mountTrang()
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Không thể vô hiệu hóa quản trị viên hoạt động cuối cùng.')
    expect(taiChiTietTaiKhoanApi).toHaveBeenLastCalledWith(7)
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it.each([403, 404])('PATCH %s khong hien detail sau khi Backend tu choi', async (httpStatus) => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus, message: 'Không được truy cập.' })
    const { router, wrapper } = await mountTrang()
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    if (httpStatus === 403) {
      expect(router.currentRoute.value.name).toBe('khongCoQuyen')
    } else {
      expect(wrapper.text()).not.toContain('Nguyễn Minh Anh')
    }
  })

  it('PATCH 401 hien normalized error va khong tu logout', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 401, message: 'Thông tin xác thực không hợp lệ.' })
    const { router, wrapper } = await mountTrang()
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('adminChiTietTaiKhoan')
    expect(wrapper.text()).toContain('Thông tin xác thực không hợp lệ.')
  })

  it('network PATCH refetch target status thi ghi nhan effective outcome', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus: null, isNetworkError: true })
    const updated = { ...TAI_KHOAN, status: 'BI_KHOA' }
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoiDanhSach(updated))
    const { wrapper } = await mountTrang()
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Trạng thái đã được Backend xác nhận.')
    expect(wrapper.text()).not.toContain('Chưa xác định được kết quả')
  })

  it('network PATCH va GET fail hien unknown outcome, khong co retry mutation', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    const { wrapper } = await mountTrang()
    taiChiTietTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Chưa xác định được kết quả cập nhật trạng thái.')
    expect(wrapper.find('.the-chi-tiet-tai-khoan__loi-mutation').text())
      .not.toContain('Thử lại')
    expect(wrapper.find('.trang-thai-loi button').exists()).toBe(true)
    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it('list sync status filter khong do page tu loai row, chi refetch Backend', async () => {
    const updated = { ...TAI_KHOAN, status: 'BI_KHOA' }
    capNhatTrangThaiTaiKhoanApi.mockResolvedValueOnce({ data: updated })
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce({
      data: { items: [], pagination: { current_page: 1, per_page: 20, total: 0, last_page: 1 } },
    })
    const { wrapper } = await mountTrang()
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Đã cập nhật trạng thái tài khoản.')
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it('responsive and accessibility hooks co label, aria-busy, live error va dialog', async () => {
    const { wrapper } = await mountTrang()

    expect(wrapper.get('label[for="tai-khoan-chi-tiet-trang-thai"]').exists()).toBe(true)
    expect(wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').attributes('aria-busy')).toBe('false')
    expect(wrapper.get('[aria-labelledby="tieu-de-trang-thai-tai-khoan"]').exists()).toBe(true)
    await wrapper.get('#tai-khoan-chi-tiet-trang-thai').setValue('BI_KHOA')
    await wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"]').trigger('submit')
    expect(wrapper.get('[role="dialog"]').attributes('aria-modal')).toBe('true')
    expect(wrapper.get('[role="dialog"]').attributes('aria-describedby')).toBeTruthy()
  })

  it('khong co hard-delete va role action khong lo payload nhay cam', async () => {
    const { wrapper } = await mountTrang()
    const source = wrapper.html()

    expect(source).not.toMatch(/Xóa tài khoản|Xoá tài khoản/)
    expect(source).not.toContain('private-avatar-url')
    expect(source).not.toContain('khong-duoc-render')
    expect(source).toContain('Chỉ chọn một trạng thái trong danh sách Backend cho phép.')
  })

  it('active role co nut thu hoi va confirmation khong lo id/email', async () => {
    const { wrapper } = await mountTrang()
    const nutThuHoi = wrapper.findAll('button').find((button) => button.text() === 'Thu hồi vai trò')

    await nutThuHoi.trigger('click')

    expect(wrapper.get('[role="dialog"]').text()).toContain('Xác nhận thu hồi vai trò')
    expect(wrapper.get('[role="dialog"]').text()).toContain('Thu hồi vai trò')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain('7')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain(TAI_KHOAN.email)
    expect(capVaiTroApi).not.toHaveBeenCalled()
    expect(thuHoiVaiTroApi).not.toHaveBeenCalled()
  })

  it('revoked role chi co action cap lai, khong co thu hoi lan nua', async () => {
    const { wrapper } = await mountTrang()
    const mucVaiTro = wrapper.findAll('.the-chi-tiet-tai-khoan__vai-tro-muc')
      .find((muc) => muc.text().includes('Huấn luyện viên'))

    expect(mucVaiTro.text()).toContain('Cấp lại vai trò')
    expect(mucVaiTro.find('button').text()).toBe('Cấp lại vai trò')
    await mucVaiTro.find('button').trigger('click')

    expect(wrapper.get('[role="dialog"]').text()).toContain('Xác nhận cấp lại vai trò')
    expect(wrapper.get('[role="dialog"]').text()).toContain('Cấp lại vai trò Huấn luyện viên')
  })

  it('absent role grant mo confirmation va success refetch detail/list', async () => {
    const current = { ...TAI_KHOAN, roles: [{ ...TAI_KHOAN.roles[0] }] }
    const updated = {
      ...current,
      roles: [...current.roles, { assignment_id: 12, code: 'RECEPTIONIST', active: true }],
    }
    const { wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(current) })
    await wrapper.get('#tai-khoan-cap-vai-tro').setValue('RECEPTIONIST')
    await wrapper.get('form[aria-label="Cấp vai trò"]').trigger('submit')

    expect(wrapper.get('[role="dialog"]').text()).toContain('Xác nhận cấp vai trò')
    expect(wrapper.get('[role="dialog"]').text()).toContain('Nhân viên lễ tân')
    capVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro())
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoiDanhSach(updated))
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(capVaiTroApi).toHaveBeenCalledWith(7, 'RECEPTIONIST')
    expect(taiChiTietTaiKhoanApi).toHaveBeenLastCalledWith(7)
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Đã cấp vai trò cho tài khoản.')
    expect(wrapper.text()).toContain('Nhân viên lễ tân')
  })

  it('role pending disable controls va chan duplicate confirm', async () => {
    let resolvePut
    capVaiTroApi.mockImplementationOnce(() => new Promise((resolve) => { resolvePut = resolve }))
    const current = { ...TAI_KHOAN, roles: [{ ...TAI_KHOAN.roles[0] }] }
    const { wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(current) })
    await wrapper.get('#tai-khoan-cap-vai-tro').setValue('RECEPTIONIST')
    await wrapper.get('form[aria-label="Cấp vai trò"]').trigger('submit')
    const request = wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.get('#tai-khoan-cap-vai-tro').attributes('disabled')).toBeDefined()
    expect(wrapper.get('form[aria-label="Cập nhật trạng thái tài khoản"] button').attributes('disabled')).toBeDefined()
    expect(wrapper.findAll('button').filter((button) => button.text() === 'Cấp vai trò')[0]
      .attributes('disabled')).toBeDefined()
    resolvePut(phanHoiVaiTro())
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(current))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoiDanhSach(current))
    await request
    await flushPromises()
  })

  it.each([
    ['ROLE_CONFLICT', 'Vai trò vừa được thay đổi bởi thao tác khác.'],
    ['TRAINER_PROFILE_REQUIRED', 'chưa có hồ sơ huấn luyện viên'],
    ['LAST_ACTIVE_ADMIN_PROTECTED', 'quản trị viên hoạt động cuối cùng'],
  ])('role 409 %s hien UX co kiem soat va khong onboarding gia', async (code, thongBao) => {
    const current = { ...TAI_KHOAN, roles: [{ ...TAI_KHOAN.roles[0] }] }
    capVaiTroApi.mockRejectedValueOnce({ httpStatus: 409, code, message: 'Conflict role.' })
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(current))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoiDanhSach(current))
    const { wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(current) })
    await wrapper.get('#tai-khoan-cap-vai-tro').setValue('RECEPTIONIST')
    await wrapper.get('form[aria-label="Cấp vai trò"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain(thongBao)
    expect(wrapper.text()).not.toContain('Tạo hồ sơ huấn luyện viên')
    expect(wrapper.text()).not.toContain('Onboarding')
    expect(taiChiTietTaiKhoanApi).toHaveBeenLastCalledWith(7)
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it('Admin revoke warning noi ro last-admin Backend guard', async () => {
    const current = {
      ...TAI_KHOAN,
      roles: [{ assignment_id: 9, code: 'ADMIN', active: true }],
    }
    const { wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(current) })
    const mucAdmin = wrapper.findAll('.the-chi-tiet-tai-khoan__vai-tro-muc')
      .find((muc) => muc.text().includes('Quản trị viên'))
    await mucAdmin.find('button').trigger('click')

    expect(wrapper.get('[role="dialog"]').text()).toContain('hoạt động cuối cùng')
    expect(wrapper.get('[role="dialog"]').find('button:last-child').classes()).toContain('nut--nguy-hiem')
  })

  it('role 422 giu detail va role current, khong refetch', async () => {
    const current = { ...TAI_KHOAN, roles: [{ ...TAI_KHOAN.roles[0] }] }
    capVaiTroApi.mockRejectedValueOnce({
      httpStatus: 422,
      code: 'INVALID_ROLE',
      message: 'Dữ liệu vai trò chưa hợp lệ.',
      fieldErrors: { role: ['Vai trò không hợp lệ.'] },
    })
    const { wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(current) })
    document.body.appendChild(wrapper.element)
    await wrapper.get('#tai-khoan-cap-vai-tro').setValue('RECEPTIONIST')
    await wrapper.get('form[aria-label="Cấp vai trò"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Dữ liệu vai trò chưa hợp lệ.')
    expect(wrapper.text()).toContain('Vai trò không hợp lệ.')
    expect(wrapper.text()).toContain('Nguyễn Minh Anh')
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledTimes(1)
    expect(document.activeElement).toBe(wrapper.get('#tai-khoan-cap-vai-tro').element)
    wrapper.unmount()
  })

  it('role 403 clear detail va chuyen khong co quyen', async () => {
    const current = { ...TAI_KHOAN, roles: [{ ...TAI_KHOAN.roles[0] }] }
    capVaiTroApi.mockRejectedValueOnce({ httpStatus: 403, message: 'Không được truy cập.' })
    const { router, wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(current) })
    await wrapper.get('#tai-khoan-cap-vai-tro').setValue('RECEPTIONIST')
    await wrapper.get('form[aria-label="Cấp vai trò"]').trigger('submit')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })

  it('current Admin role loss sau refetch 403 chuyen khong co quyen', async () => {
    const current = {
      ...TAI_KHOAN,
      roles: [{ assignment_id: 9, code: 'ADMIN', active: true }],
    }
    thuHoiVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro({
      role: 'ADMIN', active: false, transition: 'REVOKED',
    }))
    const { router, wrapper } = await mountTrang({ chiTiet: phanHoiChiTiet(current) })
    taiChiTietTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 403, message: 'Không có quyền.' })
    const mucAdmin = wrapper.findAll('.the-chi-tiet-tai-khoan__vai-tro-muc')
      .find((muc) => muc.text().includes('Quản trị viên'))
    await mucAdmin.find('button').trigger('click')
    await wrapper.get('[role="dialog"] button:last-child').trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('khongCoQuyen')
  })
})
