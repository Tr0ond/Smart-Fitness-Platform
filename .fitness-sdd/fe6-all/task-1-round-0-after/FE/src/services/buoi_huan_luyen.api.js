import ketNoiApi from './api.js'
import { laIdHoiVienHopLe } from './hoi_vien_pt.api.js'

const MAU_UUID_V4 = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i

function taoLoiDauVao(thongBao, fieldErrors = {}) {
  return {
    httpStatus: 422,
    code: 'INVALID_PT_DIRECT_SESSION_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function layAssignmentIdAnToan(giaTri) {
  if (!laIdHoiVienHopLe(giaTri)) {
    throw taoLoiDauVao('Assignment buổi huấn luyện không hợp lệ.', {
      assignment_id: ['ID assignment phải là số nguyên dương.'],
    })
  }

  return Number(giaTri)
}

function taoHeaderIdempotency(khoa) {
  if (typeof khoa !== 'string' || !MAU_UUID_V4.test(khoa)) {
    throw taoLoiDauVao('Khóa xác nhận buổi tập phải là UUIDv4.', {
      idempotency_key: ['Khóa thao tác không hợp lệ.'],
    })
  }

  return { 'Idempotency-Key': khoa }
}

/** Tai 100 buoi PT gan nhat cua PT hien tai; FE loc Member va luon bao ro gioi han. */
export async function taiLichSuBuoiHuanLuyen() {
  const phanHoi = await ketNoiApi.get('/pt/direct-sessions')

  return phanHoi.data
}

/**
 * Xac nhan mot buoi theo assignment hien tai va giu UUIDv4 theo logical action.
 * Input: assignment_id tu current Member detail, notes tuy chon va Idempotency-Key.
 * Process: chi gui hai truong Backend cho phep; Backend kiem tra exact term/quota va transaction.
 * Output: envelope history/usage do Backend tra ve, ke ca ket qua replay cung khoa.
 * Side effect: Backend moi kich hoat term/tru mot luot; FE khong tinh quota.
 */
export async function hoanTatBuoiHuanLuyen(assignmentId, notes, idempotencyKey) {
  const body = { assignment_id: layAssignmentIdAnToan(assignmentId) }

  if (notes !== undefined) {
    if (notes !== null && (typeof notes !== 'string' || notes.length > 1000)) {
      throw taoLoiDauVao('Ghi chú không được dài quá 1000 ký tự.', {
        notes: ['Ghi chú không được dài quá 1000 ký tự.'],
      })
    }
    body.notes = notes
  }

  const phanHoi = await ketNoiApi.post('/pt/direct-sessions/complete', body, {
    headers: taoHeaderIdempotency(idempotencyKey),
  })

  return phanHoi.data
}
