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

  it('co dung bon nhom menu FE1 voi nhan tieng Viet', () => {
    expect(CAC_NHOM_DIEU_HUONG_ADMIN).toHaveLength(4)
    expect(CAC_NHOM_DIEU_HUONG_ADMIN.map((muc) => muc.nhan)).toEqual([
      'Bảng điều khiển',
      'Tài khoản',
      'Hội viên',
      'Nhân viên lễ tân',
    ])
  })

  it('chi chuan bi route FE1, khong co muc FE2 FE3 FE4', () => {
    const chuoiConfig = JSON.stringify(CAC_NHOM_DIEU_HUONG_ADMIN)

    expect(chuoiConfig).not.toContain('Huấn luyện viên')
    expect(chuoiConfig).not.toContain('Phân công PT')
    expect(chuoiConfig).not.toContain('Gói tập')
    expect(chuoiConfig).not.toContain('Thanh toán')
  })

  it('moi muc chi tro toi named route noi bo', () => {
    for (const muc of CAC_NHOM_DIEU_HUONG_ADMIN) {
      expect(muc.tenTuyenDuong).toMatch(/^admin[A-Z]/)
      expect(muc.cacTuyenDuongLienQuan).toContain(muc.tenTuyenDuong)
    }
  })

  it('menu production hien Dashboard, Tai khoan, Hoi vien va Nhan vien le tan sau T07', () => {
    expect(taoDanhSachDieuHuongAdmin(boDinhTuyen)).toMatchObject([
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
        nhan: 'Nhân viên lễ tân',
        tenTuyenDuong: 'adminNhanVienLeTan',
        to: { name: 'adminNhanVienLeTan' },
      },
    ])
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
