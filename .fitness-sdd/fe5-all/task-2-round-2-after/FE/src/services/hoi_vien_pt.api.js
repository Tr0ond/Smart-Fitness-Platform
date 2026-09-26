import ketNoiApi from './api.js'

function taoLoiDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: 'INVALID_PT_MEMBER_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

/** Chi chap nhan profile ID so nguyen duong an toan cho PT-scoped URL. */
export function laIdHoiVienHopLe(giaTri) {
  if (typeof giaTri === 'number') {
    return Number.isSafeInteger(giaTri) && giaTri > 0
  }

  return typeof giaTri === 'string'
    && /^[1-9]\d*$/.test(giaTri.trim())
    && Number.isSafeInteger(Number(giaTri.trim()))
}

function layIdAnToan(giaTri) {
  if (!laIdHoiVienHopLe(giaTri)) {
    throw taoLoiDauVao('Không thể truy cập học viên này.', {
      member: ['ID học viên phải là số nguyên dương.'],
    }, 404)
  }

  return String(giaTri).trim()
}

/**
 * Tai danh sach Member dang duoc phan cong hien tai cua PT.
 *
 * Input: khong nhan filter tim kiem toan he thong.
 * Process: chi goi contract GET `/pt/members`; Backend loc exact assignment interval.
 * Output: envelope `{ data: items }` do Backend tra ve.
 * Side effect: read-only, khong suy role/assignment tren client.
 */
export async function taiDanhSachHoiVienDuocPhanCong() {
  const phanHoi = await ketNoiApi.get('/pt/members')

  return phanHoi.data
}

/**
 * Tai coaching profile an toan cua Member trong assignment hien tai.
 *
 * Input: member ID da validate format; ownership va exact assignment do Backend kiem tra.
 * Process: khong fallback sang Admin hay Member-self endpoint.
 * Output: envelope `{ data: { member, assignment } }`.
 * Side effect: read-only, khong Membership/Plan mutation.
 */
export async function taiChiTietHoiVien(memberId) {
  const id = layIdAnToan(memberId)
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}`)

  return phanHoi.data
}

export const taiDanhSachHoiVien = taiDanhSachHoiVienDuocPhanCong

