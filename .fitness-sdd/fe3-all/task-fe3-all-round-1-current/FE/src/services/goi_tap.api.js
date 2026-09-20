import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_GOI_TAP = Object.freeze(['DANG_BAN', 'NGUNG_BAN'])

const CAC_KHOA_QUYEN_LOI = Object.freeze([
  'gym_access',
  'fitness_assistant',
  'fitness_assistant_limit',
  'trainer_chat',
  'direct_trainer_sessions',
])

function taoLoiDauVao(message, fieldErrors = {}) {
  return {
    httpStatus: 422,
    code: 'INVALID_PACKAGE_REQUEST',
    message,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

export function laIdGoiTapHopLe(value) {
  return (typeof value === 'number' && Number.isSafeInteger(value) && value > 0)
    || (typeof value === 'string' && /^[1-9]\d*$/.test(value.trim()) && Number.isSafeInteger(Number(value.trim())))
}

function layId(value) {
  if (!laIdGoiTapHopLe(value)) {
    throw taoLoiDauVao('ID goi tap khong hop le.', { id: ['ID goi tap phai la so nguyen duong.'] })
  }
  return Number(value)
}

function layChuoi(value, field, { required = false, max = 255 } = {}) {
  if (value === undefined && !required) return undefined
  if (typeof value !== 'string' || (required && value.trim() === '') || value.length > max) {
    throw taoLoiDauVao('Du lieu goi tap khong hop le.', { [field]: ['Gia tri khong hop le.'] })
  }
  return value.trim()
}

function laySoNguyen(value, field, { required = false, min = 0 } = {}) {
  if (value === undefined && !required) return undefined
  const number = typeof value === 'number' ? value : Number(value)
  if (!Number.isSafeInteger(number) || number < min) {
    throw taoLoiDauVao('Du lieu goi tap khong hop le.', { [field]: ['Gia tri phai la so nguyen hop le.'] })
  }
  return number
}

function laySoTien(value, field, { required = false, min = 0 } = {}) {
  if (value === undefined && !required) return undefined
  const number = typeof value === 'number' ? value : Number(value)
  if (!Number.isFinite(number) || number < min) {
    throw taoLoiDauVao('Du lieu goi tap khong hop le.', { [field]: ['Gia tri phai la so hop le.'] })
  }
  return number
}

function layTrangThai(value, required = false) {
  const status = value === undefined && !required ? undefined : String(value ?? '').trim()
  if (status === undefined) return undefined
  if (!CAC_TRANG_THAI_GOI_TAP.includes(status)) {
    throw taoLoiDauVao('Trang thai goi tap khong hop le.', { status: ['Trang thai khong hop le.'] })
  }
  return status
}

function taoQuyenLoi(value = {}) {
  if (value === null || typeof value !== 'object' || Array.isArray(value)) {
    throw taoLoiDauVao('Quyen loi goi tap khong hop le.', { benefits: ['Quyen loi phai la object.'] })
  }

  const body = {}
  for (const key of CAC_KHOA_QUYEN_LOI) {
    if (!Object.prototype.hasOwnProperty.call(value, key)) continue
    if (key === 'fitness_assistant' || key === 'gym_access' || key === 'trainer_chat') {
      if (typeof value[key] !== 'boolean') {
        throw taoLoiDauVao('Quyen loi goi tap khong hop le.', { [`benefits.${key}`]: ['Gia tri phai la boolean.'] })
      }
      body[key] = value[key]
    } else {
      body[key] = value[key] === null ? null : laySoNguyen(value[key], `benefits.${key}`, { min: 0 })
    }
  }

  for (const key of ['gym_access', 'fitness_assistant', 'trainer_chat', 'fitness_assistant_limit', 'direct_trainer_sessions']) {
    if (!Object.prototype.hasOwnProperty.call(body, key)) {
      throw taoLoiDauVao('Can khai bao day du quyen loi goi tap.', { benefits: ['Thieu khoa quyen loi.'] })
    }
  }
  if (body.fitness_assistant && body.fitness_assistant_limit !== null && body.fitness_assistant_limit <= 0) {
    throw taoLoiDauVao('Gioi han tro ly phai lon hon 0 khi bat tro ly.', {
      'benefits.fitness_assistant_limit': ['Nhap null hoac so nguyen duong.'],
    })
  }
  if (!body.fitness_assistant && body.fitness_assistant_limit !== 0) {
    throw taoLoiDauVao('Gioi han tro ly phai bang 0 khi tat tro ly.', {
      'benefits.fitness_assistant_limit': ['Gia tri phai bang 0.'],
    })
  }
  return body
}

function taoPayloadGoiTap(value = {}, { partial = false } = {}) {
  const body = {}
  for (const field of ['code', 'name', 'description']) {
    const current = layChuoi(value[field], field, { required: !partial && field !== 'description', max: field === 'description' ? 2000 : 120 })
    if (current !== undefined) body[field] = current
  }
  const price = laySoTien(value.price, 'price', { required: !partial, min: 0 })
  const duration = laySoNguyen(value.duration_days, 'duration_days', { required: !partial, min: 1 })
  if (price !== undefined) body.price = price
  if (duration !== undefined) body.duration_days = duration
  const status = layTrangThai(value.status, !partial)
  if (status !== undefined) body.status = status
  if (!partial || Object.prototype.hasOwnProperty.call(value, 'benefits')) {
    body.benefits = taoQuyenLoi(value.benefits)
  }
  return body
}

export function taoPayloadQuyenLoiGoiTap(value) {
  return taoQuyenLoi(value)
}

/** GET danh sach goi tap; Backend la authority cho status va configuration_version. */
export async function taiDanhSachGoiTap() {
  const response = await ketNoiApi.get('/admin/packages')
  return response.data
}

/** GET detail goi tap theo ID da validate; khong doc branch/creator tu client. */
export async function taiChiTietGoiTap(id) {
  const response = await ketNoiApi.get(`/admin/packages/${layId(id)}`)
  return response.data
}

/** POST goi tap voi metadata va benefits day du theo contract. */
export async function taoGoiTap(value = {}) {
  const response = await ketNoiApi.post('/admin/packages', taoPayloadGoiTap(value))
  return response.data
}

/** PATCH metadata/status goi tap; khong gui snapshot hay authority field. */
export async function capNhatGoiTap(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/packages/${layId(id)}`, taoPayloadGoiTap(value, { partial: true }))
  return response.data
}

/** PUT thay the quyen loi; snapshot ky cu do Backend bao toan. */
export async function thayTheQuyenLoiGoiTap(id, value = {}) {
  const response = await ketNoiApi.put(`/admin/packages/${layId(id)}/benefits`, taoQuyenLoi(value))
  return response.data
}
