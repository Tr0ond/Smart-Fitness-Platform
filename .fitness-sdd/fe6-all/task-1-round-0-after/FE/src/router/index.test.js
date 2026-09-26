import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import boDinhTuyen, { datFocusVaoTieuDeSauDieuHuong } from './index.js'
import { useXacThucStore } from '../stores/xac_thuc.store.js'

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
  ptHoSo: '/pt/ho-so',
  ptHoiVien: '/pt/hoi-vien',
  ptChiTietHoiVien: '/pt/hoi-vien/:id',
  ptTienDoHoiVien: '/pt/hoi-vien/:id/tien-do',
  ptKeHoachTapHoiVien: '/pt/hoi-vien/:id/ke-hoach-tap',
  ptLichSuTapHoiVien: '/pt/hoi-vien/:id/lich-su-tap',
  ptGhiChuHoiVien: '/pt/hoi-vien/:id/ghi-chu',
  ptBuoiHuanLuyen: '/pt/hoi-vien/:id/buoi-huan-luyen',
  ptDeXuatKeHoach: '/pt/hoi-vien/:id/de-xuat',
  ptTaoDeXuatKeHoach: '/pt/hoi-vien/:id/de-xuat/tao-moi',
  leTanDangNhap: '/le-tan/dang-nhap',
  adminBangDieuKhien: '/admin/bang-dieu-khien',
  adminTaiKhoan: '/admin/tai-khoan',
  adminHoiVien: '/admin/hoi-vien',
  adminHuanLuyenVien: '/admin/huan-luyen-vien',
  adminTaoHuanLuyenVien: '/admin/huan-luyen-vien/tao-moi',
  adminChiTietHuanLuyenVien: '/admin/huan-luyen-vien/:id',
  adminPhanCongPt: '/admin/phan-cong-pt',
  adminGoiTap: '/admin/goi-tap',
  adminTaoGoiTap: '/admin/goi-tap/tao-moi',
  adminChiTietGoiTap: '/admin/goi-tap/:id',
  adminDungCu: '/admin/dung-cu',
  adminNhomCo: '/admin/nhom-co',
  adminBaiTap: '/admin/bai-tap',
  adminTaoBaiTap: '/admin/bai-tap/tao-moi',
  adminChiTietBaiTap: '/admin/bai-tap/:id',
  adminGiaoAnMau: '/admin/giao-an-mau',
  adminTaoGiaoAnMau: '/admin/giao-an-mau/tao-moi',
  adminTaoPhienBanGiaoAnMau: '/admin/giao-an-mau/:id/tao-phien-ban',
  adminChiTietGiaoAnMau: '/admin/giao-an-mau/:id',
  adminThanhToan: '/admin/thanh-toan',
  adminChiTietThanhToan: '/admin/thanh-toan/:id',
  adminDoiSoatThanhToan: '/admin/doi-soat-thanh-toan',
}

const FE3_ROUTE_MATRIX = [
  { name: 'adminGoiTap', path: '/admin/goi-tap', parent: null, menu: true },
  { name: 'adminTaoGoiTap', path: '/admin/goi-tap/tao-moi', parent: 'adminGoiTap', menu: false },
  { name: 'adminChiTietGoiTap', path: '/admin/goi-tap/:id', parent: 'adminGoiTap', menu: false },
  { name: 'adminDungCu', path: '/admin/dung-cu', parent: null, menu: true },
  { name: 'adminNhomCo', path: '/admin/nhom-co', parent: null, menu: true },
  { name: 'adminBaiTap', path: '/admin/bai-tap', parent: null, menu: true },
  { name: 'adminTaoBaiTap', path: '/admin/bai-tap/tao-moi', parent: 'adminBaiTap', menu: false },
  { name: 'adminChiTietBaiTap', path: '/admin/bai-tap/:id', parent: 'adminBaiTap', menu: false },
  { name: 'adminGiaoAnMau', path: '/admin/giao-an-mau', parent: null, menu: true },
  { name: 'adminTaoGiaoAnMau', path: '/admin/giao-an-mau/tao-moi', parent: 'adminGiaoAnMau', menu: false },
  { name: 'adminTaoPhienBanGiaoAnMau', path: '/admin/giao-an-mau/:id/tao-phien-ban', parent: 'adminGiaoAnMau', menu: false },
  { name: 'adminChiTietGiaoAnMau', path: '/admin/giao-an-mau/:id', parent: 'adminGiaoAnMau', menu: false },
]

