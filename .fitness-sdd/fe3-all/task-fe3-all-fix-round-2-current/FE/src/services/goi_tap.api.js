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
    throw taoLoiDauVao('ID gói tập không hợp lệ.', { id: ['ID gói tập phải là số nguyên dương.'] })
  }
  return Number(value)
}

function layChuoi(value, field, { required = false, nullable = false, max = 255 } = {}) {
  if (value === undefined && !required) return undefined
  if (value === null && nullable) return null
  if (typeof value !== 'string' || (required && value.trim() === '') || value.length > max) {
    throw taoLoiDauVao('Dữ liệu gói tập không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
  }
  return value.trim()
}

function laySoNguyen(value, field, { required = false, min = 0, max = Number.MAX_SAFE_INTEGER } = {}) {
  if (value === undefined && !required) return undefined
  const number = typeof value === 'number'
    ? value
    : (typeof value === 'string' && value.trim() !== '' ? Number(value) : NaN)
  if (!Number.isSafeInteger(number) || number < min || number > max) {
    throw taoLoiDauVao('Dữ liệu gói tập không hợp lệ.', { [field]: ['Giá trị phải là số nguyên hợp lệ.'] })
  }
  return number
}

function laySoTien(value, field, { required = false, min = 1, max = 999999999999999 } = {}) {
  if (value === undefined && !required) return undefined
  const number = typeof value === 'number'
    ? value
    : (typeof value === 'string' && value.trim() !== '' ? Number(value) : NaN)
  if (!Number.isSafeInteger(number) || number < min || number > max) {
    throw taoLoiDauVao('Dữ liệu gói tập không hợp lệ.', { [field]: ['Giá trị phải là số nguyên từ 1 đến 999999999999999.'] })
  }
  return number
}

function layTrangThai(value, required = false) {
  const status = value === undefined && !required ? undefined : String(value ?? '').trim()
  if (status === undefined) return undefined
  if (!CAC_TRANG_THAI_GOI_TAP.includes(status)) {
    throw taoLoiDauVao('Trạng thái gói tập không hợp lệ.', { status: ['Trạng thái không hợp lệ.'] })
  }
  return status
}

function taoQuyenLoi(value = {}) {
  if (value === null || typeof value !== 'object' || Array.isArray(value)) {
    throw taoLoiDauVao('Quyền lợi gói tập không hợp lệ.', { benefits: ['Quyền lợi phải là object.'] })
  }

  const body = {}
  for (const key of CAC_KHOA_QUYEN_LOI) {
    if (!Object.prototype.hasOwnProperty.call(value, key)) continue
    if (key === 'fitness_assistant' || key === 'gym_access' || key === 'trainer_chat') {
      if (typeof value[key] !== 'boolean') {
        throw taoLoiDauVao('Quyền lợi gói tập không hợp lệ.', { [`benefits.${key}`]: ['Giá trị phải là boolean.'] })
      }
      body[key] = value[key]
    } else if (key === 'fitness_assistant_limit') {
      body[key] = value[key] === null ? null : laySoNguyen(value[key], `benefits.${key}`, { min: 0, max: 4294967295 })
    } else {
      body[key] = laySoNguyen(value[key], `benefits.${key}`, { min: 0, max: 65535 })
    }
  }

  for (const key of ['gym_access', 'fitness_assistant', 'trainer_chat', 'fitness_assistant_limit', 'direct_trainer_sessions']) {
    if (!Object.prototype.hasOwnProperty.call(body, key)) {
      throw taoLoiDauVao('Cần khai báo đầy đủ quyền lợi gói tập.', { benefits: ['Thiếu khóa quyền lợi.'] })
    }
  }
  if (body.fitness_assistant && body.fitness_assistant_limit !== null && body.fitness_assistant_limit <= 0) {
    throw taoLoiDauVao('Giới hạn trợ lý phải lớn hơn 0 khi bật trợ lý.', {
      'benefits.fitness_assistant_limit': ['Nhập null hoặc số nguyên dương.'],
    })
  }
  if (!body.fitness_assistant && body.fitness_assistant_limit !== 0) {
    throw taoLoiDauVao('Giới hạn trợ lý phải bằng 0 khi tắt trợ lý.', {
      'benefits.fitness_assistant_limit': ['Giá trị phải bằng 0.'],
    })
  }
  if (!body.gym_access
    && !body.fitness_assistant
    && !body.trainer_chat
    && body.direct_trainer_sessions === 0) {
    throw taoLoiDauVao('Gói tập phải có ít nhất một quyền lợi đang bật.', {
      benefits: ['Bật Gym, trợ lý, chat với PT hoặc số buổi PT trực tiếp lớn hơn 0.'],
    })
  }
  return body
}

function taoPayloadGoiTap(value = {}, { partial = false } = {}) {
  const body = {}
  for (const field of ['code', 'name', 'description']) {
    const current = layChuoi(value[field], field, {
      required: !partial && field !== 'description',
      nullable: field === 'description',
      max: field === 'description' ? 2000 : (field === 'code' ? 30 : 150),
    })
    if (current !== undefined) body[field] = current
  }
  const price = laySoTien(value.price, 'price', { required: !partial, min: 1, max: 999999999999999 })
  const duration = laySoNguyen(value.duration_days, 'duration_days', { required: !partial, min: 1, max: 65535 })
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

/**
 * Mục đích: tải danh sách gói tập cho màn hình Admin.
 * Đầu vào: không có; Backend áp dụng scope và trạng thái authoritative.
 * Xử lý: phát một GET qua interceptor auth hiện có.
 * Kết quả: trả về envelope danh sách, bao gồm configuration_version nếu Backend cung cấp.
 * Side effect: chỉ đọc; không thay đổi quyền lợi, snapshot hoặc trạng thái.
 * Quy tắc/Contract: auth:api + role:ADMIN và Backend là authority.
 */
export async function taiDanhSachGoiTap() {
  const response = await ketNoiApi.get('/admin/packages')
  return response.data
}

/**
 * Mục đích: tải chi tiết một gói tập sau khi kiểm tra id.
 * Đầu vào: id gói tập dương và an toàn.
 * Xử lý: chuẩn hóa id rồi phát GET chi tiết.
 * Kết quả: trả về DTO/envelope do Backend xác nhận, gồm benefits và configuration_version.
 * Side effect: chỉ đọc; không suy ra entitlement hoặc quyền quản trị.
 * Quy tắc/Contract: không gửi branch_id, creator hay field authority từ client.
 */
export async function taiChiTietGoiTap(id) {
  const response = await ketNoiApi.get(`/admin/packages/${layId(id)}`)
  return response.data
}

/**
 * Mục đích: tạo gói tập mới cùng bộ quyền lợi hiệu lực.
 * Đầu vào: metadata hợp lệ và đủ năm khóa benefits, trong đó AI unlimited là null.
 * Xử lý: dựng allow-list rồi POST một lần qua API client.
 * Kết quả: trả envelope tạo mới; lỗi 422 chuẩn hóa trước khi gửi nếu invariant sai.
 * Side effect: một mutation Backend, không retry mù và không tự tính entitlement.
 * Quy tắc/Contract: Q01 snapshot, ít nhất một quyền lợi hiệu lực và authority field bị loại.
 */
export async function taoGoiTap(value = {}) {
  const response = await ketNoiApi.post('/admin/packages', taoPayloadGoiTap(value))
  return response.data
}

/**
 * Mục đích: cập nhật các field gói tập được phép trong một PATCH.
 * Đầu vào: id dương và payload partial; status chỉ dùng khi caller đã xác nhận chuyển trạng thái.
 * Xử lý: chuẩn hóa nullable description, price/duration integer và loại authority fields.
 * Kết quả: trả envelope Backend; không gửi benefits hay days trong metadata update.
 * Side effect: một mutation, không hard delete và không retry mù.
 * Quy tắc/Contract: Q01 giữ snapshot kỳ cũ; sensitive status phải đi qua confirmation của page.
 */
export async function capNhatGoiTap(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/packages/${layId(id)}`, taoPayloadGoiTap(value, { partial: true }))
  return response.data
}

/**
 * Mục đích: thay thế trọn bộ quyền lợi cho các lượt mua mới.
 * Đầu vào: đủ benefits hợp lệ; AI disabled dùng 0, unlimited dùng null.
 * Xử lý: kiểm tra invariant ít nhất một quyền lợi hiệu lực rồi PUT allow-list.
 * Kết quả: trả envelope Backend; snapshot các kỳ cũ không bị thay đổi.
 * Side effect: một mutation và không retry mù.
 * Quy tắc/Contract: Q01 và Backend giữ quyền sở hữu/snapshot, client không suy quyền.
 */
export async function thayTheQuyenLoiGoiTap(id, value = {}) {
  const response = await ketNoiApi.put(`/admin/packages/${layId(id)}/benefits`, taoQuyenLoi(value))
  return response.data
}
