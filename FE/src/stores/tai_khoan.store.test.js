import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import {
  capVaiTro as capVaiTroApi,
  capNhatTrangThaiTaiKhoan as capNhatTrangThaiTaiKhoanApi,
  taiChiTietHoiVien as taiChiTietHoiVienApi,
  taiChiTietNhanVienLeTan as taiChiTietNhanVienLeTanApi,
  taiChiTietTaiKhoan as taiChiTietTaiKhoanApi,
  taiDanhSachHoiVien as taiDanhSachHoiVienApi,
  taiDanhSachNhanVienLeTan as taiDanhSachNhanVienLeTanApi,
  taiDanhSachTaiKhoan as taiDanhSachTaiKhoanApi,
  thuHoiVaiTro as thuHoiVaiTroApi,
} from '../services/tai_khoan.api.js'
import {
  useTaiKhoanStore,
  xoaDuLieuTaiKhoanNeuDaKhoiTao,
} from './tai_khoan.store.js'

vi.mock('../services/tai_khoan.api.js', () => ({
  CAC_TRANG_THAI_TAI_KHOAN: ['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'],
  CAC_VAI_TRO_TAI_KHOAN: ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'],
  CAC_CHUYEN_DOI_VAI_TRO: ['GRANTED', 'REGRANTED', 'REVOKED', 'UNCHANGED'],
  capVaiTro: vi.fn(),
  capNhatTrangThaiTaiKhoan: vi.fn(),
  laIdTaiKhoanHopLe: (id) => (typeof id === 'number'
    ? Number.isSafeInteger(id) && id > 0
    : typeof id === 'string' && /^[1-9]\d*$/.test(id.trim())),
  taiChiTietTaiKhoan: vi.fn(),
  taiChiTietHoiVien: vi.fn(),
  taiChiTietNhanVienLeTan: vi.fn(),
  taiDanhSachTaiKhoan: vi.fn(),
  taiDanhSachHoiVien: vi.fn(),
  taiDanhSachNhanVienLeTan: vi.fn(),
  thuHoiVaiTro: vi.fn(),
}))

const TAI_KHOAN = {
  id: 7,
  name: 'Nguyễn Minh Anh',
  email: 'anh@example.com',
  phone: '0900000000',
  status: 'HOAT_DONG',
  roles: [{ assignment_id: 1, code: 'MEMBER', active: true }],
  branch: { id: 1, code: 'HN-01', name: 'Chi nhánh Hà Nội' },
}