const FE4_ROUTE_MATRIX = [
  { name: 'adminThanhToan', path: '/admin/thanh-toan', parent: null, menu: true },
  { name: 'adminChiTietThanhToan', path: '/admin/thanh-toan/:id', parent: 'adminThanhToan', menu: false },
  { name: 'adminDoiSoatThanhToan', path: '/admin/doi-soat-thanh-toan', parent: null, menu: true },
]

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

  it('registers FE6 routes as protected PT screens with scoped breadcrumbs', () => {
    const routes = boDinhTuyen.getRoutes()
    const buoi = routes.find((route) => route.name === 'ptBuoiHuanLuyen')
    const deXuat = routes.find((route) => route.name === 'ptDeXuatKeHoach')
    const taoDeXuat = routes.find((route) => route.name === 'ptTaoDeXuatKeHoach')

    expect(buoi.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['PT'],
      boCuc: 'pt',
      tieuDe: 'Buổi huấn luyện trực tiếp',
      duongDanPhanCap: [{ nhan: 'Hội viên', tenTuyenDuong: 'ptHoiVien' }, { nhan: 'Buổi huấn luyện' }],
    })
    expect(deXuat.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['PT'],
      boCuc: 'pt',
      tieuDe: 'Đề xuất kế hoạch tập',
      duongDanPhanCap: [{ nhan: 'Hội viên', tenTuyenDuong: 'ptHoiVien' }, { nhan: 'Đề xuất' }],
    })
    expect(taoDeXuat.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['PT'],
      boCuc: 'pt',
      tieuDe: 'Tạo đề xuất kế hoạch',
      duongDanPhanCap: [
        { nhan: 'Hội viên', tenTuyenDuong: 'ptHoiVien' },
        { nhan: 'Đề xuất', tenTuyenDuong: 'ptDeXuatKeHoach' },
        { nhan: 'Tạo mới' },
      ],
    })
    expect(boDinhTuyen.resolve('/pt/hoi-vien/7/de-xuat/tao-moi').name).toBe('ptTaoDeXuatKeHoach')
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

  it('trainer list route co meta Admin va breadcrumb Huấn luyện viên', () => {
    const route = boDinhTuyen.getRoutes().find((muc) => muc.name === 'adminHuanLuyenVien')

    expect(route).toBeDefined()
    expect(route.path).toBe('/admin/huan-luyen-vien')
    expect(route.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Danh sách Huấn luyện viên',
      hienTrongDieuHuong: true,
      duongDanPhanCap: [{ nhan: 'Huấn luyện viên' }],
    })
  })

  it('trainer detail route co meta Admin va breadcrumb quay ve danh sach', () => {
    const route = boDinhTuyen.getRoutes().find((muc) => muc.name === 'adminChiTietHuanLuyenVien')

    expect(route).toBeDefined()
    expect(route.path).toBe('/admin/huan-luyen-vien/:id')
    expect(route.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Chi tiết Huấn luyện viên',
      hienTrongDieuHuong: false,
      duongDanPhanCap: [
        { nhan: 'Huấn luyện viên', tenTuyenDuong: 'adminHuanLuyenVien' },
        { nhan: 'Chi tiết Huấn luyện viên' },
      ],
    })
  })

  it('trainer tao moi la route static truoc param, co breadcrumb va active parent metadata', () => {
    const route = boDinhTuyen.getRoutes().find((muc) => muc.name === 'adminTaoHuanLuyenVien')
    const ketQua = boDinhTuyen.resolve('/admin/huan-luyen-vien/tao-moi')

    expect(route).toBeDefined()
    expect(route.path).toBe('/admin/huan-luyen-vien/tao-moi')
    expect(route.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Tạo mới Huấn luyện viên',
      hienTrongDieuHuong: false,
      duongDanPhanCap: [
        { nhan: 'Huấn luyện viên', tenTuyenDuong: 'adminHuanLuyenVien' },
        { nhan: 'Tạo mới Huấn luyện viên' },
      ],
    })
    expect(ketQua.name).toBe('adminTaoHuanLuyenVien')
    expect(ketQua.matched.map((muc) => muc.name)).not.toContain('adminChiTietHuanLuyenVien')
  })

  it('assignment route co Admin meta breadcrumb va hien trong menu', () => {
    const route = boDinhTuyen.getRoutes().find((muc) => muc.name === 'adminPhanCongPt')

    expect(route).toBeDefined()
    expect(route.path).toBe('/admin/phan-cong-pt')
    expect(route.meta).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tieuDe: 'Phân công PT',
      hienTrongDieuHuong: true,
      duongDanPhanCap: [{ nhan: 'Phân công PT' }],
    })
  })

  it('catalog co du 12 route, meta Admin, breadcrumb va static route dung truoc param', () => {
    const routes = boDinhTuyen.getRoutes()
    const names = [
      'adminGoiTap', 'adminTaoGoiTap', 'adminChiTietGoiTap',
      'adminDungCu', 'adminNhomCo',
      'adminBaiTap', 'adminTaoBaiTap', 'adminChiTietBaiTap',
      'adminGiaoAnMau', 'adminTaoGiaoAnMau', 'adminTaoPhienBanGiaoAnMau', 'adminChiTietGiaoAnMau',
    ]
    expect(names.every((name) => routes.some((route) => route.name === name))).toBe(true)
    for (const name of names) {
      const route = routes.find((item) => item.name === name)
      expect(route.meta).toMatchObject({ yeuCauXacThuc: true, vaiTro: ['ADMIN'], boCuc: 'admin' })
      expect(route.meta.tieuDe).toMatch(/[À-ỹ]/)
      expect(route.meta.duongDanPhanCap.length).toBeGreaterThan(0)
    }
    expect(boDinhTuyen.resolve('/admin/giao-an-mau/tao-moi').name).toBe('adminTaoGiaoAnMau')
    expect(boDinhTuyen.resolve('/admin/giao-an-mau/4/tao-phien-ban').name).toBe('adminTaoPhienBanGiaoAnMau')
  })

  it('table-drive exact FE3 route paths, Admin metadata, breadcrumbs and deep-link matching', () => {
    const routes = boDinhTuyen.getRoutes()

    for (const expected of FE3_ROUTE_MATRIX) {
      const route = routes.find((item) => item.name === expected.name)
      expect(route).toBeDefined()
      expect(route.path).toBe(expected.path)
      expect(route.meta).toMatchObject({
        yeuCauXacThuc: true,
        vaiTro: ['ADMIN'],
        boCuc: 'admin',
        tinhNang: expected.name,
        hienTrongDieuHuong: expected.menu,
      })
      expect(Array.isArray(route.meta.duongDanPhanCap)).toBe(true)
      expect(route.meta.duongDanPhanCap.length).toBe(expected.parent ? 2 : 1)
      expect(route.meta.duongDanPhanCap[0]).toMatchObject({ nhan: expect.any(String) })
      if (expected.parent) {
        expect(route.meta.duongDanPhanCap[0].tenTuyenDuong).toBe(expected.parent)
        expect(route.meta.duongDanPhanCap[1]).toMatchObject({ nhan: expect.any(String) })
      }
      expect(boDinhTuyen.resolve(expected.path.replace(':id', '17')).name).toBe(expected.name)
    }
  })

  it('table-drive exact FE4 Payment routes, Admin metadata, breadcrumbs and deep links', () => {
    const routes = boDinhTuyen.getRoutes()

    for (const expected of FE4_ROUTE_MATRIX) {
      const route = routes.find((item) => item.name === expected.name)
      expect(route).toBeDefined()
      expect(route.path).toBe(expected.path)
      expect(route.meta).toMatchObject({
        yeuCauXacThuc: true,
        vaiTro: ['ADMIN'],
        boCuc: 'admin',
        tinhNang: expected.name,
        hienTrongDieuHuong: expected.menu,
      })
      expect(route.meta.duongDanPhanCap.length).toBe(expected.parent ? 2 : 1)
      if (expected.parent) {
        expect(route.meta.duongDanPhanCap[0]).toMatchObject({
          tenTuyenDuong: expected.parent,
        })
      }
      expect(boDinhTuyen.resolve(expected.path.replace(':id', '17')).name).toBe(expected.name)
    }
  })

  it('guest bi redirect va non-ADMIN bi tu choi, ADMIN duoc vao catalog', async () => {
    const store = useXacThucStore()
    store.xoaPhienDangNhap()
    await boDinhTuyen.push('/admin/goi-tap')
    expect(boDinhTuyen.currentRoute.value.name).toBe('chonVaiTro')

    store.token = 'token-pt'
    store.nguoiDung = { id: 1 }
    store.vaiTro = ['PT']
    store.vaiTroDangDung = 'PT'
    store.daKhoiPhucPhien = true
    await boDinhTuyen.push('/admin/goi-tap')
    expect(boDinhTuyen.currentRoute.value.name).toBe('khongCoQuyen')

    store.token = 'token-admin'
    store.nguoiDung = { id: 2 }
    store.vaiTro = ['ADMIN']
    store.vaiTroDangDung = 'ADMIN'
    store.daKhoiPhucPhien = true
    await boDinhTuyen.push('/admin/goi-tap')
    expect(boDinhTuyen.currentRoute.value.name).toBe('adminGoiTap')
  })

  it('guest PT Receptionist bi chan va Admin truy cap duoc moi FE3 deep link', async () => {
    const store = useXacThucStore()
    const deepLinks = FE3_ROUTE_MATRIX.map((item) => ({
      name: item.name,
      path: item.path.replace(':id', '17'),
    }))

    store.xoaPhienDangNhap()
    for (const deepLink of deepLinks) {
      await boDinhTuyen.push(deepLink.path)
      expect(boDinhTuyen.currentRoute.value.name).toBe('chonVaiTro')
    }

    for (const vaiTro of ['PT', 'RECEPTIONIST']) {
      store.token = `token-${vaiTro.toLowerCase()}`
      store.nguoiDung = { id: 3 }
      store.vaiTro = [vaiTro]
      store.vaiTroDangDung = vaiTro
      store.daKhoiPhucPhien = true
      for (const deepLink of deepLinks) {
        await boDinhTuyen.push(deepLink.path)
        expect(boDinhTuyen.currentRoute.value.name).toBe('khongCoQuyen')
      }
    }

    store.token = 'token-admin'
    store.nguoiDung = { id: 4 }
    store.vaiTro = ['ADMIN']
    store.vaiTroDangDung = 'ADMIN'
    store.daKhoiPhucPhien = true
    for (const deepLink of deepLinks) {
      await boDinhTuyen.push(deepLink.path)
      expect(boDinhTuyen.currentRoute.value.name).toBe(deepLink.name)
    }
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
