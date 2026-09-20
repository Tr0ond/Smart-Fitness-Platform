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
  if (!laIdNhomCoHopLe(value)) throw taoLoiDauVao('ID nhóm cơ không hợp lệ.', { id: ['ID phải là số nguyên dương.'] })
  return Number(value)
}

function layPayload(value = {}, partial = false) {
  const body = {}
  for (const field of ['code', 'name', 'description']) {
    if (value[field] === undefined && partial) continue
    if (field === 'description' && value[field] === null) {
      body[field] = null
      continue
    }
    if (typeof value[field] !== 'string' || (field !== 'description' && value[field].trim() === '')) {
      throw taoLoiDauVao('Dữ liệu nhóm cơ không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
    }
    body[field] = value[field].trim()
  }
  if (!partial || value.status !== undefined) {
    if (!CAC_TRANG_THAI_NHOM_CO.includes(value.status)) throw taoLoiDauVao('Trạng thái nhóm cơ không hợp lệ.', { status: ['Trạng thái không hợp lệ.'] })
    body.status = value.status
  }
  if (value.muscle_groups !== undefined) {
    if (!Array.isArray(value.muscle_groups)) throw taoLoiDauVao('Quan hệ nhóm cơ không hợp lệ.', { muscle_groups: ['Phải là mảng.'] })
    body.muscle_groups = value.muscle_groups.map((item) => ({ id: layId(item.id), role: item.role }))
  }
  return body
}

/**
 * Mục đích: tải cả nhóm cơ đang hoạt động và ngừng sử dụng.
 * Đầu vào: không có; danh sách do Backend phân quyền.
 * Xử lý: phát GET qua API client.
 * Kết quả: envelope nhóm cơ dùng cho M061 relation reconciliation.
 * Side effect: chỉ đọc; không thay đổi pivot bài tập.
 * Quy tắc/Contract: M061 yêu cầu giữ inactive để echo đúng id/role.
 */
export async function taiDanhSachNhomCo() {
  const response = await ketNoiApi.get('/admin/muscle-groups')
  return response.data
}

/**
 * Mục đích: tạo nhóm cơ với vòng đời M061.
 * Đầu vào: code/name bắt buộc, description tùy chọn và enum status.
 * Xử lý: dựng payload allow-list rồi POST một lần.
 * Kết quả: DTO nhóm cơ mới hoặc lỗi 422.
 * Side effect: không tạo pivot và không ghi audit ở client.
 * Quy tắc/Contract: Backend áp dụng default/status và chặn dữ liệu không hợp lệ.
 */
export async function taoNhomCo(value = {}) {
  const response = await ketNoiApi.post('/admin/muscle-groups', layPayload(value))
  return response.data
}

/**
 * Mục đích: cập nhật metadata hoặc trạng thái nhóm cơ.
 * Đầu vào: id dương và payload partial; relation replacement chỉ gửi khi caller yêu cầu.
 * Xử lý: chuẩn hóa enum và giữ nguyên mảng inactive nếu được cung cấp.
 * Kết quả: DTO authoritative hoặc lỗi immutable M061.
 * Side effect: Backend chịu trách nhiệm transaction, row lock và audit.
 * Quy tắc/Contract: không hard-delete; same-state no-op và CAP_NHAT_TRANG_THAI_NHOM_CO do Backend.
 */
export async function capNhatNhomCo(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/muscle-groups/${layId(id)}`, layPayload(value, true))
  return response.data
}
