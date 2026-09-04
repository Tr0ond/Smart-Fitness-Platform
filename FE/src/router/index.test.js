import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import boDinhTuyen, { datFocusVaoTieuDeSauDieuHuong } from './index.js'

const ROUTE_MONG_DOI = {
  dieuPhoiTrangGoc: '/',
  chonVaiTro: '/chon-vai-tro',
  quenMatKhau: '/quen-mat-khau',
  datLaiMatKhau: '/dat-lai-mat-khau',
  datLaiMatKhauCu: '/reset-password',
  khongCoQuyen: '/khong-co-quyen',
  khongTimThay: '/khong-tim-thay',
  adminDangNhap: '/admin/dang-nhap',
  ptDangNhap: '/pt/dang-nhap',
  leTanDangNhap: '/le-tan/dang-nhap',
  adminBangDieuKhien: '/admin/bang-dieu-khien',
  adminTaiKhoan: '/admin/tai-khoan',
  adminHoiVien: '/admin/hoi-vien',
}

describe('router foundation FE0-T05', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await boDinhTuyen.push({ name: 'chonVaiTro' })
  })

  it('co du route public auth, error, selector va root names', () => {
    const routes = boDinhTuyen.getRoutes()
    const routePaths = Object.fromEntries(
      routes
        .filter((route) => typeof route.name === 'string')
        .map((route) => [route.name, route.path]),
    )

    expect(routePaths).toMatchObject(ROUTE_MONG_DOI)
    expect(routes.some((route) => route.path === '/:pathMatch(.*)*')).toBe(true)
    expect(routes.find((route) => route.name === 'adminDangNhap').meta).toMatchObject({
      congKhai: true,
      vaiTro: 'ADMIN',
    })
    expect(routes.find((route) => route.name === 'adminBangDieuKhien').meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Bảng điều khiển',
      duongDanPhanCap: [{ nhan: 'Bảng điều khiển' }],
    })
    expect(routes.find((route) => route.name === 'adminTaiKhoan').meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Danh sách tài khoản',
      duongDanPhanCap: [{ nhan: 'Tài khoản' }],
      hienTrongDieuHuong: true,
    })
  })

  it('catch-all redirect den trang khong tim thay', async () => {
    await boDinhTuyen.push('/duong-dan-khong-ton-tai')

    expect(boDinhTuyen.currentRoute.value.name).toBe('khongTimThay')
    expect(boDinhTuyen.currentRoute.value.path).toBe('/khong-tim-thay')
  })

  it('public actor login khong bi guard redirect', async () => {
    await boDinhTuyen.push('/pt/dang-nhap')

    expect(boDinhTuyen.currentRoute.value.name).toBe('ptDangNhap')
  })

  it('account detail route co meta Admin, breadcrumb va khong them menu item', () => {
    const route = boDinhTuyen.getRoutes().find((muc) => muc.name === 'adminChiTietTaiKhoan')

    expect(route).toBeDefined()
    expect(route.path).toBe('/admin/tai-khoan/:id')
    expect(route.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Chi tiết tài khoản',
      hienTrongDieuHuong: false,
      duongDanPhanCap: [
        { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })
  })

  it('member list/detail route co meta Admin va breadcrumb named an toan', () => {
    const danhSach = boDinhTuyen.getRoutes().find((muc) => muc.name === 'adminHoiVien')
    const chiTiet = boDinhTuyen.getRoutes().find((muc) => muc.name === 'adminChiTietHoiVien')

    expect(danhSach).toBeDefined()
    expect(danhSach.path).toBe('/admin/hoi-vien')
    expect(danhSach.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Danh sách Hội viên',
      hienTrongDieuHuong: true,
      duongDanPhanCap: [{ nhan: 'Hội viên' }],
    })
    expect(chiTiet).toBeDefined()
    expect(chiTiet.path).toBe('/admin/hoi-vien/:id')
    expect(chiTiet.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Chi tiết Hội viên',
      hienTrongDieuHuong: false,
      duongDanPhanCap: [
        { nhan: 'Hội viên', tenTuyenDuong: 'adminHoiVien' },
        { nhan: 'Chi tiết Hội viên' },
      ],
    })
  })

  it('email reset cu van mo dung man hinh dat lai mat khau', async () => {
    await boDinhTuyen.push({ name: 'datLaiMatKhauCu', query: { token: 'a'.repeat(64) } })

    expect(boDinhTuyen.currentRoute.value.name).toBe('datLaiMatKhauCu')
    expect(boDinhTuyen.currentRoute.value.query.token).toBe('a'.repeat(64))
  })

  it('selector restore session neu co token nhung van cho neutral pre-auth fallback', async () => {
    await boDinhTuyen.push('/chon-vai-tro')

    expect(boDinhTuyen.currentRoute.value.name).toBe('chonVaiTro')
  })

  it('focus heading sau navigation thay vi de activeElement o body', async () => {
    const tieuDe = document.createElement('h1')
    tieuDe.textContent = 'Trang mới'
    document.body.appendChild(tieuDe)

    try {
      await expect(datFocusVaoTieuDeSauDieuHuong()).resolves.toBe(true)
      expect(document.activeElement).toBe(tieuDe)
      expect(tieuDe.getAttribute('tabindex')).toBe('-1')
    } finally {
      tieuDe.remove()
    }
  })
})
