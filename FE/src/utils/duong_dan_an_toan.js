export const CAC_TEN_TUYEN_DUONG_NOI_BO_CHO_PHEP = Object.freeze([
  'chonVaiTro',
  'quenMatKhau',
  'datLaiMatKhau',
  'khongCoQuyen',
  'khongTimThay',
  'adminDangNhap',
  'ptDangNhap',
  'leTanDangNhap',
])

/**
 * Chuyen Array/Set allow-list thanh tap ten route de tra cuu an toan.
 *
 * Dau vao: danh sach ten route do caller chu dong cung cap.
 * Cach hoat dong: giu Set hien co, copy Array, hoac fail closed voi kieu khac.
 * Ket qua: Set dung cho phep kiem tra membership exact.
 * Side effect: khong doc router va khong navigate.
 * Security Rule: khong tu dong lay danh sach route runtime.
 */
function taoTapTenTuyenDuong(danhSachChoPhep) {
  if (danhSachChoPhep instanceof Set) {
    return danhSachChoPhep
  }

  if (Array.isArray(danhSachChoPhep)) {
    return new Set(danhSachChoPhep)
  }

  return new Set()
}

/**
 * Loc ky tu co the bien route name thanh path/URL nguy hiem.
 *
 * Dau vao: ten route string da qua kiem tra kieu.
 * Cach hoat dong: reject protocol, protocol-relative prefix, slash, backslash,
 * query, fragment va percent encoding truoc khi allow-list membership.
 * Ket qua: true neu chuoi khong co dau hieu redirect ngoai.
 * Side effect: khong thay doi chuoi hay router.
 * Security Rule: fail closed voi scheme javascript/data/mailto/ftp va URL ngoai.
 */
function laTenTuyenDuongKhongChuaKyTuNguyHiem(tenTuyenDuong) {
  if (tenTuyenDuong.startsWith('//')) {
    return false
  }

  if (/^[a-z][a-z\d+.-]*:/i.test(tenTuyenDuong)) {
    return false
  }

  return !['/', '\\', ':', '?', '#', '%'].some((kyTu) => tenTuyenDuong.includes(kyTu))
}

/**
 * Kiem tra redirect chi den named route noi bo da duoc allow-list.
 *
 * Dau vao: routeName string, danh sach ten route cho phep (Array/Set), router
 * tuy chon de xac nhan route dang ton tai trong runtime.
 * Cach hoat dong: reject gia tri khong phai string, URL/protocol/ky tu path nguy
 * hiem va ten ngoai allow-list; neu co router thi kiem tra them route hien huu.
 * Ket qua: true chi khi route name an toan va duoc cho phep, nguoc lai false.
 * Side effect: khong navigate, khong thay doi trang hien tai va khong sua router.
 * Security Rule: khong lay toan bo route runtime lam allow-list va khong chap nhan
 * open redirect, protocol-relative URL, backslash trick hay scheme javascript/data.
 */
export function laDuongDanNoiBoHopLe(
  giaTri,
  danhSachChoPhep = CAC_TEN_TUYEN_DUONG_NOI_BO_CHO_PHEP,
  router = null,
) {
  if (typeof giaTri !== 'string' || giaTri === '' || giaTri !== giaTri.trim()) {
    return false
  }

  if (!laTenTuyenDuongKhongChuaKyTuNguyHiem(giaTri)) {
    return false
  }

  const tapTenTuyenDuong = taoTapTenTuyenDuong(danhSachChoPhep)

  if (!tapTenTuyenDuong.has(giaTri)) {
    return false
  }

  if (router === null || router === undefined) {
    return true
  }

  try {
    if (typeof router.hasRoute === 'function') {
      return router.hasRoute(giaTri)
    }

    if (typeof router.resolve !== 'function') {
      return false
    }

    const ketQua = router.resolve({ name: giaTri })

    return Array.isArray(ketQua?.matched) && ketQua.matched.length > 0
  } catch {
    return false
  }
}
