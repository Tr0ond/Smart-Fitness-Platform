import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import ketNoiApi, { datBoDocToken, datXuLy401 } from '../services/api.js'
import {
  KHOA_ACTOR_PHIEN,
  KHOA_TOKEN_PHIEN,
  useXacThucStore,
} from './xac_thuc.store.js'
import { useTaiKhoanStore } from './tai_khoan.store.js'
import { useThanhToanStore } from './thanh_toan.store.js'
import { useDeXuatStore } from './de_xuat.store.js'
import { useHoiVienPtStore } from './hoi_vien_pt.store.js'
import {
  dangNhap as dangNhapApi,
  dangXuat as dangXuatApi,
  taiThongTinNguoiDung as taiThongTinNguoiDungApi,
} from '../services/xac_thuc.api.js'

vi.mock('../services/xac_thuc.api.js', () => ({
  dangNhap: vi.fn(),
  dangXuat: vi.fn(),
  taiThongTinNguoiDung: vi.fn(),
}))

const TOKEN_HOP_LE = 'a'.repeat(64)
const NGUOI_DUNG_PT = {
  id: 21,
  name: 'PT Test',
  email: 'pt@example.com',
  status: 'HOAT_DONG',
  roles: ['PT'],
}

function phanHoiDangNhap(nguoiDung = NGUOI_DUNG_PT, token = TOKEN_HOP_LE) {
  return {
    data: {
      access_token: token,
      token_type: 'Bearer',
      expires_at: '2026-09-30T00:00:00.000000Z',
      user: nguoiDung,
    },
  }
}

function phanHoiMe(nguoiDung = NGUOI_DUNG_PT) {
  return { data: nguoiDung }
}

function datTokenTrongPhien(store, token = TOKEN_HOP_LE) {
  store.token = token
  sessionStorage.setItem(KHOA_TOKEN_PHIEN, token)
}

