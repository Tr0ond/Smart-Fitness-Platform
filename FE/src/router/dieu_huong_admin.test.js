import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import boDinhTuyen from './index.js'
import {
  CAC_NHOM_DIEU_HUONG_ADMIN,
  laMucDieuHuongDangHoatDong,
  taoDanhSachDieuHuongAdmin,
  taoMetaTuyenDuongAdmin,
} from './dieu_huong_admin.js'

const TrangKiemThu = { template: '<h1>Trang kiểm thử</h1>' }

function taoRouterKiemThu(routes = []) {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'trangKiemThu', component: TrangKiemThu },
      ...routes,
    ],
  })
}

describe('dieu_huong_admin FE1-T01', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await boDinhTuyen.push({ name: 'chonVaiTro' })
  })

  it('co menu Admin san sang voi nhan tieng Viet va PT dung thu tu', () => {
    expect(CAC_NHOM_DIEU_HUONG_ADMIN).toHaveLength(13)
    expect([...CAC_NHOM_DIEU_HUONG_ADMIN].sort((a, b) => a.thuTu - b.thuTu).map((muc) => muc.nhan)).toEqual([
      'Bảng điều khiển',
      'Tài khoản',
      'Hội viên',
      'Huấn luyện viên',
      'Phân công PT',
      'Nhân viên lễ tân',
      'Gói tập',
      'Dụng cụ',
      'Nhóm cơ',
      'Bài tập',
      'Giáo án mẫu',
      'Thanh toán',
      'Đối soát thanh toán',
    ])
  })

  it('co nam muc catalog va hai muc payment read-only dung hop dong', () => {
    const chuoiConfig = JSON.stringify(CAC_NHOM_DIEU_HUONG_ADMIN)
    const cacMucPhanCong = CAC_NHOM_DIEU_HUONG_ADMIN.filter(
      (muc) => muc.nhan === 'Phân công PT',
    )

    expect(cacMucPhanCong).toHaveLength(1)
    expect(cacMucPhanCong[0]).toMatchObject({
      nhan: 'Phân công PT',
      tenTuyenDuong: 'adminPhanCongPt',
      cacTuyenDuongLienQuan: ['adminPhanCongPt'],
      thuTu: 36,
    })
    expect(CAC_NHOM_DIEU_HUONG_ADMIN.filter((muc) => [
      'Gói tập', 'Dụng cụ', 'Nhóm cơ', 'Bài tập', 'Giáo án mẫu',
    ].includes(muc.nhan)).map((muc) => muc.tenTuyenDuong)).toEqual([
      'adminGoiTap', 'adminDungCu', 'adminNhomCo', 'adminBaiTap', 'adminGiaoAnMau',
    ])
    expect(chuoiConfig).toContain('Thanh toán')
    expect(CAC_NHOM_DIEU_HUONG_ADMIN).toEqual(expect.arrayContaining([
      expect.objectContaining({
        nhan: 'Thanh toán',
        tenTuyenDuong: 'adminThanhToan',
        cacTuyenDuongLienQuan: ['adminThanhToan', 'adminChiTietThanhToan'],
      }),
      expect.objectContaining({
        nhan: 'Đối soát thanh toán',
        tenTuyenDuong: 'adminDoiSoatThanhToan',
        cacTuyenDuongLienQuan: ['adminDoiSoatThanhToan'],
      }),
    ]))
    expect(CAC_NHOM_DIEU_HUONG_ADMIN.filter((muc) => muc.nhan === 'Huấn luyện viên'))
      .toHaveLength(1)
  })

  it('moi muc chi tro toi named route noi bo', () => {
    for (const muc of CAC_NHOM_DIEU_HUONG_ADMIN) {
      expect(muc.tenTuyenDuong).toMatch(/^admin[A-Z]/)
      expect(muc.cacTuyenDuongLienQuan).toContain(muc.tenTuyenDuong)
    }
  })

  it('menu production hien dung sau khi route Phan cong PT san sang', () => {
    const danhSach = taoDanhSachDieuHuongAdmin(boDinhTuyen)

    expect(danhSach.slice(0, 6).map((muc) => muc.tenTuyenDuong)).toEqual([
      'adminBangDieuKhien',
      'adminTaiKhoan',
      'adminHoiVien',
      'adminHuanLuyenVien',
      'adminPhanCongPt',
      'adminNhanVienLeTan',
    ])
    expect(danhSach.slice(0, 6)).toMatchObject([
      {
        nhan: 'Bảng điều khiển',
        tenTuyenDuong: 'adminBangDieuKhien',
        to: { name: 'adminBangDieuKhien' },
      },
      {
        nhan: 'Tài khoản',
        tenTuyenDuong: 'adminTaiKhoan',
        to: { name: 'adminTaiKhoan' },
      },
      {
        nhan: 'Hội viên',
        tenTuyenDuong: 'adminHoiVien',
        to: { name: 'adminHoiVien' },
      },
      {
        nhan: 'Huấn luyện viên',
        tenTuyenDuong: 'adminHuanLuyenVien',
        to: { name: 'adminHuanLuyenVien' },
      },
      {
        nhan: 'Phân công PT',
        tenTuyenDuong: 'adminPhanCongPt',
        to: { name: 'adminPhanCongPt' },
      },
      {
        nhan: 'Nhân viên lễ tân',
        tenTuyenDuong: 'adminNhanVienLeTan',
        to: { name: 'adminNhanVienLeTan' },
      },
    ])
    expect(danhSach.find((muc) => muc.tenTuyenDuong === 'adminHuanLuyenVien').cacTuyenDuongLienQuan)
      .toContain('adminChiTietHuanLuyenVien')
    expect(danhSach.find((muc) => muc.tenTuyenDuong === 'adminHuanLuyenVien').cacTuyenDuongLienQuan)
      .toContain('adminTaoHuanLuyenVien')
    expect(danhSach.filter((muc) => muc.tenTuyenDuong === 'adminPhanCongPt')).toHaveLength(1)
  })

  it('route test-only da dang ky thi muc tuong ung xuat hien', () => {
    const router = taoRouterKiemThu([
      { path: '/admin/tai-khoan', name: 'adminTaiKhoan', component: TrangKiemThu },
    ])

    const danhSach = taoDanhSachDieuHuongAdmin(router)

    expect(danhSach).toHaveLength(1)
    expect(danhSach[0]).toMatchObject({
      nhan: 'Tài khoản',
      tenTuyenDuong: 'adminTaiKhoan',
      to: { name: 'adminTaiKhoan' },
    })
  })

  it('route detail lam active parent qua related route names', () => {
    const mucTaiKhoan = CAC_NHOM_DIEU_HUONG_ADMIN.find((muc) => muc.tenTuyenDuong === 'adminTaiKhoan')

    expect(laMucDieuHuongDangHoatDong(mucTaiKhoan, {
      name: 'adminChiTietTaiKhoan',
      matched: [{ name: 'adminChiTietTaiKhoan' }],
    })).toBe(true)
  })

  it('route PT detail va tao moi lam active parent qua related route names', () => {
    const mucHuanLuyenVien = CAC_NHOM_DIEU_HUONG_ADMIN.find(
      (muc) => muc.tenTuyenDuong === 'adminHuanLuyenVien',
    )

    expect(laMucDieuHuongDangHoatDong(mucHuanLuyenVien, {
      name: 'adminChiTietHuanLuyenVien',
      matched: [{ name: 'adminChiTietHuanLuyenVien' }],
    })).toBe(true)
    expect(laMucDieuHuongDangHoatDong(mucHuanLuyenVien, {
      name: 'adminTaoHuanLuyenVien',
      matched: [{ name: 'adminTaoHuanLuyenVien' }],
    })).toBe(true)
  })

  it('nam catalog menu entries thu tu va moi route con active dung parent', () => {
    const catalog = [
      { menu: 'adminGoiTap', children: ['adminGoiTap', 'adminTaoGoiTap', 'adminChiTietGoiTap'] },
      { menu: 'adminDungCu', children: ['adminDungCu'] },
      { menu: 'adminNhomCo', children: ['adminNhomCo'] },
      { menu: 'adminBaiTap', children: ['adminBaiTap', 'adminTaoBaiTap', 'adminChiTietBaiTap'] },
      { menu: 'adminGiaoAnMau', children: ['adminGiaoAnMau', 'adminTaoGiaoAnMau', 'adminTaoPhienBanGiaoAnMau', 'adminChiTietGiaoAnMau'] },
    ]
    const menu = taoDanhSachDieuHuongAdmin(boDinhTuyen)

    expect(menu.filter((item) => catalog.some((entry) => entry.menu === item.tenTuyenDuong)).map((item) => item.tenTuyenDuong))
      .toEqual(catalog.map((entry) => entry.menu))
    for (const entry of catalog) {
      const item = menu.find((candidate) => candidate.tenTuyenDuong === entry.menu)
      for (const routeName of entry.children) {
        expect(laMucDieuHuongDangHoatDong(item, { name: routeName, matched: [{ name: routeName }] })).toBe(true)
      }
    }
  })

  it('route Phan cong PT active rieng va khong active route khac', () => {
    const mucPhanCong = CAC_NHOM_DIEU_HUONG_ADMIN.find(
      (muc) => muc.tenTuyenDuong === 'adminPhanCongPt',
    )

    expect(laMucDieuHuongDangHoatDong(mucPhanCong, {
      name: 'adminPhanCongPt',
      matched: [{ name: 'adminPhanCongPt' }],
    })).toBe(true)
    expect(laMucDieuHuongDangHoatDong(mucPhanCong, {
      name: 'adminHuanLuyenVien',
      matched: [{ name: 'adminHuanLuyenVien' }],
    })).toBe(false)
  })

  it('Payment detail active parent va queue doi soat khong active nham', () => {
    const mucThanhToan = CAC_NHOM_DIEU_HUONG_ADMIN.find((muc) => muc.tenTuyenDuong === 'adminThanhToan')
    const mucDoiSoat = CAC_NHOM_DIEU_HUONG_ADMIN.find((muc) => muc.tenTuyenDuong === 'adminDoiSoatThanhToan')

    expect(laMucDieuHuongDangHoatDong(mucThanhToan, {
      name: 'adminChiTietThanhToan',
      matched: [{ name: 'adminChiTietThanhToan' }],
    })).toBe(true)
    expect(laMucDieuHuongDangHoatDong(mucDoiSoat, {
      name: 'adminDoiSoatThanhToan',
      matched: [{ name: 'adminDoiSoatThanhToan' }],
    })).toBe(true)
    expect(laMucDieuHuongDangHoatDong(mucThanhToan, {
      name: 'adminDoiSoatThanhToan',
      matched: [{ name: 'adminDoiSoatThanhToan' }],
    })).toBe(false)
  })

  it('route matched cung duoc dung de xac dinh active context', () => {
    const mucHoiVien = CAC_NHOM_DIEU_HUONG_ADMIN.find((muc) => muc.tenTuyenDuong === 'adminHoiVien')

    expect(laMucDieuHuongDangHoatDong(mucHoiVien, {
      name: 'adminKhungBaoNgoai',
      matched: [{ name: 'adminHoiVien' }, { name: 'adminChiTietHoiVien' }],
    })).toBe(true)
  })

  it('meta Admin tuan thu auth actor va presentation convention', () => {
    expect(taoMetaTuyenDuongAdmin({
      tinhNang: 'adminTaiKhoan',
      tieuDe: 'Tài khoản',
      duongDanPhanCap: [{ nhan: 'Tài khoản' }],
      hienTrongDieuHuong: true,
    })).toEqual({
      yeuCauXacThuc: true,
      vaiTro: ['ADMIN'],
      boCuc: 'admin',
      tinhNang: 'adminTaiKhoan',
      tieuDe: 'Tài khoản',
      duongDanPhanCap: [{ nhan: 'Tài khoản' }],
      hienTrongDieuHuong: true,
    })
  })
})
