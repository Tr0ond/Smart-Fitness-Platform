import { laMucDieuHuongDangHoatDong } from './dieu_huong_admin.js'

const CAC_NHAN_DIEU_HUONG_PT_NOI_BO = Object.freeze([
  Object.freeze({
    nhan: 'Hồ sơ',
    tenTuyenDuong: 'ptHoSo',
    cacTuyenDuongLienQuan: Object.freeze(['ptHoSo']),
    thuTu: 10,
  }),
  Object.freeze({
    nhan: 'Hội viên',
    tenTuyenDuong: 'ptHoiVien',
    cacTuyenDuongLienQuan: Object.freeze([
      'ptHoiVien',
      'ptChiTietHoiVien',
      'ptTienDoHoiVien',
      'ptKeHoachTapHoiVien',
      'ptLichSuTapHoiVien',
      'ptGhiChuHoiVien',
      'ptBuoiHuanLuyen',
      'ptDeXuatKeHoach',
      'ptTaoDeXuatKeHoach',
    ]),
    thuTu: 20,
  }),
])

export const CAC_NHAN_DIEU_HUONG_PT = CAC_NHAN_DIEU_HUONG_PT_NOI_BO
export const CAC_MUC_DIEU_HUONG_PT = CAC_NHAN_DIEU_HUONG_PT_NOI_BO

function laTenTuyenDuongHopLe(tenTuyenDuong) {
  return typeof tenTuyenDuong === 'string' && tenTuyenDuong.trim() !== ''
}

function laTuyenDuongDaDangKy(router, tenTuyenDuong) {
  if (!laTenTuyenDuongHopLe(tenTuyenDuong)) {
    return false
  }

  try {
    if (typeof router?.hasRoute === 'function') {
      return router.hasRoute(tenTuyenDuong)
    }

    if (typeof router?.resolve === 'function') {
      const ketQua = router.resolve({ name: tenTuyenDuong })
      return ketQua?.name === tenTuyenDuong && ketQua.matched.length > 0
    }
  } catch {
    return false
  }

  return false
}

/**
 * Loc menu PT theo route registry that, khong suy quyen tu menu.
 *
 * Input: Vue Router va cau hinh presentation.
 * Process: chi giu named route da dang ky, sap xep thu tu co dinh.
 * Output: item co `to` an toan de sidebar render RouterLink.
 * Side effect: chi doc router, khong goi API hay thay auth state.
 */
export function locMucDieuHuongDaSanSang(router, danhSach = CAC_NHAN_DIEU_HUONG_PT_NOI_BO) {
  if (!Array.isArray(danhSach)) {
    return []
  }

  return danhSach
    .filter((muc) => muc !== null
      && typeof muc === 'object'
      && typeof muc.nhan === 'string'
      && muc.nhan.trim() !== ''
      && laTuyenDuongDaDangKy(router, muc.tenTuyenDuong))
    .sort((mucMot, mucHai) => (mucMot.thuTu ?? 0) - (mucHai.thuTu ?? 0))
    .map((muc) => ({ ...muc, to: { name: muc.tenTuyenDuong } }))
}

/** Tao menu PT chi gom Ho so va Hoi vien khi route da san sang. */
export function taoDanhSachDieuHuongPt(router) {
  return locMucDieuHuongDaSanSang(router)
}

export const taoDanhSachDieuHuongPT = taoDanhSachDieuHuongPt

export { laMucDieuHuongDangHoatDong }

/** Meta presentation chung cho bảy route PT workspace. */
export function taoMetaTuyenDuongPt({
  tinhNang = '',
  tieuDe = '',
  duongDanPhanCap = [],
  hienTrongDieuHuong = false,
} = {}) {
  return {
    yeuCauXacThuc: true,
    vaiTro: ['PT'],
    boCuc: 'pt',
    tinhNang,
    tieuDe,
    duongDanPhanCap: Array.isArray(duongDanPhanCap) ? duongDanPhanCap : [],
    hienTrongDieuHuong,
  }
}