describe('xac_thuc.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    sessionStorage.clear()
    localStorage.clear()
    vi.clearAllMocks()
    datBoDocToken(null)
    datXuLy401(null)
  })

  it('login success luu token vao memory va sessionStorage', async () => {
    dangNhapApi.mockResolvedValue(phanHoiDangNhap())
    taiThongTinNguoiDungApi.mockResolvedValue(phanHoiMe())
    const store = useXacThucStore()

    await store.dangNhap({ email: 'pt@example.com', password: 'MatKhau!123' })

    expect(store.token).toBe(TOKEN_HOP_LE)
    expect(sessionStorage.getItem(KHOA_TOKEN_PHIEN)).toBe(TOKEN_HOP_LE)
    expect(store.nguoiDung).toEqual(NGUOI_DUNG_PT)
    expect(store.vaiTro).toEqual(['PT'])
  })

  it('login goi /me sau khi luu token va khong persist password', async () => {
    dangNhapApi.mockResolvedValue(phanHoiDangNhap())
    taiThongTinNguoiDungApi.mockResolvedValue(phanHoiMe())
    const ghiLocalStorage = vi.spyOn(localStorage, 'setItem')
    const store = useXacThucStore()

    await store.dangNhap({ email: 'pt@example.com', password: 'MatKhau!123' })

    expect(taiThongTinNguoiDungApi).toHaveBeenCalledTimes(1)
    expect(sessionStorage.getItem(KHOA_TOKEN_PHIEN)).not.toContain('MatKhau!123')
    expect(sessionStorage.getItem(KHOA_ACTOR_PHIEN)).toBeNull()
    expect(ghiLocalStorage).not.toHaveBeenCalled()
  })

  it('login co token nhung /me fail thi clear token va session', async () => {
    const loi = { httpStatus: 401, code: null, message: 'Chưa xác thực.' }
    dangNhapApi.mockResolvedValue(phanHoiDangNhap())
    taiThongTinNguoiDungApi.mockRejectedValue(loi)
    const store = useXacThucStore()

    await expect(store.dangNhap({ email: 'pt@example.com', password: 'MatKhau!123' })).rejects.toBe(loi)

    expect(store.token).toBeNull()
    expect(store.nguoiDung).toBeNull()
    expect(sessionStorage.getItem(KHOA_TOKEN_PHIEN)).toBeNull()
  })

  it('restore khong co token thi khong goi /me va ket thuc ro rang', async () => {
    const store = useXacThucStore()

    await expect(store.khoiPhucPhien()).resolves.toBe(false)

    expect(taiThongTinNguoiDungApi).not.toHaveBeenCalled()
    expect(store.token).toBeNull()
    expect(store.daKhoiPhucPhien).toBe(true)
    expect(store.dangKhoiPhucPhien).toBe(false)
  })

  it('restore co token hop le goi /me va restore actor chi khi role con hop le', async () => {
    sessionStorage.setItem(KHOA_TOKEN_PHIEN, TOKEN_HOP_LE)
    sessionStorage.setItem(KHOA_ACTOR_PHIEN, 'PT')
    taiThongTinNguoiDungApi.mockResolvedValue(phanHoiMe())
    const store = useXacThucStore()

    await expect(store.khoiPhucPhien()).resolves.toBe(true)

    expect(taiThongTinNguoiDungApi).toHaveBeenCalledTimes(1)
    expect(store.token).toBe(TOKEN_HOP_LE)
    expect(store.nguoiDung).toEqual(NGUOI_DUNG_PT)
    expect(store.vaiTroDangDung).toBe('PT')
    expect(store.daKhoiPhucPhien).toBe(true)
    expect(store.dangKhoiPhucPhien).toBe(false)
  })

  it('restore token invalid hoac 401 thi clear local session', async () => {
    sessionStorage.setItem(KHOA_TOKEN_PHIEN, TOKEN_HOP_LE)
    sessionStorage.setItem(KHOA_ACTOR_PHIEN, 'PT')
    taiThongTinNguoiDungApi.mockRejectedValue({ httpStatus: 401, code: null, message: 'Chưa xác thực.' })
    const store = useXacThucStore()

    await expect(store.khoiPhucPhien()).resolves.toBe(false)

    expect(store.token).toBeNull()
    expect(store.nguoiDung).toBeNull()
    expect(store.vaiTro).toEqual([])
    expect(store.vaiTroDangDung).toBeNull()
    expect(sessionStorage.length).toBe(0)
    expect(store.daKhoiPhucPhien).toBe(true)
  })

  it.each([
    [{ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.', isNetworkError: false }],
    [{ httpStatus: null, message: 'Không thể kết nối đến máy chủ.', isNetworkError: true }],
  ])('restore /me loi tam thoi giu token nhung khoa authority trong memory', async (loiTamThoi) => {
    sessionStorage.setItem(KHOA_TOKEN_PHIEN, TOKEN_HOP_LE)
    sessionStorage.setItem(KHOA_ACTOR_PHIEN, 'PT')
    taiThongTinNguoiDungApi.mockRejectedValue(loiTamThoi)
    const store = useXacThucStore()
    store.nguoiDung = NGUOI_DUNG_PT
    store.vaiTro = ['PT']
    store.vaiTroDangDung = 'PT'

    await expect(store.khoiPhucPhien()).resolves.toBe(false)

    expect(store.token).toBe(TOKEN_HOP_LE)
    expect(sessionStorage.getItem(KHOA_TOKEN_PHIEN)).toBe(TOKEN_HOP_LE)
    expect(sessionStorage.getItem(KHOA_ACTOR_PHIEN)).toBe('PT')
    expect(store.nguoiDung).toBeNull()
    expect(store.vaiTro).toEqual([])
    expect(store.vaiTroDangDung).toBeNull()
    expect(store.loiKhoiPhucPhien).toMatchObject({ message: loiTamThoi.message })
    expect(store.daKhoiPhucPhien).toBe(true)
  })

  it('cho phep retry /me cung token va khoi phuc actor sau loi tam thoi', async () => {
    sessionStorage.setItem(KHOA_TOKEN_PHIEN, TOKEN_HOP_LE)
    sessionStorage.setItem(KHOA_ACTOR_PHIEN, 'PT')
    taiThongTinNguoiDungApi
      .mockRejectedValueOnce({ httpStatus: 503, message: 'Máy chủ đang gặp sự cố.', isNetworkError: false })
      .mockResolvedValueOnce(phanHoiMe())
    const store = useXacThucStore()

    await expect(store.khoiPhucPhien()).resolves.toBe(false)
    await expect(store.khoiPhucPhien()).resolves.toBe(true)

    expect(taiThongTinNguoiDungApi).toHaveBeenCalledTimes(2)
    expect(store.token).toBe(TOKEN_HOP_LE)
    expect(store.nguoiDung).toEqual(NGUOI_DUNG_PT)
    expect(store.vaiTro).toEqual(['PT'])
    expect(store.vaiTroDangDung).toBe('PT')
    expect(store.loiKhoiPhucPhien).toBeNull()
  })

  it('login da nhan token nhung /me loi tam thoi thi giu token va chuyen sang trang thai retry', async () => {
    dangNhapApi.mockResolvedValue(phanHoiDangNhap())
    taiThongTinNguoiDungApi.mockRejectedValue({
      httpStatus: 503,
      message: 'Máy chủ đang gặp sự cố.',
      isNetworkError: false,
    })
    const store = useXacThucStore()

    await expect(store.dangNhap({ email: 'pt@example.com', password: 'MatKhau!123' })).resolves.toBeNull()

    expect(store.token).toBe(TOKEN_HOP_LE)
    expect(sessionStorage.getItem(KHOA_TOKEN_PHIEN)).toBe(TOKEN_HOP_LE)
    expect(store.nguoiDung).toBeNull()
    expect(store.loiKhoiPhucPhien).toMatchObject({ httpStatus: 503 })
    expect(store.daKhoiPhucPhien).toBe(true)
  })

  it('logout success luon clear local auth state', async () => {
    const store = useXacThucStore()
    datTokenTrongPhien(store)
    store.nguoiDung = NGUOI_DUNG_PT
    store.vaiTro = ['PT']
    store.vaiTroDangDung = 'PT'
    sessionStorage.setItem(KHOA_ACTOR_PHIEN, 'PT')
    dangXuatApi.mockResolvedValue({ data: null })

    await expect(store.dangXuat()).resolves.toBe(true)

    expect(dangXuatApi).toHaveBeenCalledTimes(1)
    expect(store.token).toBeNull()
    expect(store.nguoiDung).toBeNull()
    expect(store.vaiTro).toEqual([])
    expect(store.vaiTroDangDung).toBeNull()
    expect(sessionStorage.length).toBe(0)
  })

  it('logout va doi actor don dep account list memory', () => {
    const authStore = useXacThucStore()
    const accountStore = useTaiKhoanStore()
    accountStore.danhSachTaiKhoan = [{ id: 7, name: 'Tai khoan cu' }]
    accountStore.boLoc = { search: 'cu', status: '', role: '', per_page: 20 }
    accountStore.taiKhoanDaChon = { id: 7, name: 'Tai khoan cu', status: 'HOAT_DONG', roles: [] }
    accountStore.dangTaiChiTiet = true
    accountStore.loiTaiChiTiet = { message: 'Loi detail cu' }
    accountStore.dangCapNhatTrangThai = true
    accountStore.loiCapNhatTrangThai = { message: 'Loi mutation cu' }
    accountStore.thongBaoCapNhatTrangThai = 'Thong bao cu'
    accountStore.danhSachNhanVienLeTan = [{ id: 9, name: 'Le tan cu' }]
    accountStore.nhanVienLeTanDaChon = { id: 9, name: 'Le tan cu' }
    accountStore.dangThuHoiVaiTroNhanVienLeTan = true

    authStore.xoaPhienDangNhap()

    expect(accountStore.danhSachTaiKhoan).toEqual([])
    expect(accountStore.boLoc).toEqual({ search: '', status: '', role: '', per_page: 20 })
    expect(accountStore.taiKhoanDaChon).toBeNull()
    expect(accountStore.dangTaiChiTiet).toBe(false)
    expect(accountStore.loiTaiChiTiet).toBeNull()
    expect(accountStore.dangCapNhatTrangThai).toBe(false)
    expect(accountStore.loiCapNhatTrangThai).toBeNull()
    expect(accountStore.thongBaoCapNhatTrangThai).toBeNull()
    expect(accountStore.danhSachNhanVienLeTan).toEqual([])
    expect(accountStore.nhanVienLeTanDaChon).toBeNull()
    expect(accountStore.dangThuHoiVaiTroNhanVienLeTan).toBe(false)

    authStore.vaiTro = ['ADMIN', 'PT']
    authStore.chonVaiTroDangDung('ADMIN')
    accountStore.danhSachTaiKhoan = [{ id: 8, name: 'Tai khoan admin' }]
    accountStore.danhSachNhanVienLeTan = [{ id: 10, name: 'Le tan admin' }]
    authStore.chonVaiTroDangDung('PT')

    expect(accountStore.danhSachTaiKhoan).toEqual([])
    expect(accountStore.danhSachNhanVienLeTan).toEqual([])
  })

  it('logout va doi actor don dep Payment list/detail va hai queue trong memory', () => {
    const authStore = useXacThucStore()
    const paymentStore = useThanhToanStore()
    paymentStore.danhSachThanhToan = [{ payment_id: 7, status: 'THANH_CONG' }]
    paymentStore.chiTietThanhToan = { payment_id: 7, status: 'THANH_CONG' }
    paymentStore.danhSachCanDoiSoat = [{ payment_id: 7, status: 'CAN_DOI_SOAT' }]
    paymentStore.danhSachSuKienThanhToan = [{ event_id: 71, payment_id: null }]
    paymentStore.loiThanhToan = { message: 'loi cu' }
    paymentStore.loiChiTiet = { message: 'loi detail cu' }
    paymentStore.loiCanDoiSoat = { message: 'loi queue cu' }
    paymentStore.loiSuKienThanhToan = { message: 'loi event cu' }

    authStore.xoaPhienDangNhap()

    expect(paymentStore.danhSachThanhToan).toEqual([])
    expect(paymentStore.chiTietThanhToan).toBeNull()
    expect(paymentStore.danhSachCanDoiSoat).toEqual([])
    expect(paymentStore.danhSachSuKienThanhToan).toEqual([])
    expect(paymentStore.loiThanhToan).toBeNull()
    expect(paymentStore.loiChiTiet).toBeNull()
    expect(paymentStore.loiCanDoiSoat).toBeNull()
    expect(paymentStore.loiSuKienThanhToan).toBeNull()
  })

  it('logout va doi actor xoa Member direct history va proposal draft/action', () => {
    const authStore = useXacThucStore()
    const memberStore = useHoiVienPtStore()
    const proposalStore = useDeXuatStore()
    memberStore.chonHoiVien(7)
    memberStore.chiTietHoiVien = { member: { id: 7 }, assignment: { id: 71, is_current: true } }
    memberStore.lichSuBuoiHuanLuyen = [{ history_id: 91, member_id: 7 }]
    memberStore.thaoTacBuoiHuanLuyenDangCho = { memberId: 7, outcomeUnknown: true }
    proposalStore.chonHoiVien(7)
    proposalStore.danhSachDeXuat = [{ id: 42, title: 'Private proposal' }]
    proposalStore.banNhap.title = 'Private draft'
    proposalStore.thaoTacDangCho = { memberId: 7, idempotencyKey: 'pending-key', outcomeUnknown: true }

    authStore.xoaPhienDangNhap()

    expect(memberStore.hoiVienDaChonId).toBeNull()
    expect(memberStore.lichSuBuoiHuanLuyen).toEqual([])
    expect(memberStore.thaoTacBuoiHuanLuyenDangCho).toBeNull()
    expect(proposalStore.memberId).toBeNull()
    expect(proposalStore.danhSachDeXuat).toEqual([])
    expect(proposalStore.banNhap.title).toBe('')
    expect(proposalStore.thaoTacDangCho).toBeNull()

    authStore.vaiTro = ['ADMIN', 'PT']
    authStore.chonVaiTroDangDung('ADMIN')
    memberStore.chonHoiVien(8)
    memberStore.lichSuBuoiHuanLuyen = [{ history_id: 92, member_id: 8 }]
    proposalStore.chonHoiVien(8)
    proposalStore.banNhap.title = 'Draft before actor switch'
    authStore.chonVaiTroDangDung('PT')

    expect(memberStore.hoiVienDaChonId).toBeNull()
    expect(memberStore.lichSuBuoiHuanLuyen).toEqual([])
    expect(proposalStore.memberId).toBeNull()
    expect(proposalStore.banNhap.title).toBe('')
  })

  it('logout network hoac 5xx fail van clear local session', async () => {
    const store = useXacThucStore()
    datTokenTrongPhien(store)
    dangXuatApi.mockRejectedValue({ httpStatus: 503, code: 'SERVICE_UNAVAILABLE' })

    await expect(store.dangXuat()).resolves.toBe(false)

    expect(store.token).toBeNull()
    expect(sessionStorage.getItem(KHOA_TOKEN_PHIEN)).toBeNull()
  })

  it('Axios request dung token hien tai va sau logout khong dung token cu', async () => {
    const store = useXacThucStore()
    datTokenTrongPhien(store)
    store.dangKyCoCheAuth()
    const cacHeader = []
    const adapter = vi.fn(async (config) => {
      cacHeader.push(config.headers.Authorization ?? null)
      return { data: { ok: true }, status: 200, statusText: 'OK', headers: {}, config }
    })

    await ketNoiApi.get('/protected', { adapter })
    store.xoaPhienDangNhap()
    await ketNoiApi.get('/protected', { adapter })

    expect(cacHeader).toEqual([`Bearer ${TOKEN_HOP_LE}`, null])
  })

  it('401 authenticated response clear auth state, con 403 khong logout', async () => {
    const store = useXacThucStore()
    const accountStore = useTaiKhoanStore()
    accountStore.danhSachNhanVienLeTan = [{ id: 9, name: 'Le tan cu' }]
    accountStore.nhanVienLeTanDaChon = { id: 9, name: 'Le tan cu' }
    datTokenTrongPhien(store)
    store.vaiTroDangDung = 'PT'
    const dieuPhoiSau401 = vi.fn()
    store.dangKyCoCheAuth(dieuPhoiSau401)
    store.nguoiDung = NGUOI_DUNG_PT
    store.vaiTro = ['PT']
    const adapter401 = vi.fn(async (config) => Promise.reject({
      config,
      response: { status: 401, data: { message: 'Chưa xác thực.' }, headers: {} },
    }))

    await expect(ketNoiApi.get('/protected', { adapter: adapter401 })).rejects.toMatchObject({ httpStatus: 401 })
    expect(store.token).toBeNull()
    expect(store.nguoiDung).toBeNull()
    expect(accountStore.danhSachNhanVienLeTan).toEqual([])
    expect(accountStore.nhanVienLeTanDaChon).toBeNull()
    expect(dieuPhoiSau401).toHaveBeenCalledWith('PT')

    datTokenTrongPhien(store)
    store.nguoiDung = NGUOI_DUNG_PT
    store.vaiTro = ['PT']
    const adapter403 = vi.fn(async (config) => Promise.reject({
      config,
      response: { status: 403, data: { message: 'Không có quyền truy cập.' }, headers: {} },
    }))

    await expect(ketNoiApi.get('/protected', { adapter: adapter403 })).rejects.toMatchObject({ httpStatus: 403 })
    expect(store.token).toBe(TOKEN_HOP_LE)
    expect(store.nguoiDung).toEqual(NGUOI_DUNG_PT)
  })

  it('late 401 cua token cu khong xoa phien moi', async () => {
    const tokenMoi = 'b'.repeat(64)
    const store = useXacThucStore()
    datTokenTrongPhien(store)
    store.nguoiDung = NGUOI_DUNG_PT
    store.vaiTro = ['PT']
    store.vaiTroDangDung = 'PT'
    store.dangKyCoCheAuth()
    let tuChoiRequestCu
    const adapter = vi.fn((config) => new Promise((_resolve, reject) => {
      tuChoiRequestCu = () => reject({
        config,
        response: { status: 401, data: { message: 'Chưa xác thực.' }, headers: {} },
      })
    }))
    const requestCu = ketNoiApi.get('/protected-slow', { adapter })

    await vi.waitFor(() => expect(adapter).toHaveBeenCalledTimes(1))
    store.token = tokenMoi
    sessionStorage.setItem(KHOA_TOKEN_PHIEN, tokenMoi)
    tuChoiRequestCu()

    await expect(requestCu).rejects.toMatchObject({ httpStatus: 401 })
    expect(store.token).toBe(tokenMoi)
    expect(sessionStorage.getItem(KHOA_TOKEN_PHIEN)).toBe(tokenMoi)
    expect(store.nguoiDung).toEqual(NGUOI_DUNG_PT)
  })

  it('me thay the stale roles va clear active actor bi revoke', async () => {
    const store = useXacThucStore()
    datTokenTrongPhien(store)
    store.vaiTroDangDung = 'PT'
    sessionStorage.setItem(KHOA_ACTOR_PHIEN, 'PT')
    taiThongTinNguoiDungApi.mockResolvedValue(phanHoiMe({ ...NGUOI_DUNG_PT, roles: ['ADMIN'] }))

    await store.taiThongTinNguoiDung()

    expect(store.vaiTro).toEqual(['ADMIN'])
    expect(store.vaiTroDangDung).toBeNull()
    expect(sessionStorage.getItem(KHOA_ACTOR_PHIEN)).toBeNull()
  })

  it('multi-role khong tu dong uu tien actor va MEMBER-only khong invent Web role', async () => {
    const store = useXacThucStore()
    datTokenTrongPhien(store)
    taiThongTinNguoiDungApi.mockResolvedValue(phanHoiMe({ ...NGUOI_DUNG_PT, roles: ['ADMIN', 'PT', 'RECEPTIONIST'] }))

    await store.taiThongTinNguoiDung()

    expect(store.vaiTro).toEqual(['ADMIN', 'PT', 'RECEPTIONIST'])
    expect(store.vaiTroDangDung).toBeNull()

    taiThongTinNguoiDungApi.mockResolvedValue(phanHoiMe({ ...NGUOI_DUNG_PT, roles: ['MEMBER'] }))
    await store.taiThongTinNguoiDung()

    expect(store.vaiTro).toEqual(['MEMBER'])
    expect(store.vaiTroDangDung).toBeNull()
  })

  it('chi cho phep chon actor la Web role dang co va khong cho role tuy y', async () => {
    const store = useXacThucStore()
    datTokenTrongPhien(store)
    taiThongTinNguoiDungApi.mockResolvedValue(phanHoiMe({ ...NGUOI_DUNG_PT, roles: ['ADMIN', 'PT'] }))
    await store.taiThongTinNguoiDung()

    expect(store.chonVaiTroDangDung('PT')).toBe('PT')
    expect(sessionStorage.getItem(KHOA_ACTOR_PHIEN)).toBe('PT')
    expect(() => store.chonVaiTroDangDung('MEMBER')).toThrow()
    expect(() => store.chonVaiTroDangDung('FREE')).toThrow()
  })
})
