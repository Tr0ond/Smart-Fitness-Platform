/**
 * Lay thong bao da duoc Axios normalize de hien thi an toan trong auth UI.
 *
 * Dau vao: normalized API error va fallback cho loi khong phai contract.
 * Cach hoat dong: uu tien message da loc; neu message thieu thi lay loi field dau tien
 * bat ke ten field, khong hard-code rieng email va khong hien raw error/config.
 * Ket qua: mot chuoi tieng Viet an toan cho nguoi dung.
 * Side effect: khong log va khong thay doi auth/session state.
 * Business Rule: 422 uu tien loi field da normalize; network/5xx dung message tong quat tu T03.
 */
export function layThongBaoLoiApi(error, fallback = 'Không thể xử lý yêu cầu.') {
  const laLoiDaChuanHoa = Number.isInteger(error?.httpStatus) || error?.isNetworkError === true

  if (laLoiDaChuanHoa && typeof error.message === 'string' && error.message.trim() !== '') {
    return error.message
  }

  const loiTruongDauTien = Object.values(error?.fieldErrors ?? {})
    .flatMap((danhSach) => Array.isArray(danhSach) ? danhSach : [])
    .find((thongBao) => typeof thongBao === 'string' && thongBao.trim() !== '')

  return typeof loiTruongDauTien === 'string' ? loiTruongDauTien : fallback
}
