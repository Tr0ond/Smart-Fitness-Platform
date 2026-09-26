import ketNoiApi from './api.js'
import { laIdHoiVienHopLe } from './hoi_vien_pt.api.js'

function taoLoiDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: 'INVALID_PT_PLAN_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function layIdAnToan(giaTri) {
  if (!laIdHoiVienHopLe(giaTri)) {
    throw taoLoiDauVao('Không thể truy cập kế hoạch của học viên này.', {
      member: ['ID học viên phải là số nguyên dương.'],
    }, 404)
  }

  return String(giaTri).trim()
}

/**
 * Tai Plan chinh thuc hien tai va future schedule cua Member.
 *
 * Input: Member ID da validate format.
 * Process: chi goi PT workspace official-plan route; khong doc Proposal/Member-self.
 * Output: `{ data: { plan, future_schedule, schedule_window } }`.
 * Side effect: read-only; khong activate Membership hay mutate Plan.
 */
export async function taiKeHoachTapHoiVien(memberId) {
  const id = layIdAnToan(memberId)
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}/workout/plans/current`)

  return phanHoi.data
}

export const taiKeHoachTapHienTai = taiKeHoachTapHoiVien

