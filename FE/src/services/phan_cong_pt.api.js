import ketNoiApi from './api.js'

function taoLoiDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: 'INVALID_ASSIGNMENT_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

export function laIdHoSoHopLe(giaTri) {
  if (typeof giaTri === 'number') {
    return Number.isSafeInteger(giaTri) && giaTri > 0
  }

  return typeof giaTri === 'string'
    && /^[1-9]\d*$/.test(giaTri.trim())
    && Number.isSafeInteger(Number(giaTri.trim()))
}

function layIdAnToan(giaTri, truong) {
  if (!laIdHoSoHopLe(giaTri)) {
    throw taoLoiDauVao('ID hồ sơ PT/Hội viên không hợp lệ.', {
      [truong]: ['ID hồ sơ phải là số nguyên dương.'],
    })
  }

  return Number(giaTri)
}

function layThoiGian(duLieu, truong) {
  if (!Object.prototype.hasOwnProperty.call(duLieu ?? {}, truong)) {
    return undefined
  }
  const giaTri = duLieu?.[truong]
  if (giaTri === null || giaTri === '') {
    return null
  }
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) {
    throw taoLoiDauVao('Mốc thời gian không hợp lệ.', {
      [truong]: ['Mốc thời gian không hợp lệ.'],
    })
  }

  return giaTri
}

function layLyDo(duLieu) {
  if (!Object.prototype.hasOwnProperty.call(duLieu ?? {}, 'reason')) {
    return undefined
  }
  const reason = duLieu?.reason
  if (reason === null || reason === '') {
    return null
  }
  if (typeof reason !== 'string') {
    throw taoLoiDauVao('Lý do không hợp lệ.', { reason: ['Lý do không hợp lệ.'] })
  }

  return reason.trim()
}

function taoQuery(boLoc = {}) {
  const params = {}
  for (const [truong, key] of [['member_id', 'member_id'], ['trainer_id', 'trainer_id']]) {
    if (boLoc?.[truong] !== undefined && boLoc?.[truong] !== '') {
      params[key] = layIdAnToan(boLoc[truong], truong)
    }
  }
  if (boLoc?.current !== undefined && boLoc?.current !== '') {
    if (typeof boLoc.current !== 'boolean') {
      throw taoLoiDauVao('Bộ lọc hiện tại không hợp lệ.', { current: ['Chỉ nhận true hoặc false.'] })
    }
    params.current = boLoc.current
  }
  for (const truong of ['page', 'per_page']) {
    if (boLoc?.[truong] !== undefined && boLoc?.[truong] !== '') {
      const so = Number(boLoc[truong])
      if (!Number.isInteger(so) || so < 1 || (truong === 'per_page' && so > 100)) {
        throw taoLoiDauVao('Phân trang không hợp lệ.', { [truong]: ['Giá trị phân trang không hợp lệ.'] })
      }
      params[truong] = so
    }
  }

  return params
}

/** GET Admin assignment list với filter allow-list duy nhất. */
export async function taiDanhSachPhanCong(boLoc = {}) {
  const phanHoi = await ketNoiApi.get('/pt/assignments', { params: taoQuery(boLoc) })
  return phanHoi.data
}

/** GET Admin assignment detail theo profile assignment ID. */
export async function taiChiTietPhanCong(assignmentId) {
  if (!laIdHoSoHopLe(assignmentId)) {
    throw taoLoiDauVao('ID phân công không hợp lệ.', { assignment_id: ['ID phân công không hợp lệ.'] })
  }
  const phanHoi = await ketNoiApi.get(`/pt/assignments/${Number(assignmentId)}`)
  return phanHoi.data
}

/** POST create không thêm Idempotency-Key theo contract assignment. */
export async function taoPhanCong(duLieu = {}) {
  const body = {
    member_id: layIdAnToan(duLieu.member_id, 'member_id'),
    trainer_id: layIdAnToan(duLieu.trainer_id, 'trainer_id'),
  }
  const startAt = layThoiGian(duLieu, 'start_at')
  const endAt = layThoiGian(duLieu, 'end_at')
  if (startAt !== undefined && startAt !== null) body.start_at = startAt
  if (endAt !== undefined && endAt !== null) body.end_at = endAt
  const phanHoi = await ketNoiApi.post('/pt/assignments', body)
  return phanHoi.data
}

/** PATCH end chỉ gửi reason; server tự ghi end_at. */
export async function ketThucPhanCong(assignmentId, duLieu = {}) {
  if (!laIdHoSoHopLe(assignmentId)) {
    throw taoLoiDauVao('ID phân công không hợp lệ.', { assignment_id: ['ID phân công không hợp lệ.'] })
  }
  const reason = layLyDo(duLieu)
  const body = {}
  if (reason !== undefined && reason !== null) body.reason = reason
  const phanHoi = await ketNoiApi.patch(`/pt/assignments/${Number(assignmentId)}/end`, body)
  return phanHoi.data
}

/** POST reassign chỉ gửi trainer/start/reason; không gửi branch/actor/end/idempotency. */
export async function phanCongLai(assignmentId, duLieu = {}) {
  if (!laIdHoSoHopLe(assignmentId)) {
    throw taoLoiDauVao('ID phân công không hợp lệ.', { assignment_id: ['ID phân công không hợp lệ.'] })
  }
  const body = { trainer_id: layIdAnToan(duLieu.trainer_id, 'trainer_id') }
  const startAt = layThoiGian(duLieu, 'start_at')
  const reason = layLyDo(duLieu)
  if (startAt !== undefined && startAt !== null) body.start_at = startAt
  if (reason !== undefined && reason !== null) body.reason = reason
  const phanHoi = await ketNoiApi.post(`/pt/assignments/${Number(assignmentId)}/reassign`, body)
  return phanHoi.data
}
