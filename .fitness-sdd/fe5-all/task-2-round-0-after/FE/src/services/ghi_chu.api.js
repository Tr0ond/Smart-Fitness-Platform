import ketNoiApi from './api.js'
import { laIdHoiVienHopLe } from './hoi_vien_pt.api.js'

function taoLoiDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: 'INVALID_PT_NOTE_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function layIdAnToan(giaTri) {
  if (!laIdHoiVienHopLe(giaTri)) {
    throw taoLoiDauVao('Không thể truy cập ghi chú của học viên này.', {
      member: ['ID học viên phải là số nguyên dương.'],
    }, 404)
  }

  return String(giaTri).trim()
}

function layIdLienKet(giaTri, truong) {
  if (giaTri === undefined || giaTri === null || giaTri === '') return undefined
  const so = Number(giaTri)
  if (!Number.isSafeInteger(so) || so < 1) {
    throw taoLoiDauVao('ID liên kết ghi chú không hợp lệ.', { [truong]: ['ID phải là số nguyên dương.'] }, 422)
  }
  return so
}

function taoBody(duLieu = {}) {
  const giaTri = Object.prototype.hasOwnProperty.call(duLieu, 'content')
    ? duLieu.content
    : duLieu.noi_dung

  if (typeof giaTri !== 'string' || giaTri.trim() === '') {
    throw taoLoiDauVao('Nội dung ghi chú không được để trống.', {
      content: ['Nội dung ghi chú không được để trống.'],
    })
  }

  const body = { content: giaTri.trim() }
  const planId = layIdLienKet(duLieu.plan_id, 'plan_id')
  const sessionId = layIdLienKet(duLieu.session_id, 'session_id')
  if (planId !== undefined) body.plan_id = planId
  if (sessionId !== undefined) body.session_id = sessionId
  return body
}

/** Tai toi da 100 ghi chu cua assignment PT hien tai theo thu tu moi nhat. */
export async function taiDanhSachGhiChu(memberId) {
  const id = layIdAnToan(memberId)
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}/notes`)

  return phanHoi.data
}

/**
 * Them note append-only; khong blind retry vi POST khong co idempotency contract.
 *
 * Input: content (hoac noi_dung) va lien ket Plan/Session tuy chon da allow-list.
 * Process: Backend revalidate exact assignment va transaction/audit.
 * Output: note moi trong envelope Backend.
 * Side effect: chi append note; khong edit/delete Plan hay Workout Session.
 */
export async function themGhiChuHuanLuyen(memberId, duLieu = {}) {
  const id = layIdAnToan(memberId)
  const phanHoi = await ketNoiApi.post(`/pt/members/${id}/notes`, taoBody(duLieu))

  return phanHoi.data
}

export const taoGhiChuHuanLuyen = themGhiChuHuanLuyen

