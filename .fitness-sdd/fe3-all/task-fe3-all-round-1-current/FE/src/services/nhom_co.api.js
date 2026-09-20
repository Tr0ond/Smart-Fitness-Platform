import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_NHOM_CO = Object.freeze(['HOAT_DONG', 'NGUNG_SU_DUNG'])

function taoLoiDauVao(message, fieldErrors = {}) {
  return {
    httpStatus: 422,
    code: 'INVALID_MUSCLE_GROUP_REQUEST',
    message,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

export function laIdNhomCoHopLe(value) {
  return (typeof value === 'number' && Number.isSafeInteger(value) && value > 0)
    || (typeof value === 'string' && /^[1-9]\d*$/.test(value.trim()) && Number.isSafeInteger(Number(value.trim())))
}

function layId(value) {
  if (!laIdNhomCoHopLe(value)) throw taoLoiDauVao('ID nhom co khong hop le.', { id: ['ID phai la so nguyen duong.'] })
  return Number(value)
}

function layPayload(value = {}, partial = false) {
  const body = {}
  for (const field of ['code', 'name', 'description']) {
    if (value[field] === undefined && partial) continue
    if (typeof value[field] !== 'string' || (field !== 'description' && value[field].trim() === '')) {
      throw taoLoiDauVao('Du lieu nhom co khong hop le.', { [field]: ['Gia tri khong hop le.'] })
    }
    body[field] = value[field].trim()
  }
  if (!partial || value.status !== undefined) {
    if (!CAC_TRANG_THAI_NHOM_CO.includes(value.status)) throw taoLoiDauVao('Trang thai nhom co khong hop le.', { status: ['Trang thai khong hop le.'] })
    body.status = value.status
  }
  if (value.muscle_groups !== undefined) {
    if (!Array.isArray(value.muscle_groups)) throw taoLoiDauVao('Quan he nhom co khong hop le.', { muscle_groups: ['Phai la mang.'] })
    body.muscle_groups = value.muscle_groups.map((item) => ({ id: layId(item.id), role: item.role }))
  }
  return body
}

/** GET muscle groups and retain inactive groups for relation reconciliation. */
export async function taiDanhSachNhomCo() {
  const response = await ketNoiApi.get('/admin/muscle-groups')
  return response.data
}

/** POST a muscle group with HOAT_DONG/NGUNG_SU_DUNG lifecycle status. */
export async function taoNhomCo(value = {}) {
  const response = await ketNoiApi.post('/admin/muscle-groups', layPayload(value))
  return response.data
}

/** PATCH muscle group metadata/status; no client-side audit or hard delete. */
export async function capNhatNhomCo(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/muscle-groups/${layId(id)}`, layPayload(value, true))
  return response.data
}