const NHAN_VIEN_LE_TAN = {
  ...TAI_KHOAN,
  roles: [{ assignment_id: 2, code: 'RECEPTIONIST', name: 'Nhân viên lễ tân', active: true }],
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

function phanHoiChiTiet(taiKhoan = TAI_KHOAN) {
  return { data: taiKhoan }
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

describe('tai_khoan.store FE1-T03', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
  })

  it('co state list/filter/detail/mutation ban dau', () => {
    const store = useTaiKhoanStore()

    expect(store.danhSachTaiKhoan).toEqual([])
    expect(store.boLoc).toEqual({ search: '', status: '', role: '', per_page: 20 })
    expect(store.phanTrang).toEqual({ current_page: 1, per_page: 20, total: 0, last_page: 1 })
    expect(store.daTaiLanDau).toBe(false)
    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.dangTaiChiTiet).toBe(false)
    expect(store.loiTaiChiTiet).toBeNull()
    expect(store.dangCapNhatTrangThai).toBe(false)
    expect(store.loiCapNhatTrangThai).toBeNull()
    expect(store).toHaveProperty('capNhatTrangThaiTaiKhoan')
    expect(store.dangThayDoiVaiTro).toBe(false)
    expect(store.vaiTroDangXuLy).toBeNull()
    expect(store.loiThayDoiVaiTro).toBeNull()
    expect(store.thongBaoThayDoiVaiTro).toBeNull()
    expect(store.ketQuaThayDoiVaiTro).toBeNull()
    expect(store).toHaveProperty('capVaiTro')
    expect(store).toHaveProperty('thuHoiVaiTro')
  })

  it('tai lan dau gui page/per_page va commit items pagination exact', async () => {
    taiDanhSachTaiKhoanApi.mockResolvedValue(phanHoi([TAI_KHOAN], {
      current_page: 1,
      per_page: 20,
      total: 21,
      last_page: 2,
    }))
    const store = useTaiKhoanStore()

    await expect(store.taiDanhSachTaiKhoan()).resolves.toMatchObject({ items: [TAI_KHOAN] })

    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: '', status: '', role: '', per_page: 20, page: 1,
    })
    expect(store.danhSachTaiKhoan).toEqual([TAI_KHOAN])
    expect(store.phanTrang).toEqual({ current_page: 1, per_page: 20, total: 21, last_page: 2 })
    expect(store.daTaiLanDau).toBe(true)
    expect(store.dangTai).toBe(false)
  })

  it('ap dung filter luon reset page 1 va giu applied filter', async () => {
    taiDanhSachTaiKhoanApi.mockResolvedValue(phanHoi())
    const store = useTaiKhoanStore()
    store.phanTrang.current_page = 4

    await store.apDungBoLocTaiKhoan({ search: ' Anh ', status: 'HOAT_DONG', role: 'MEMBER' })

    expect(store.boLoc).toEqual({ search: 'Anh', status: 'HOAT_DONG', role: 'MEMBER', per_page: 20 })
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: 'Anh', status: 'HOAT_DONG', role: 'MEMBER', per_page: 20, page: 1,
    })
  })

  it('dat lai filter reset page va goi query rong', async () => {
    taiDanhSachTaiKhoanApi.mockResolvedValue(phanHoi())
    const store = useTaiKhoanStore()
    store.boLoc = { search: 'Anh', status: 'BI_KHOA', role: 'PT', per_page: 20 }
    store.phanTrang.current_page = 3

    await store.datLaiBoLocTaiKhoan()

    expect(store.boLoc).toEqual({ search: '', status: '', role: '', per_page: 20 })
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: '', status: '', role: '', per_page: 20, page: 1,
    })
  })

  it('chuyen trang giu applied filter va khong doi per_page client', async () => {
    taiDanhSachTaiKhoanApi.mockResolvedValue(phanHoi([], {
      current_page: 2,
      per_page: 20,
      total: 40,
      last_page: 2,
    }))
    const store = useTaiKhoanStore()
    store.boLoc = { search: 'Anh', status: '', role: 'MEMBER', per_page: 20 }
    store.phanTrang = { current_page: 1, per_page: 20, total: 40, last_page: 2 }

    await store.chuyenTrangTaiKhoan(2)

    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: 'Anh', status: '', role: 'MEMBER', per_page: 20, page: 2,
    })
    expect(store.phanTrang.current_page).toBe(2)
  })

  it('Account list sua mot lan ve last_page khi dataset co lai', async () => {
    taiDanhSachTaiKhoanApi
      .mockResolvedValueOnce(phanHoi([], { current_page: 2, total: 1, last_page: 1 }))
      .mockResolvedValueOnce(phanHoi([TAI_KHOAN], { current_page: 1, total: 1, last_page: 1 }))
    const store = useTaiKhoanStore()
    store.boLoc = { search: 'Anh', status: '', role: 'MEMBER', per_page: 20 }

    await store.taiDanhSachTaiKhoan({ boLoc: store.boLoc, trang: 2 })

    expect(taiDanhSachTaiKhoanApi).toHaveBeenNthCalledWith(1, {
      search: 'Anh', status: '', role: 'MEMBER', per_page: 20, page: 2,
    })
    expect(taiDanhSachTaiKhoanApi).toHaveBeenNthCalledWith(2, {
      search: 'Anh', status: '', role: 'MEMBER', per_page: 20, page: 1,
    })
    expect(store.phanTrang.current_page).toBe(1)
    expect(store.danhSachTaiKhoan).toEqual([TAI_KHOAN])
  })

  it.each([0, -1, 3, '2', null])('chan chuyen trang ngoai bien %j khong request', async (trang) => {
    const store = useTaiKhoanStore()
    store.phanTrang = { current_page: 1, per_page: 20, total: 40, last_page: 2 }

    await store.chuyenTrangTaiKhoan(trang)

    expect(taiDanhSachTaiKhoanApi).not.toHaveBeenCalled()
  })

  it('khong request lai khi click dung trang hien tai', async () => {
    const store = useTaiKhoanStore()
    store.phanTrang = { current_page: 2, per_page: 20, total: 40, last_page: 2 }

    await store.chuyenTrangTaiKhoan(2)

    expect(taiDanhSachTaiKhoanApi).not.toHaveBeenCalled()
  })

  it('retry giu nguyen applied filter va current page', async () => {
    taiDanhSachTaiKhoanApi.mockResolvedValue(phanHoi())
    const store = useTaiKhoanStore()
    store.boLoc = { search: 'Anh', status: 'HOAT_DONG', role: 'MEMBER', per_page: 20 }
    store.phanTrang = { current_page: 2, per_page: 20, total: 21, last_page: 2 }

    await store.thuLaiTaiDanhSachTaiKhoan()

    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: 'Anh', status: 'HOAT_DONG', role: 'MEMBER', per_page: 20, page: 2,
    })
  })

  it('giu data cu va map loi 422 field errors', async () => {
    const loi = {
      httpStatus: 422,
      code: 'INVALID_ACCOUNT_FILTER',
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { search: ['Từ khóa không hợp lệ.'] },
      isNetworkError: false,
    }
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
      .mockRejectedValueOnce(loi)
    const store = useTaiKhoanStore()
    await store.taiDanhSachTaiKhoan()
    await store.apDungBoLocTaiKhoan({ search: '???' })

    expect(store.danhSachTaiKhoan).toEqual([TAI_KHOAN])
    expect(store.loiTaiDanhSach).toMatchObject({
      httpStatus: 422,
      fieldErrors: { search: ['Từ khóa không hợp lệ.'] },
    })
  })

  it.each([
    [{ httpStatus: 401, message: 'Thông tin xác thực không hợp lệ.' }],
    [{ httpStatus: 403, message: 'Bạn không có quyền thực hiện thao tác này.' }],
    [{ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' }],
    [{ httpStatus: null, message: 'Không thể kết nối đến máy chủ.', isNetworkError: true }],
  ])('giu data va luu loi an toan cho %j', async (loi) => {
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
      .mockRejectedValueOnce(loi)
    const store = useTaiKhoanStore()
    await store.taiDanhSachTaiKhoan()
    await store.thuLaiTaiDanhSachTaiKhoan()

    expect(store.danhSachTaiKhoan).toEqual([TAI_KHOAN])
    expect(store.loiTaiDanhSach).toMatchObject({ message: loi.message })
    expect(store.loiTaiDanhSach).not.toHaveProperty('config')
  })

  it('khong ghi response cu khi request moi hoan thanh truoc', async () => {
    let resolveCu
    let resolveMoi
    taiDanhSachTaiKhoanApi
      .mockImplementationOnce(() => new Promise((resolve) => { resolveCu = resolve }))
      .mockImplementationOnce(() => new Promise((resolve) => { resolveMoi = resolve }))
    const store = useTaiKhoanStore()
    const requestCu = store.taiDanhSachTaiKhoan({
      boLoc: { search: 'cu' },
      trang: 1,
    })
    const requestMoi = store.taiDanhSachTaiKhoan({
      boLoc: { search: 'moi' },
      trang: 1,
    })

    resolveMoi(phanHoi([{ ...TAI_KHOAN, id: 8, name: 'Kết quả mới' }]))
    await requestMoi
    resolveCu(phanHoi([{ ...TAI_KHOAN, id: 9, name: 'Kết quả cũ' }]))
    await requestCu

    expect(store.danhSachTaiKhoan).toEqual([{ ...TAI_KHOAN, id: 8, name: 'Kết quả mới' }])
    expect(store.boLoc.search).toBe('moi')
  })

  it('xoaDuLieu vo hieu pending response va reset memory', async () => {
    let resolveRequest
    taiDanhSachTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRequest = resolve
    }))
    const store = useTaiKhoanStore()
    const request = store.taiDanhSachTaiKhoan({ boLoc: { search: 'cu' }, trang: 1 })
    store.xoaDuLieu()
    resolveRequest(phanHoi())
    await request

    expect(store.danhSachTaiKhoan).toEqual([])
    expect(store.boLoc).toEqual({ search: '', status: '', role: '', per_page: 20 })
    expect(store.daTaiLanDau).toBe(false)
    expect(store.loiTaiDanhSach).toBeNull()
  })

  it('validate response envelope va khong render malformed data', async () => {
    taiDanhSachTaiKhoanApi.mockResolvedValue({ data: { items: [], pagination: {} } })
    const store = useTaiKhoanStore()

    await store.taiDanhSachTaiKhoan()

    expect(store.loiTaiDanhSach).toMatchObject({ code: 'ACCOUNT_LIST_RESPONSE_INVALID' })
    expect(store.danhSachTaiKhoan).toEqual([])
  })

  it('cleanup helper chi reset store da khoi tao', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    expect(xoaDuLieuTaiKhoanNeuDaKhoiTao(pinia)).toBe(false)
    const store = useTaiKhoanStore(pinia)
    store.danhSachTaiKhoan = [TAI_KHOAN]

    expect(xoaDuLieuTaiKhoanNeuDaKhoiTao(pinia)).toBe(true)
    expect(store.danhSachTaiKhoan).toEqual([])
  })

  it('khong co persist plugin hay credential trong state', () => {
    const store = useTaiKhoanStore()
    const stateText = JSON.stringify(store.$state)

    expect(stateText).not.toMatch(/password|token|reset|mat_khau|credential/i)
    expect(store.$persist).toBeUndefined()
  })

  it('tai detail va commit DTO sau khi validate envelope', async () => {
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet())
    const store = useTaiKhoanStore()

    await expect(store.taiChiTietTaiKhoan('7')).resolves.toEqual(TAI_KHOAN)

    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledWith(7)
    expect(store.taiKhoanDaChon).toEqual(TAI_KHOAN)
    expect(store.dangTaiChiTiet).toBe(false)
    expect(store.daTaiChiTietLanDau).toBe(true)
  })

  it('khong gui GET detail voi id route invalid va hien loi unavailable', async () => {
    const store = useTaiKhoanStore()

    await store.taiChiTietTaiKhoan('-1')

    expect(taiChiTietTaiKhoanApi).not.toHaveBeenCalled()
    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.loiTaiChiTiet).toMatchObject({ httpStatus: 404 })
  })

  it('malformed detail response khong commit state', async () => {
    taiChiTietTaiKhoanApi.mockResolvedValueOnce({ data: { id: 7, roles: [] } })
    const store = useTaiKhoanStore()

    await store.taiChiTietTaiKhoan(7)

    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.loiTaiChiTiet).toMatchObject({ code: 'ACCOUNT_DETAIL_RESPONSE_INVALID' })
  })

  it.each([403, 404])('detail %s clear selected Account khong logout', async (httpStatus) => {
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet())
      .mockRejectedValueOnce({ httpStatus, message: 'Không được truy cập.' })
    const store = useTaiKhoanStore()
    await store.taiChiTietTaiKhoan(7)
    await store.taiChiTietTaiKhoan(8)

    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.loiTaiChiTiet.httpStatus).toBe(httpStatus)
  })

  it('detail 5xx giu detail cung id de nguoi dung thu lai', async () => {
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet())
      .mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    const store = useTaiKhoanStore()
    await store.taiChiTietTaiKhoan(7)
    await store.lamMoiTaiKhoan(7)

    expect(store.taiKhoanDaChon).toEqual(TAI_KHOAN)
    expect(store.loiTaiChiTiet).toMatchObject({ httpStatus: 503 })
  })

  it('detail race A-B chi commit response B ve sau', async () => {
    let resolveA
    let resolveB
    taiChiTietTaiKhoanApi
      .mockImplementationOnce(() => new Promise((resolve) => { resolveA = resolve }))
      .mockImplementationOnce(() => new Promise((resolve) => { resolveB = resolve }))
    const store = useTaiKhoanStore()
    const requestA = store.taiChiTietTaiKhoan(7)
    const requestB = store.taiChiTietTaiKhoan(8)

    resolveB(phanHoiChiTiet({ ...TAI_KHOAN, id: 8, name: 'Tài khoản B' }))
    await requestB
    resolveA(phanHoiChiTiet({ ...TAI_KHOAN, id: 7, name: 'Tài khoản A' }))
    await requestA

    expect(store.taiKhoanDaChon).toMatchObject({ id: 8, name: 'Tài khoản B' })
  })

  it('xoaDuLieu clear detail va mutation state', () => {
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    store.loiTaiChiTiet = { httpStatus: 503 }
    store.loiCapNhatTrangThai = { httpStatus: 409 }
    store.thongBaoCapNhatTrangThai = 'Đã cập nhật.'
    store.dangCapNhatTrangThai = true
    store.danhSachNhanVienLeTan = [NHAN_VIEN_LE_TAN]
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN
    store.dangCapNhatTrangThaiNhanVienLeTan = true
    store.loiThuHoiVaiTroNhanVienLeTan = { httpStatus: 409 }

    store.xoaDuLieu()

    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.loiTaiChiTiet).toBeNull()
    expect(store.loiCapNhatTrangThai).toBeNull()
    expect(store.thongBaoCapNhatTrangThai).toBeNull()
    expect(store.dangCapNhatTrangThai).toBe(false)
    expect(store.danhSachNhanVienLeTan).toEqual([])
    expect(store.nhanVienLeTanDaChon).toBeNull()
    expect(store.dangCapNhatTrangThaiNhanVienLeTan).toBe(false)
    expect(store.loiThuHoiVaiTroNhanVienLeTan).toBeNull()
  })

  it('status success khong optimistic, refetch detail va sync list cung filter/page', async () => {
    let resolveDetail
    capNhatTrangThaiTaiKhoanApi.mockResolvedValueOnce({ data: { status: 'BI_KHOA' } })
    taiChiTietTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveDetail = resolve
    }))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([{ ...TAI_KHOAN, status: 'BI_KHOA' }]))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    store.boLoc = { search: 'Anh', status: '', role: 'MEMBER', per_page: 20 }
    store.phanTrang = { current_page: 2, per_page: 20, total: 21, last_page: 2 }
    const request = store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    await Promise.resolve()
    expect(store.taiKhoanDaChon.status).toBe('HOAT_DONG')
    expect(store.dangCapNhatTrangThai).toBe(true)
    resolveDetail(phanHoiChiTiet({ ...TAI_KHOAN, status: 'BI_KHOA' }))
    await request

    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledWith(7, 'BI_KHOA')
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledWith(7)
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: 'Anh', status: '', role: 'MEMBER', per_page: 20, page: 2,
    })
    expect(store.taiKhoanDaChon.status).toBe('BI_KHOA')
    expect(store.thongBaoCapNhatTrangThai).toBe('Đã cập nhật trạng thái tài khoản.')
    expect(store.dangCapNhatTrangThai).toBe(false)
  })

  it('same current status khong gui PATCH', async () => {
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await expect(store.capNhatTrangThaiTaiKhoan(7, 'HOAT_DONG')).resolves.toMatchObject({
      unchanged: true,
    })

    expect(capNhatTrangThaiTaiKhoanApi).not.toHaveBeenCalled()
  })

  it('chan duplicate mutation khi request dang pending', async () => {
    let resolvePatch
    capNhatTrangThaiTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolvePatch = resolve
    }))
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...TAI_KHOAN, status: 'BI_KHOA' }))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    const request = store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    await expect(store.capNhatTrangThaiTaiKhoan(7, 'NGUNG_HOAT_DONG')).resolves.toBeNull()
    resolvePatch({ data: {} })
    await request

    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it('cleanup detail chan status mutation cu khoi tao refetch cho route A', async () => {
    let resolvePatch
    capNhatTrangThaiTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolvePatch = resolve
    }))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    const request = store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    store.xoaTaiKhoanDaChon()
    store.taiKhoanDaChon = { ...TAI_KHOAN, id: 8, name: 'Tài khoản B' }
    resolvePatch({ data: { id: 7, status: 'BI_KHOA' } })
    await request

    expect(taiChiTietTaiKhoanApi).not.toHaveBeenCalled()
    expect(taiDanhSachTaiKhoanApi).not.toHaveBeenCalled()
    expect(store.taiKhoanDaChon).toMatchObject({ id: 8, name: 'Tài khoản B' })
    expect(store.dangCapNhatTrangThai).toBe(false)
    expect(store.thongBaoCapNhatTrangThai).toBeNull()
  })

  it('422 giu selected detail va map field error', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { status: ['Trạng thái không hợp lệ.'] },
    })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    expect(store.taiKhoanDaChon).toEqual(TAI_KHOAN)
    expect(store.loiCapNhatTrangThai).toMatchObject({
      httpStatus: 422,
      fieldErrors: { status: ['Trạng thái không hợp lệ.'] },
    })
    expect(taiChiTietTaiKhoanApi).not.toHaveBeenCalled()
  })

  it('409 giu code exact va refetch detail/list', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: 409,
      code: 'LAST_ACTIVE_ADMIN_PROTECTED',
      message: 'Không thể vô hiệu hóa quản trị viên hoạt động cuối cùng.',
    })
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet())
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    expect(store.loiCapNhatTrangThai).toMatchObject({
      httpStatus: 409,
      code: 'LAST_ACTIVE_ADMIN_PROTECTED',
    })
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledWith(7)
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it.each([403, 404])('status %s clear selected va khong logout', async (httpStatus) => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus, message: 'Không được truy cập.' })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.loiCapNhatTrangThai.httpStatus).toBe(httpStatus)
  })

  it('401 giu loi normalized va khong tu logout', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 401, message: 'Thông tin xác thực không hợp lệ.' })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    expect(store.loiCapNhatTrangThai).toMatchObject({ httpStatus: 401 })
    expect(store.taiKhoanDaChon).toEqual(TAI_KHOAN)
  })

  it('network sau PATCH refetch thay target thi ghi nhan da xac nhan', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({
      httpStatus: null,
      isNetworkError: true,
      message: 'Không thể kết nối đến máy chủ.',
    })
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...TAI_KHOAN, status: 'BI_KHOA' }))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([{ ...TAI_KHOAN, status: 'BI_KHOA' }]))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await expect(store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')).resolves.toMatchObject({ daXacNhan: true })

    expect(store.loiCapNhatTrangThai).toBeNull()
    expect(store.thongBaoCapNhatTrangThai).toBe('Trạng thái đã được Backend xác nhận.')
    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it('network sau PATCH refetch state cu thi cho phep action moi va giu unknown warning', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus: null, isNetworkError: true })
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet())
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    expect(store.taiKhoanDaChon.status).toBe('HOAT_DONG')
    expect(store.loiCapNhatTrangThai).toMatchObject({ outcomeUnknown: true })
    expect(store.dangCapNhatTrangThai).toBe(false)
  })

  it('network PATCH va GET cung fail giu ket qua chua xac dinh, khong blind retry', async () => {
    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    taiChiTietTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')

    expect(store.loiCapNhatTrangThai).toMatchObject({ outcomeUnknown: true })
    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledTimes(1)
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it('validation khi selected account khong trung id khong gui PATCH', async () => {
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(8, 'BI_KHOA')

    expect(capNhatTrangThaiTaiKhoanApi).not.toHaveBeenCalled()
    expect(store.loiCapNhatTrangThai).toMatchObject({ httpStatus: 404 })
  })

  it('validation status ngoai enum khong gui PATCH', async () => {
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capNhatTrangThaiTaiKhoan(7, 'ACTIVE')

    expect(capNhatTrangThaiTaiKhoanApi).not.toHaveBeenCalled()
    expect(store.loiCapNhatTrangThai).toMatchObject({ httpStatus: 422 })
  })

  it('grant role khong optimistic, refetch detail va sync list cung filter/page', async () => {
    const updated = {
      ...TAI_KHOAN,
      roles: [
        ...TAI_KHOAN.roles,
        { assignment_id: 12, code: 'RECEPTIONIST', active: true },
      ],
    }
    capVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro())
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([updated]))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    store.boLoc = { search: 'Anh', status: '', role: 'MEMBER', per_page: 20 }
    store.phanTrang = { current_page: 2, per_page: 20, total: 21, last_page: 2 }

    await expect(store.capVaiTro(7, 'RECEPTIONIST')).resolves.toMatchObject({
      changed: true,
      role: { transition: 'GRANTED' },
    })

    expect(store.taiKhoanDaChon.roles).toEqual(updated.roles)
    expect(capVaiTroApi).toHaveBeenCalledWith(7, 'RECEPTIONIST')
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledWith(7)
    expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledWith({
      search: 'Anh', status: '', role: 'MEMBER', per_page: 20, page: 2,
    })
    expect(store.thongBaoThayDoiVaiTro).toBe('Đã cấp vai trò cho tài khoản.')
    expect(store.dangThayDoiVaiTro).toBe(false)
  })

  it('regrant role revoked dung PUT va giu assignment state authoritative', async () => {
    const current = {
      ...TAI_KHOAN,
      roles: [{ assignment_id: 12, code: 'PT', active: false, revoked_at: '2026-08-30T10:00:00Z' }],
    }
    const updated = {
      ...current,
      roles: [{ assignment_id: 12, code: 'PT', active: true, granted_at: '2026-09-02T10:00:00Z' }],
    }
    capVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro({ role: 'PT', transition: 'REGRANTED' }))
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([updated]))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = current

    await expect(store.capVaiTro(7, 'PT')).resolves.toMatchObject({
      role: { transition: 'REGRANTED' },
    })

    expect(capVaiTroApi).toHaveBeenCalledTimes(1)
    expect(store.taiKhoanDaChon.roles[0].active).toBe(true)
  })

  it('revoke role active dung DELETE va khong xoa assignment history local', async () => {
    const current = { ...TAI_KHOAN, roles: [{ assignment_id: 12, code: 'ADMIN', active: true }] }
    const updated = {
      ...current,
      roles: [{ assignment_id: 12, code: 'ADMIN', active: false, revoked_at: '2026-09-02T10:00:00Z' }],
    }
    thuHoiVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro({ role: 'ADMIN', active: false, transition: 'REVOKED' }))
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([updated]))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = current

    await expect(store.thuHoiVaiTro(7, 'ADMIN')).resolves.toMatchObject({
      role: { transition: 'REVOKED' },
    })

    expect(thuHoiVaiTroApi).toHaveBeenCalledWith(7, 'ADMIN')
    expect(store.taiKhoanDaChon.roles[0].active).toBe(false)
  })

  it.each([
    ['capVaiTro', 'RECEPTIONIST', 'active'],
    ['thuHoiVaiTro', 'PT', 'revoked'],
  ])('target state %s la local no-op khong gui mutation', async (action, role, state) => {
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = {
      ...TAI_KHOAN,
      roles: [{ assignment_id: 12, code: role, active: state === 'active' }],
    }

    await expect(store[action](7, role)).resolves.toMatchObject({ unchanged: true })

    expect(capVaiTroApi).not.toHaveBeenCalled()
    expect(thuHoiVaiTroApi).not.toHaveBeenCalled()
    expect(store.ketQuaThayDoiVaiTro.transition).toBe('UNCHANGED')
  })

  it('role pending chan duplicate va khong optimistic update', async () => {
    let resolvePut
    capVaiTroApi.mockImplementationOnce(() => new Promise((resolve) => { resolvePut = resolve }))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    const request = store.capVaiTro(7, 'RECEPTIONIST')

    await Promise.resolve()
    expect(store.dangThayDoiVaiTro).toBe(true)
    expect(store.vaiTroDangXuLy).toBe('RECEPTIONIST')
    expect(store.taiKhoanDaChon.roles).toEqual(TAI_KHOAN.roles)
    await expect(store.capVaiTro(7, 'ADMIN')).resolves.toBeNull()

    resolvePut(phanHoiVaiTro())
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(TAI_KHOAN))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
    await request
    expect(capVaiTroApi).toHaveBeenCalledTimes(1)
  })

  it('cleanup detail chan role mutation cu khoi tao refetch cho route A', async () => {
    let resolveRole
    capVaiTroApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRole = resolve
    }))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    const request = store.capVaiTro(7, 'PT')

    store.xoaTaiKhoanDaChon()
    store.taiKhoanDaChon = { ...TAI_KHOAN, id: 8, name: 'Tài khoản B' }
    resolveRole(phanHoiVaiTro({ role: 'PT' }))
    await request

    expect(taiChiTietTaiKhoanApi).not.toHaveBeenCalled()
    expect(taiDanhSachTaiKhoanApi).not.toHaveBeenCalled()
    expect(store.taiKhoanDaChon).toMatchObject({ id: 8, name: 'Tài khoản B' })
    expect(store.dangThayDoiVaiTro).toBe(false)
    expect(store.thongBaoThayDoiVaiTro).toBeNull()
  })

  it.each(['ROLE_CONFLICT', 'TRAINER_PROFILE_REQUIRED', 'LAST_ACTIVE_ADMIN_PROTECTED'])(
    'role 409 %s giu code exact va refetch detail/list', async (code) => {
      capVaiTroApi.mockRejectedValueOnce({
        httpStatus: 409,
        code,
        message: 'Conflict role.',
      })
      taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(TAI_KHOAN))
      taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi())
      const store = useTaiKhoanStore()
      store.taiKhoanDaChon = TAI_KHOAN

      await store.capVaiTro(7, 'PT')

      expect(store.loiThayDoiVaiTro).toMatchObject({ httpStatus: 409, code })
      expect(taiChiTietTaiKhoanApi).toHaveBeenCalledWith(7)
      expect(taiDanhSachTaiKhoanApi).toHaveBeenCalledTimes(1)
    },
  )

  it('role 422 giu current role va khong refetch mutation outcome', async () => {
    capVaiTroApi.mockRejectedValueOnce({
      httpStatus: 422,
      code: 'INVALID_ROLE',
      message: 'Dữ liệu vai trò chưa hợp lệ.',
    })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capVaiTro(7, 'PT')

    expect(store.taiKhoanDaChon).toEqual(TAI_KHOAN)
    expect(store.loiThayDoiVaiTro).toMatchObject({ httpStatus: 422, code: 'INVALID_ROLE' })
    expect(taiChiTietTaiKhoanApi).not.toHaveBeenCalled()
  })

  it.each([403, 404])('role %s clear selected Account', async (httpStatus) => {
    capVaiTroApi.mockRejectedValueOnce({ httpStatus, message: 'Không được truy cập.' })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capVaiTro(7, 'PT')

    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.loiThayDoiVaiTro.httpStatus).toBe(httpStatus)
  })

  it('role timeout refetch effective thi xac nhan, chi gui mutation mot lan', async () => {
    capVaiTroApi.mockRejectedValueOnce({ httpStatus: null, isNetworkError: true })
    const updated = {
      ...TAI_KHOAN,
      roles: [...TAI_KHOAN.roles, { assignment_id: 12, code: 'PT', active: true }],
    }
    taiChiTietTaiKhoanApi.mockResolvedValueOnce(phanHoiChiTiet(updated))
    taiDanhSachTaiKhoanApi.mockResolvedValueOnce(phanHoi([updated]))
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await expect(store.capVaiTro(7, 'PT')).resolves.toMatchObject({ daXacNhan: true })

    expect(store.loiThayDoiVaiTro).toBeNull()
    expect(store.thongBaoThayDoiVaiTro).toContain('Backend xác nhận')
    expect(capVaiTroApi).toHaveBeenCalledTimes(1)
  })

  it('role timeout refetch old/GET fail giu unknown va khong blind retry', async () => {
    capVaiTroApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    taiChiTietTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN

    await store.capVaiTro(7, 'PT')

    expect(store.loiThayDoiVaiTro).toMatchObject({ outcomeUnknown: true })
    expect(capVaiTroApi).toHaveBeenCalledTimes(1)
    expect(taiChiTietTaiKhoanApi).toHaveBeenCalledTimes(1)
  })

  it('current Admin revoke thanh cong nhung GET mat authority thi giu 403', async () => {
    const current = { ...TAI_KHOAN, roles: [{ assignment_id: 12, code: 'ADMIN', active: true }] }
    thuHoiVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro({ role: 'ADMIN', active: false, transition: 'REVOKED' }))
    taiChiTietTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 403, message: 'Không có quyền.' })
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = current

    await store.thuHoiVaiTro(7, 'ADMIN')

    expect(store.taiKhoanDaChon).toBeNull()
    expect(store.loiThayDoiVaiTro).toMatchObject({ httpStatus: 403 })
  })

  it('role state cleanup xoa mutation outcome va sequence', () => {
    const store = useTaiKhoanStore()
    store.taiKhoanDaChon = TAI_KHOAN
    store.dangThayDoiVaiTro = true
    store.vaiTroDangXuLy = 'PT'
    store.loiThayDoiVaiTro = { httpStatus: 409 }
    store.thongBaoThayDoiVaiTro = 'old'
    store.ketQuaThayDoiVaiTro = { transition: 'GRANTED' }

    store.xoaDuLieu()

    expect(store.dangThayDoiVaiTro).toBe(false)
    expect(store.vaiTroDangXuLy).toBeNull()
    expect(store.loiThayDoiVaiTro).toBeNull()
    expect(store.thongBaoThayDoiVaiTro).toBeNull()
    expect(store.ketQuaThayDoiVaiTro).toBeNull()
  })

  it('co scoped state Member va khong ghi de list Account tong quat', async () => {
    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi([TAI_KHOAN]))
    const store = useTaiKhoanStore()
    store.danhSachTaiKhoan = [{ id: 99, name: 'Account khác' }]
    store.boLoc = { search: 'account', status: '', role: 'PT', per_page: 20 }

    await store.taiDanhSachHoiVien()

    expect(store.danhSachHoiVien).toEqual([TAI_KHOAN])
    expect(store.boLocHoiVien).toEqual({ search: '', status: '', per_page: 20 })
    expect(store.danhSachTaiKhoan).toEqual([{ id: 99, name: 'Account khác' }])
    expect(store.boLoc).toEqual({ search: 'account', status: '', role: 'PT', per_page: 20 })
    expect(taiDanhSachHoiVienApi).toHaveBeenCalledWith({
      search: '', status: '', per_page: 20, page: 1,
    })
  })

  it('Member filter chi giu search/status va reset page, khong co role selector state', async () => {
    taiDanhSachHoiVienApi.mockResolvedValueOnce(phanHoi([TAI_KHOAN], { total: 40, last_page: 2 }))
      .mockResolvedValueOnce(phanHoi([TAI_KHOAN], { current_page: 2, total: 40, last_page: 2 }))
    const store = useTaiKhoanStore()
    store.phanTrangHoiVien = { current_page: 4, per_page: 20, total: 80, last_page: 4 }

    await store.apDungBoLocHoiVien({ search: ' Anh ', status: 'BI_KHOA', role: 'PT' })

    expect(store.boLocHoiVien).toEqual({ search: 'Anh', status: 'BI_KHOA', per_page: 20 })
    expect(taiDanhSachHoiVienApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: 'BI_KHOA', per_page: 20, page: 1,
    })

    await store.chuyenTrangHoiVien(2)
    expect(taiDanhSachHoiVienApi).toHaveBeenLastCalledWith({
      search: 'Anh', status: 'BI_KHOA', per_page: 20, page: 2,
    })
  })

  it('Member list sua mot lan ve last_page khi dataset co lai', async () => {
    taiDanhSachHoiVienApi
      .mockResolvedValueOnce(phanHoi([], { current_page: 3, total: 1, last_page: 1 }))
      .mockResolvedValueOnce(phanHoi([TAI_KHOAN], { current_page: 1, total: 1, last_page: 1 }))
    const store = useTaiKhoanStore()
    store.boLocHoiVien = { search: 'Anh', status: 'HOAT_DONG', per_page: 20 }

    await store.taiDanhSachHoiVien({ boLoc: store.boLocHoiVien, trang: 3 })

    expect(taiDanhSachHoiVienApi).toHaveBeenNthCalledWith(1, {
      search: 'Anh', status: 'HOAT_DONG', per_page: 20, page: 3,
    })
    expect(taiDanhSachHoiVienApi).toHaveBeenNthCalledWith(2, {
      search: 'Anh', status: 'HOAT_DONG', per_page: 20, page: 1,
    })
    expect(store.phanTrangHoiVien.current_page).toBe(1)
    expect(store.danhSachHoiVien).toEqual([TAI_KHOAN])
  })

  it('Member detail fail-closed khi Account khong co active MEMBER role', async () => {
    taiChiTietHoiVienApi.mockResolvedValueOnce(phanHoiChiTiet({
      ...TAI_KHOAN,
      roles: [{ assignment_id: 8, code: 'PT', active: true }],
    }))
    const store = useTaiKhoanStore()

    await store.taiChiTietHoiVien(7)

    expect(store.hoiVienDaChon).toBeNull()
    expect(store.loiTaiChiTietHoiVien).toMatchObject({
      httpStatus: 404,
      code: 'MEMBER_ORIENTATION_INVALID',
      message: 'Không thể truy cập dữ liệu này.',
    })
  })

  it('Member list race chi commit response moi nhat', async () => {
    let resolveCu
    let resolveMoi
    taiDanhSachHoiVienApi
      .mockImplementationOnce(() => new Promise((resolve) => { resolveCu = resolve }))
      .mockImplementationOnce(() => new Promise((resolve) => { resolveMoi = resolve }))
    const store = useTaiKhoanStore()
    const taiCu = store.taiDanhSachHoiVien({ boLoc: { search: 'cu' }, trang: 1 })
    const taiMoi = store.taiDanhSachHoiVien({ boLoc: { search: 'moi' }, trang: 1 })

    resolveMoi(phanHoi([{ ...TAI_KHOAN, id: 8, name: 'Hội viên mới' }]))
    await taiMoi
    resolveCu(phanHoi([{ ...TAI_KHOAN, id: 7, name: 'Hội viên cũ' }]))
    await taiCu

    expect(store.danhSachHoiVien).toEqual([{ ...TAI_KHOAN, id: 8, name: 'Hội viên mới' }])
    expect(store.boLocHoiVien.search).toBe('moi')
  })

  it('Member detail race khong cho response cu ghi de detail moi', async () => {
    let resolveCu
    let resolveMoi
    taiChiTietHoiVienApi
      .mockImplementationOnce(() => new Promise((resolve) => { resolveCu = resolve }))
      .mockImplementationOnce(() => new Promise((resolve) => { resolveMoi = resolve }))
    const store = useTaiKhoanStore()
    const taiCu = store.taiChiTietHoiVien(7)
    const taiMoi = store.taiChiTietHoiVien(8)

    resolveMoi(phanHoiChiTiet({ ...TAI_KHOAN, id: 8, name: 'Hội viên mới' }))
    await taiMoi
    resolveCu(phanHoiChiTiet({ ...TAI_KHOAN, id: 7, name: 'Hội viên cũ' }))
    await taiCu

    expect(store.hoiVienDaChon).toMatchObject({ id: 8, name: 'Hội viên mới' })
  })

  it('Member cleanup chi xoa scoped state va giu Account tong quat', () => {
    const store = useTaiKhoanStore()
    store.danhSachTaiKhoan = [{ id: 99, name: 'Account khác' }]
    store.boLoc = { search: 'account', status: '', role: 'ADMIN', per_page: 20 }
    store.danhSachHoiVien = [TAI_KHOAN]
    store.boLocHoiVien = { search: 'member', status: 'HOAT_DONG', per_page: 20 }
    store.hoiVienDaChon = TAI_KHOAN
    store.loiTaiHoiVien = { httpStatus: 503 }
    store.loiTaiChiTietHoiVien = { httpStatus: 404 }

    store.xoaDuLieuHoiVien()

    expect(store.danhSachHoiVien).toEqual([])
    expect(store.boLocHoiVien).toEqual({ search: '', status: '', per_page: 20 })
    expect(store.hoiVienDaChon).toBeNull()
    expect(store.loiTaiHoiVien).toBeNull()
    expect(store.loiTaiChiTietHoiVien).toBeNull()
    expect(store.danhSachTaiKhoan).toEqual([{ id: 99, name: 'Account khác' }])
    expect(store.boLoc).toEqual({ search: 'account', status: '', role: 'ADMIN', per_page: 20 })
  })

  it('Member detail cleanup giu nguyen list va applied filter', () => {
    const store = useTaiKhoanStore()
    store.danhSachHoiVien = [TAI_KHOAN]
    store.boLocHoiVien = { search: 'member', status: 'HOAT_DONG', per_page: 20 }
    store.hoiVienDaChon = TAI_KHOAN
    store.loiTaiChiTietHoiVien = { httpStatus: 503 }

    store.xoaChiTietHoiVien()

    expect(store.hoiVienDaChon).toBeNull()
    expect(store.loiTaiChiTietHoiVien).toBeNull()
    expect(store.danhSachHoiVien).toEqual([TAI_KHOAN])
    expect(store.boLocHoiVien).toEqual({ search: 'member', status: 'HOAT_DONG', per_page: 20 })
  })

  it('co scoped state Receptionist va list fixed role khong ghi de Account/Member', async () => {
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoi([NHAN_VIEN_LE_TAN]))
    const store = useTaiKhoanStore()
    store.danhSachTaiKhoan = [{ id: 99, name: 'Account khác' }]
    store.danhSachHoiVien = [{ id: 88, name: 'Hội viên khác' }]

    await store.apDungBoLocNhanVienLeTan({
      search: ' Le tan ', status: 'HOAT_DONG', role: 'ADMIN', branch_id: 4,
    })

    expect(store.danhSachNhanVienLeTan).toEqual([NHAN_VIEN_LE_TAN])
    expect(store.boLocNhanVienLeTan).toEqual({ search: 'Le tan', status: 'HOAT_DONG', per_page: 20 })
    expect(taiDanhSachNhanVienLeTanApi).toHaveBeenCalledWith({
      search: 'Le tan', status: 'HOAT_DONG', per_page: 20, page: 1,
    })
    expect(store.danhSachTaiKhoan).toEqual([{ id: 99, name: 'Account khác' }])
    expect(store.danhSachHoiVien).toEqual([{ id: 88, name: 'Hội viên khác' }])
  })

  it('Receptionist list sua mot lan ve last_page khi dataset co lai', async () => {
    taiDanhSachNhanVienLeTanApi
      .mockResolvedValueOnce(phanHoi([], { current_page: 2, total: 1, last_page: 1 }))
      .mockResolvedValueOnce(phanHoi([NHAN_VIEN_LE_TAN], { current_page: 1, total: 1, last_page: 1 }))
    const store = useTaiKhoanStore()
    store.boLocNhanVienLeTan = { search: 'Lễ tân', status: '', per_page: 20 }

    await store.taiDanhSachNhanVienLeTan({ boLoc: store.boLocNhanVienLeTan, trang: 2 })

    expect(taiDanhSachNhanVienLeTanApi).toHaveBeenNthCalledWith(1, {
      search: 'Lễ tân', status: '', per_page: 20, page: 2,
    })
    expect(taiDanhSachNhanVienLeTanApi).toHaveBeenNthCalledWith(2, {
      search: 'Lễ tân', status: '', per_page: 20, page: 1,
    })
    expect(store.phanTrangNhanVienLeTan.current_page).toBe(1)
    expect(store.danhSachNhanVienLeTan).toEqual([NHAN_VIEN_LE_TAN])
  })

  it('Receptionist detail chi commit active role va fail-closed khi role khong active', async () => {
    taiChiTietNhanVienLeTanApi
      .mockResolvedValueOnce(phanHoiChiTiet(NHAN_VIEN_LE_TAN))
      .mockResolvedValueOnce(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, roles: [{ code: 'RECEPTIONIST', active: false }] }))
    const store = useTaiKhoanStore()

    await store.taiChiTietNhanVienLeTan(7)
    expect(store.nhanVienLeTanDaChon).toEqual(NHAN_VIEN_LE_TAN)

    await store.taiChiTietNhanVienLeTan(7)
    expect(store.nhanVienLeTanDaChon).toBeNull()
    expect(store.loiTaiChiTietNhanVienLeTan).toMatchObject({
      httpStatus: 404,
      code: 'RECEPTIONIST_ORIENTATION_INVALID',
      message: 'Không thể truy cập dữ liệu này.',
    })
  })

  it('Receptionist list va detail race chi commit response moi nhat', async () => {
    let resolveListCu
    let resolveListMoi
    taiDanhSachNhanVienLeTanApi
      .mockImplementationOnce(() => new Promise((resolve) => { resolveListCu = resolve }))
      .mockImplementationOnce(() => new Promise((resolve) => { resolveListMoi = resolve }))
    const store = useTaiKhoanStore()
    const listCu = store.taiDanhSachNhanVienLeTan({ boLoc: { search: 'cu' }, trang: 1 })
    const listMoi = store.taiDanhSachNhanVienLeTan({ boLoc: { search: 'moi' }, trang: 1 })
    resolveListMoi(phanHoi([{ ...NHAN_VIEN_LE_TAN, id: 8, name: 'Lễ tân mới' }]))
    await listMoi
    resolveListCu(phanHoi([{ ...NHAN_VIEN_LE_TAN, id: 7, name: 'Lễ tân cũ' }]))
    await listCu
    expect(store.danhSachNhanVienLeTan).toMatchObject([{ id: 8, name: 'Lễ tân mới' }])
    expect(store.boLocNhanVienLeTan.search).toBe('moi')

    let resolveDetailCu
    let resolveDetailMoi
    taiChiTietNhanVienLeTanApi
      .mockImplementationOnce(() => new Promise((resolve) => { resolveDetailCu = resolve }))
      .mockImplementationOnce(() => new Promise((resolve) => { resolveDetailMoi = resolve }))
    const detailCu = store.taiChiTietNhanVienLeTan(7)
    const detailMoi = store.taiChiTietNhanVienLeTan(8)
    resolveDetailMoi(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, id: 8, name: 'Lễ tân B' }))
    await detailMoi
    resolveDetailCu(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, id: 7, name: 'Lễ tân A' }))
    await detailCu
    expect(store.nhanVienLeTanDaChon).toMatchObject({ id: 8, name: 'Lễ tân B' })
  })

  it('Receptionist status mutation dung PATCH exact, khong optimistic va refetch scoped detail/list', async () => {
    capNhatTrangThaiTaiKhoanApi.mockResolvedValueOnce({ data: { id: 7, status: 'BI_KHOA' } })
    taiChiTietNhanVienLeTanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, status: 'BI_KHOA' }))
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoi([{ ...NHAN_VIEN_LE_TAN, status: 'BI_KHOA' }]))
    const store = useTaiKhoanStore()
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN
    store.taiKhoanDaChon = TAI_KHOAN
    store.danhSachTaiKhoan = [TAI_KHOAN]

    const request = store.capNhatTrangThaiNhanVienLeTan(7, 'BI_KHOA')
    expect(store.nhanVienLeTanDaChon.status).toBe('HOAT_DONG')
    await request

    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledWith(7, 'BI_KHOA')
    expect(store.nhanVienLeTanDaChon.status).toBe('BI_KHOA')
    expect(store.danhSachTaiKhoan).toEqual([TAI_KHOAN])
    expect(store.thongBaoCapNhatTrangThaiNhanVienLeTan).toContain('Đã cập nhật')
  })

  it('cleanup Receptionist detail chan status mutation cu khoi tao refetch route A', async () => {
    let resolvePatch
    capNhatTrangThaiTaiKhoanApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolvePatch = resolve
    }))
    const store = useTaiKhoanStore()
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN
    const request = store.capNhatTrangThaiNhanVienLeTan(7, 'BI_KHOA')

    store.xoaChiTietNhanVienLeTan()
    store.nhanVienLeTanDaChon = { ...NHAN_VIEN_LE_TAN, id: 8, name: 'Lễ tân B' }
    resolvePatch({ data: { id: 7, status: 'BI_KHOA' } })
    await request

    expect(taiChiTietNhanVienLeTanApi).not.toHaveBeenCalled()
    expect(taiDanhSachNhanVienLeTanApi).not.toHaveBeenCalled()
    expect(store.nhanVienLeTanDaChon).toMatchObject({ id: 8, name: 'Lễ tân B' })
    expect(store.dangCapNhatTrangThaiNhanVienLeTan).toBe(false)
  })

  it('Receptionist status 422 khong goi mutation va 5xx/network unknown refetch khong blind retry', async () => {
    const store = useTaiKhoanStore()
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN

    await store.capNhatTrangThaiNhanVienLeTan(7, 'ACTIVE')
    expect(store.loiCapNhatTrangThaiNhanVienLeTan).toMatchObject({ httpStatus: 422 })
    expect(capNhatTrangThaiTaiKhoanApi).not.toHaveBeenCalled()

    capNhatTrangThaiTaiKhoanApi.mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.' })
    taiChiTietNhanVienLeTanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, status: 'BI_KHOA' }))
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoi([{ ...NHAN_VIEN_LE_TAN, status: 'BI_KHOA' }]))
    await store.capNhatTrangThaiNhanVienLeTan(7, 'BI_KHOA')

    expect(capNhatTrangThaiTaiKhoanApi).toHaveBeenCalledTimes(1)
    expect(store.thongBaoCapNhatTrangThaiNhanVienLeTan).toBe('Trạng thái đã được Backend xác nhận.')
  })

  it('Receptionist revoke dung DELETE fixed role, refetch scope exit va khong co grant/regrant', async () => {
    thuHoiVaiTroApi.mockResolvedValueOnce(phanHoiVaiTro({ role: 'RECEPTIONIST', active: false, transition: 'REVOKED' }))
    taiChiTietNhanVienLeTanApi.mockResolvedValueOnce(phanHoiChiTiet({ ...NHAN_VIEN_LE_TAN, roles: [] }))
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoi([]))
    const store = useTaiKhoanStore()
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN

    const ketQua = await store.thuHoiVaiTroLeTan(7)

    expect(thuHoiVaiTroApi).toHaveBeenCalledWith(7, 'RECEPTIONIST')
    expect(thuHoiVaiTroApi.mock.calls[0]).toHaveLength(2)
    expect(store.nhanVienLeTanDaChon).toBeNull()
    expect(ketQua).toMatchObject({ scopeExited: true, role: { role: 'RECEPTIONIST', transition: 'REVOKED' } })
    expect(store.thongBaoThuHoiVaiTroNhanVienLeTan).toContain('Đã thu hồi')
  })

  it('cleanup Receptionist detail chan revoke cu khoi tao refetch route A', async () => {
    let resolveRevoke
    thuHoiVaiTroApi.mockImplementationOnce(() => new Promise((resolve) => {
      resolveRevoke = resolve
    }))
    const store = useTaiKhoanStore()
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN
    const request = store.thuHoiVaiTroLeTan(7)

    store.xoaChiTietNhanVienLeTan()
    store.nhanVienLeTanDaChon = { ...NHAN_VIEN_LE_TAN, id: 8, name: 'Lễ tân B' }
    resolveRevoke(phanHoiVaiTro())
    await request

    expect(taiChiTietNhanVienLeTanApi).not.toHaveBeenCalled()
    expect(taiDanhSachNhanVienLeTanApi).not.toHaveBeenCalled()
    expect(store.nhanVienLeTanDaChon).toMatchObject({ id: 8, name: 'Lễ tân B' })
    expect(store.dangThuHoiVaiTroNhanVienLeTan).toBe(false)
  })

  it('Receptionist revoke 409 refetch detail/list va giu code Backend exact', async () => {
    thuHoiVaiTroApi.mockRejectedValueOnce({ httpStatus: 409, code: 'LAST_ACTIVE_ADMIN_PROTECTED', message: 'Không thể.' })
    taiChiTietNhanVienLeTanApi.mockResolvedValueOnce(phanHoiChiTiet(NHAN_VIEN_LE_TAN))
    taiDanhSachNhanVienLeTanApi.mockResolvedValueOnce(phanHoi([NHAN_VIEN_LE_TAN]))
    const store = useTaiKhoanStore()
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN

    await store.thuHoiVaiTroLeTan(7)

    expect(store.loiThuHoiVaiTroNhanVienLeTan).toMatchObject({
      httpStatus: 409,
      code: 'LAST_ACTIVE_ADMIN_PROTECTED',
    })
    expect(store.nhanVienLeTanDaChon).toEqual(NHAN_VIEN_LE_TAN)
  })

  it('Receptionist cleanup xoa scoped state nhung giu Account va Member', () => {
    const store = useTaiKhoanStore()
    store.danhSachNhanVienLeTan = [NHAN_VIEN_LE_TAN]
    store.nhanVienLeTanDaChon = NHAN_VIEN_LE_TAN
    store.danhSachTaiKhoan = [TAI_KHOAN]
    store.danhSachHoiVien = [{ id: 8, name: 'Hội viên' }]
    store.loiTaiNhanVienLeTan = { httpStatus: 503 }

    store.xoaDuLieuNhanVienLeTan()

    expect(store.danhSachNhanVienLeTan).toEqual([])
    expect(store.nhanVienLeTanDaChon).toBeNull()
    expect(store.loiTaiNhanVienLeTan).toBeNull()
    expect(store.danhSachTaiKhoan).toEqual([TAI_KHOAN])
    expect(store.danhSachHoiVien).toEqual([{ id: 8, name: 'Hội viên' }])
  })
})
