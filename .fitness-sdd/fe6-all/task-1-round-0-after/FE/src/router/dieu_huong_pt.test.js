import { describe, expect, it } from 'vitest'
import {
  CAC_NHAN_DIEU_HUONG_PT,
  laMucDieuHuongDangHoatDong,
  taoDanhSachDieuHuongPt,
  taoMetaTuyenDuongPt,
} from './dieu_huong_pt.js'

function taoRouter(names) {
  return {
    hasRoute: (name) => names.includes(name),
  }
}

describe('dieu_huong_pt FE5', () => {
  it('exposes exactly Hồ sơ and Hội viên menu parents from registered routes', () => {
    const menu = taoDanhSachDieuHuongPt(taoRouter([
      'ptHoSo', 'ptHoiVien', 'ptChiTietHoiVien', 'ptTienDoHoiVien',
      'ptKeHoachTapHoiVien', 'ptLichSuTapHoiVien', 'ptGhiChuHoiVien',
      'ptBuoiHuanLuyen', 'ptDeXuatKeHoach', 'ptTaoDeXuatKeHoach',
    ]))
    expect(menu.map((item) => item.tenTuyenDuong)).toEqual(['ptHoSo', 'ptHoiVien'])
    expect(menu[1].to).toEqual({ name: 'ptHoiVien' })
  })

  it('keeps selected member child routes active under Hội viên parent', () => {
    const muc = CAC_NHAN_DIEU_HUONG_PT.find((item) => item.tenTuyenDuong === 'ptHoiVien')
    expect(laMucDieuHuongDangHoatDong(muc, {
      name: 'ptLichSuTapHoiVien',
      matched: [{ name: 'ptLichSuTapHoiVien' }],
    })).toBe(true)
  })

  it('recognizes FE6 child routes without exposing them as top-level menu entries', () => {
    const menu = taoDanhSachDieuHuongPt(taoRouter([
      'ptHoSo', 'ptHoiVien', 'ptBuoiHuanLuyen', 'ptDeXuatKeHoach', 'ptTaoDeXuatKeHoach',
    ]))
    const member = CAC_NHAN_DIEU_HUONG_PT.find((item) => item.tenTuyenDuong === 'ptHoiVien')

    expect(menu.map((item) => item.tenTuyenDuong)).toEqual(['ptHoSo', 'ptHoiVien'])
    expect(member.cacTuyenDuongLienQuan).toEqual(expect.arrayContaining([
      'ptBuoiHuanLuyen', 'ptDeXuatKeHoach', 'ptTaoDeXuatKeHoach',
    ]))
    expect(laMucDieuHuongDangHoatDong(member, {
      name: 'ptTaoDeXuatKeHoach',
      matched: [{ name: 'ptTaoDeXuatKeHoach' }],
    })).toBe(true)
  })

  it('creates PT-only protected route metadata and safe breadcrumbs', () => {
    expect(taoMetaTuyenDuongPt({
      tinhNang: 'ptGhiChuHoiVien',
      tieuDe: 'Ghi chú',
      duongDanPhanCap: [{ nhan: 'Hội viên', tenTuyenDuong: 'ptHoiVien' }, { nhan: 'Ghi chú' }],
    })).toMatchObject({
      yeuCauXacThuc: true,
      vaiTro: ['PT'],
      boCuc: 'pt',
      tinhNang: 'ptGhiChuHoiVien',
    })
  })
})
