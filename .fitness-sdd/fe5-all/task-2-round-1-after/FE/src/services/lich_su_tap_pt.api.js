import ketNoiApi from './api.js'
import { laIdHoiVienHopLe } from './hoi_vien_pt.api.js'

function taoLoiDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: 'INVALID_PT_HISTORY_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function laIdDuong(giaTri) {
  if (typeof giaTri === 'number') return Number.isSafeInteger(giaTri) && giaTri > 0
  return typeof giaTri === 'string'
    && /^[1-9]\d*$/.test(giaTri.trim())
    && Number.isSafeInteger(Number(giaTri.trim()))
}

function layIdAnToan(giaTri, truong = 'member') {
  if (!(truong === 'member' ? laIdHoiVienHopLe(giaTri) : laIdDuong(giaTri))) {
    throw taoLoiDauVao('Không thể truy cập lịch sử buổi tập này.', {
      [truong]: ['ID phải là số nguyên dương.'],
    }, 404)
  }

  return String(giaTri).trim()
}

function taoQuery(boLoc = {}) {
  const params = {}
  if (boLoc.limit !== undefined && boLoc.limit !== null && boLoc.limit !== '') {
    const limit = Number(boLoc.limit)
    if (!Number.isSafeInteger(limit) || limit < 1 || limit > 100) {
      throw taoLoiDauVao('Giới hạn lịch sử phải từ 1 đến 100.', { limit: ['Giá trị từ 1 đến 100.'] })
    }
    params.limit = limit
  }
  if (boLoc.before_id !== undefined && boLoc.before_id !== null && boLoc.before_id !== '') {
    const beforeId = Number(boLoc.before_id)
    if (!Number.isSafeInteger(beforeId) || beforeId < 1) {
      throw taoLoiDauVao('Con trỏ lịch sử không hợp lệ.', { before_id: ['Giá trị phải là số nguyên dương.'] })
    }
    params.before_id = beforeId
  }
  return params
}

/** Tai danh sach session immutable theo cursor ID giam dan. */
export async function taiLichSuTapHoiVien(memberId, boLoc = {}) {
  const id = layIdAnToan(memberId)
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}/workout/sessions`, {
    params: taoQuery(boLoc),
  })

  return phanHoi.data
}

/** Tai snapshot session immutable va bind lai ownership Member tai Backend. */
export async function taiChiTietLichSuTapHoiVien(memberId, sessionId) {
  const member = layIdAnToan(memberId)
  const session = layIdAnToan(sessionId, 'session')
  const phanHoi = await ketNoiApi.get(`/pt/members/${member}/workout/sessions/${session}`)

  return phanHoi.data
}

export const taiLichSuTap = taiLichSuTapHoiVien
export const taiChiTietLichSuTap = taiChiTietLichSuTapHoiVien
