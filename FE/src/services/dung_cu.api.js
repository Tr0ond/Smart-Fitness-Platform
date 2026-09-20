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
  if (!laIdDungCuHopLe(value)) throw taoLoiDauVao('ID dụng cụ không hợp lệ.', { id: ['ID phải là số nguyên dương.'] })
  return Number(value)
}

function layPayload(value = {}, partial = false) {
  const body = {}
  for (const field of ['code', 'name', 'description']) {
    if (partial && field === 'code') continue
    if (value[field] === undefined && partial) continue
    if (field === 'description' && value[field] === null) {
      body[field] = null
      continue
    }
    if (typeof value[field] !== 'string' || (field !== 'description' && value[field].trim() === '')) {
      throw taoLoiDauVao('Dữ liệu dụng cụ không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
    }
    body[field] = value[field].trim()
  }
  if (!partial || value.status !== undefined) {
    if (!CAC_TRANG_THAI_DUNG_CU.includes(value.status)) throw taoLoiDauVao('Trạng thái dụng cụ không hợp lệ.', { status: ['Trạng thái không hợp lệ.'] })
    body.status = value.status
  }
  return body
}

/**
 * Mục đích: tải toàn bộ danh mục dụng cụ cho Admin.
 * Đầu vào: không có; Backend quyết định scope và gồm cả bản ghi ngừng dùng.
 * Xử lý: phát GET qua API client có auth interceptor.
 * Kết quả: envelope danh sách dụng cụ authoritative.
 * Side effect: chỉ đọc; không xóa quan hệ bài tập hay lịch sử.
 * Quy tắc/Contract: lifecycle catalog dùng PATCH status, cấm hard-delete.
 */
export async function taiDanhSachDungCu() {
  const response = await ketNoiApi.get('/admin/equipment')
  return response.data
}

/**
 * Mục đích: tạo bản ghi dụng cụ mới.
 * Đầu vào: code/name bắt buộc, description tùy chọn và status hợp lệ.
 * Xử lý: chuẩn hóa payload allow-list rồi POST một lần.
 * Kết quả: DTO dụng cụ mới hoặc lỗi 422 đã chuẩn hóa.
 * Side effect: tạo catalog record; code do Backend xác lập bất biến.
 * Quy tắc/Contract: không gửi authority field và không retry mù.
 */
export async function taoDungCu(value = {}) {
  const response = await ketNoiApi.post('/admin/equipment', layPayload(value))
  return response.data
}

/**
 * Mục đích: cập nhật metadata hoặc trạng thái dụng cụ.
 * Đầu vào: id dương và payload partial; code luôn bị loại.
 * Xử lý: kiểm tra field hiện diện rồi PATCH một lần.
 * Kết quả: DTO authoritative hoặc lỗi validation từ Backend.
 * Side effect: giữ quan hệ bài tập hiện hữu; không hard-delete.
 * Quy tắc/Contract: trạng thái nhạy cảm do page xác nhận trước khi gọi.
 */
export async function capNhatDungCu(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/equipment/${layId(id)}`, layPayload(value, true))
  return response.data
}
