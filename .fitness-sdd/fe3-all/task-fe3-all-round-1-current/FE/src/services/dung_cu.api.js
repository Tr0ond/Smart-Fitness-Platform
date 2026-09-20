import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_DUNG_CU = Object.freeze(['HOAT_DONG', 'NGUNG_SU_DUNG'])

function taoLoiDauVao(message, fieldErrors = {}) {
  return {
    httpStatus: 422,
    code: 'INVALID_EQUIPMENT_REQUEST',
    message,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

export function laIdDungCuHopLe(value) {
  return (typeof value === 'number' && Number.isSafeInteger(value) && value > 0)
    || (typeof value === 'string' && /^[1-9]\d*$/.test(value.trim()) && Number.isSafeInteger(Number(value.trim())))
}

function layId(value) {
  if (!laIdDungCuHopLe(value)) throw taoLoiDauVao('ID dung cu khong hop le.', { id: ['ID phai la so nguyen duong.'] })
  return Number(value)
}

function layPayload(value = {}, partial = false) {
  const body = {}
  for (const field of ['code', 'name', 'description']) {
    if (partial && field === 'code') continue
    if (value[field] === undefined && partial) continue
    if (typeof value[field] !== 'string' || (field !== 'description' && value[field].trim() === '')) {
      throw taoLoiDauVao('Du lieu dung cu khong hop le.', { [field]: ['Gia tri khong hop le.'] })
    }
    body[field] = value[field].trim()
  }
  if (!partial || value.status !== undefined) {
    if (!CAC_TRANG_THAI_DUNG_CU.includes(value.status)) throw taoLoiDauVao('Trang thai dung cu khong hop le.', { status: ['Trang thai khong hop le.'] })
    body.status = value.status
  }
  return body
}

/** GET equipment catalog, including records marked NGUNG_SU_DUNG. */
export async function taiDanhSachDungCu() {
  const response = await ketNoiApi.get('/admin/equipment')
  return response.data
}

/** POST equipment with immutable code established by Backend. */
export async function taoDungCu(value = {}) {
  const response = await ketNoiApi.post('/admin/equipment', layPayload(value))
  return response.data
}

/** PATCH equipment metadata/status; code is deliberately omitted. */
export async function capNhatDungCu(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/equipment/${layId(id)}`, layPayload(value, true))
  return response.data
}
