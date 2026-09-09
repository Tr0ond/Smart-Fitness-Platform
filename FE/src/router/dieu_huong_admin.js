const CAC_NHAN_DIEU_HUONG_ADMIN = Object.freeze([
  Object.freeze({
    nhan: 'Bảng điều khiển',
    tenTuyenDuong: 'adminBangDieuKhien',
    cacTuyenDuongLienQuan: Object.freeze(['adminBangDieuKhien']),
    thuTu: 10,
  }),
  Object.freeze({
    nhan: 'Tài khoản',
    tenTuyenDuong: 'adminTaiKhoan',
    cacTuyenDuongLienQuan: Object.freeze(['adminTaiKhoan', 'adminChiTietTaiKhoan']),
    thuTu: 20,
  }),
  Object.freeze({
    nhan: 'Hội viên',
    tenTuyenDuong: 'adminHoiVien',
    cacTuyenDuongLienQuan: Object.freeze(['adminHoiVien', 'adminChiTietHoiVien']),
    thuTu: 30,
  }),
  Object.freeze({
    nhan: 'Huấn luyện viên',
    tenTuyenDuong: 'adminHuanLuyenVien',
    cacTuyenDuongLienQuan: Object.freeze([
      'adminHuanLuyenVien',
      'adminTaoHuanLuyenVien',
      'adminChiTietHuanLuyenVien',
    ]),
    thuTu: 35,
  }),
  Object.freeze({
    nhan: 'Phân công PT',
    tenTuyenDuong: 'adminPhanCongPt',
    cacTuyenDuongLienQuan: Object.freeze(['adminPhanCongPt']),
    thuTu: 36,
  }),
  Object.freeze({
    nhan: 'Nhân viên lễ tân',
    tenTuyenDuong: 'adminNhanVienLeTan',
    cacTuyenDuongLienQuan: Object.freeze(['adminNhanVienLeTan', 'adminChiTietNhanVienLeTan']),
    thuTu: 40,
  }),
])

export const CAC_NHOM_DIEU_HUONG_ADMIN = CAC_NHAN_DIEU_HUONG_ADMIN
export const CAC_MUC_DIEU_HUONG_ADMIN = CAC_NHAN_DIEU_HUONG_ADMIN

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
 * Loc cac muc Admin co route production da dang ky de tao menu an toan.
 *
 * Dau vao: router hien tai va danh sach muc du kien presentation.
 * Cach hoat dong: chi giu muc co nhan, route name noi bo va router xac nhan route ton tai;
 * sap xep theo thu tu co dinh, khong dung menu de suy quyen Backend.
 * Ket qua: danh sach muc co `to` la named route noi bo, san sang truyen vao sidebar.
 * Side effect: chi doc route registry, khong dang ky route, goi API hay thay doi auth state.
 * Security/UX: route chua ton tai bi loai de khong tao RouterLink hong; menu visibility khong phai authorization.
 */
export function locMucDieuHuongDaSanSang(router, danhSach = CAC_NHAN_DIEU_HUONG_ADMIN) {
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
    .map((muc) => ({
      ...muc,
      to: { name: muc.tenTuyenDuong },
    }))
}

/**
 * Tao menu Admin tu config FE1 va route registry thuc te.
 *
 * Dau vao: router Vue Router dang phuc vu shell Admin.
 * Cach hoat dong: loc tap muc FE1 co route da register, giu nguyen label/related route names.
 * Ket qua: menu rong neu FE1 business route chua san sang, hoac menu chi gom named route hop le.
 * Side effect: khong them route, khong goi business API va khong thay doi quyen actor.
 * Security/UX: menu chi la lop hien thi; guard va Backend van la noi quyet dinh authorization.
 */
export function taoDanhSachDieuHuongAdmin(router) {
  return locMucDieuHuongDaSanSang(router)
}

/**
 * Xac dinh muc menu Admin co dang active theo route name va route matched.
 *
 * Dau vao: muc menu co route chinh/related route names va route hien tai.
 * Cach hoat dong: doi chieu route name hien tai voi tap route lien quan, khong suy tu pathname.
 * Ket qua: boolean de sidebar hien active parent, ke ca khi dang o route chi tiet.
 * Side effect: khong navigate, khong goi API va khong thay doi state authorization.
 * Security/UX: active menu chi phan anh context giao dien, khong cap quyen truy cap.
 */
export function laMucDieuHuongDangHoatDong(muc, route) {
  if (muc === null || typeof muc !== 'object' || route === null || typeof route !== 'object') {
    return false
  }

  const cacTenTuyenDuong = [muc.tenTuyenDuong, ...(Array.isArray(muc.cacTuyenDuongLienQuan)
    ? muc.cacTuyenDuongLienQuan
    : [])].filter(laTenTuyenDuongHopLe)

  if (cacTenTuyenDuong.includes(route.name)) {
    return true
  }

  return Array.isArray(route.matched)
    && route.matched.some((tuyenDuong) => cacTenTuyenDuong.includes(tuyenDuong?.name))
}

/**
 * Tao meta presentation dung cho route Admin business trong cac task sau.
 *
 * Dau vao: tinh nang, tieu de, metadata breadcrumb va co hien trong menu hay khong.
 * Cach hoat dong: dong goi role/bo cuc/auth contract Admin cung metadata presentation;
 * khong dang ky route va khong thay the guard/Backend authorization.
 * Ket qua: object meta co `yeuCauXacThuc=true`, `vaiTro=['ADMIN']`, `boCuc='admin'`.
 * Side effect: khong goi API, khong chon actor va khong sua router registry.
 * Security Rule: `tinhNang` chi phuc vu readiness presentation, khong phai quyen Backend.
 */
export function taoMetaTuyenDuongAdmin({
  tinhNang = '',
  tieuDe = '',
  duongDanPhanCap = [],
  hienTrongDieuHuong = false,
} = {}) {
  return {
    yeuCauXacThuc: true,
    vaiTro: ['ADMIN'],
    boCuc: 'admin',
    tinhNang,
    tieuDe,
    duongDanPhanCap: Array.isArray(duongDanPhanCap) ? duongDanPhanCap : [],
    hienTrongDieuHuong,
  }
}
