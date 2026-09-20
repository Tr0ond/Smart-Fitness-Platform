import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_GIAO_AN_MAU = Object.freeze(['HOAT_DONG', 'NGUNG_SU_DUNG'])

function taoLoiDauVao(message, fieldErrors = {}) {
  return {
    httpStatus: 422,
    code: 'INVALID_WORKOUT_TEMPLATE_REQUEST',
    message,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

export function laIdGiaoAnMauHopLe(value) {
  return (typeof value === 'number' && Number.isSafeInteger(value) && value > 0)
    || (typeof value === 'string' && /^[1-9]\d*$/.test(value.trim()) && Number.isSafeInteger(Number(value.trim())))
}

function layId(value, field = 'id') {
  if (!laIdGiaoAnMauHopLe(value)) throw taoLoiDauVao('ID giáo án mẫu không hợp lệ.', { [field]: ['ID phải là số nguyên dương.'] })
  return Number(value)
}

function layChuoi(value, field, { required = false, nullable = false, max = 255 } = {}) {
  if (value === undefined && !required) return undefined
  if (value === null && nullable) return null
  if (typeof value !== 'string' || (required && value.trim() === '') || value.length > max) throw taoLoiDauVao('Dữ liệu giáo án mẫu không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
  return value.trim()
}

function layTrangThai(value, required = false) {
  if (value === undefined && !required) return undefined
  if (!CAC_TRANG_THAI_GIAO_AN_MAU.includes(value)) throw taoLoiDauVao('Trạng thái giáo án mẫu không hợp lệ.', { status: ['Trạng thái không hợp lệ.'] })
  return value
}

function laySo(value, field, { required = false, min = 0, max = Number.MAX_SAFE_INTEGER } = {}) {
  if (value === undefined && !required) return undefined
  const number = Number(value)
  if (!Number.isSafeInteger(number) || number < min || number > max) throw taoLoiDauVao('Dữ liệu giáo án mẫu không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
  return number
}

function taoNgay(value, field = 'days', { required = false } = {}) {
  if (value === undefined && !required) return undefined
  if (!Array.isArray(value) || value.length === 0 || value.length > 7) throw taoLoiDauVao('Giáo án mẫu phải có ngày tập.', { [field]: ['Phải có từ 1 đến 7 ngày.'] })
  const orders = new Set()
  return value.map((day, dayIndex) => {
    const order = laySo(day?.order, `${field}.${dayIndex}.order`, { required: true, min: 1, max: 7 })
    if (orders.has(order)) throw taoLoiDauVao('Thứ tự ngày tập bị trùng.', { [field]: ['Thứ tự ngày phải duy nhất.'] })
    orders.add(order)
    const exercises = Array.isArray(day?.exercises) ? day.exercises : []
    const exerciseOrders = new Set()
    return {
      order,
      name: layChuoi(day?.name, `${field}.${dayIndex}.name`, { required: true, max: 150 }),
      estimated_minutes: laySo(day?.estimated_minutes, `${field}.${dayIndex}.estimated_minutes`, { required: true, min: 1, max: 65535 }),
      exercises: exercises.map((exercise, exerciseIndex) => {
        const exerciseOrder = laySo(exercise?.order, `${field}.${dayIndex}.exercises.${exerciseIndex}.order`, { required: true, min: 1, max: 65535 })
        if (exerciseOrders.has(exerciseOrder)) throw taoLoiDauVao('Thứ tự bài tập bị trùng.', { [field]: ['Thứ tự bài tập phải duy nhất trong ngày.'] })
        exerciseOrders.add(exerciseOrder)
        const minReps = laySo(exercise?.min_reps, 'min_reps', { required: true, min: 1, max: 65535 })
        const maxReps = laySo(exercise?.max_reps, 'max_reps', { required: true, min: minReps, max: 65535 })
        return {
          exercise_id: layId(exercise?.exercise_id, 'exercise_id'),
          order: exerciseOrder,
          target_sets: laySo(exercise?.target_sets, 'target_sets', { required: true, min: 1, max: 65535 }),
          min_reps: minReps,
          max_reps: maxReps,
          rest_seconds: laySo(exercise?.rest_seconds, 'rest_seconds', { required: true, min: 0, max: 65535 }),
          ...(exercise?.notes === undefined ? {} : { notes: layChuoi(exercise.notes, 'notes', { nullable: true, max: 1000 }) }),
        }
      }),
    }
  })
}

function taoPayloadMetadata(value = {}, { partial = false, includeDays = false } = {}) {
  const body = {}
  for (const field of ['code', 'name', 'goal', 'level', 'description']) {
    const current = layChuoi(value[field], field, {
      required: !partial && ['code', 'name', 'goal', 'level'].includes(field),
      nullable: field === 'description',
      max: field === 'description' ? 3000 : ({ code: 40, name: 150, goal: 100, level: 50 }[field] ?? 160),
    })
    if (current !== undefined) body[field] = current
  }
  const sessions = laySo(value.sessions_per_week, 'sessions_per_week', { required: !partial, min: 1, max: 7 })
  if (sessions !== undefined) body.sessions_per_week = sessions
  const status = layTrangThai(value.status, !partial)
  if (status !== undefined) body.status = status
  if (includeDays || !partial) {
    body.days = taoNgay(value.days, 'days', { required: true })
    if (body.sessions_per_week !== body.days.length) throw taoLoiDauVao('Số buổi mỗi tuần phải bằng số ngày.', { sessions_per_week: ['Không khớp số ngày.'] })
  }
  return body
}

/**
 * Mục đích: tải danh sách giáo án mẫu cho Admin.
 * Đầu vào: không có; scope và trạng thái do Backend quyết định.
 * Xử lý: phát một GET qua API client có interceptor auth.
 * Kết quả: envelope danh sách server-authoritative.
 * Side effect: chỉ đọc, không sửa metadata hay cây phiên bản.
 * Quy tắc/Contract: role ADMIN; không suy diễn ngày tập ở client.
 */
export async function taiDanhSachGiaoAnMau() {
  const response = await ketNoiApi.get('/admin/workout-templates')
  return response.data
}

/**
 * Mục đích: tải DTO chi tiết làm nguồn cho editor metadata hoặc revision.
 * Đầu vào: id giáo án mẫu là số nguyên dương.
 * Xử lý: kiểm tra id trước khi gọi GET.
 * Kết quả: DTO gồm metadata, content_version và cây ngày/bài hiện tại.
 * Side effect: chỉ đọc; không làm mới bản nháp đang nhập.
 * Quy tắc/Contract: Backend là authority của trạng thái và content_version.
 */
export async function taiChiTietGiaoAnMau(id) {
  const response = await ketNoiApi.get(`/admin/workout-templates/${layId(id)}`)
  return response.data
}

/**
 * Mục đích: tải nền giáo án authoritative trước khi mở trình soạn phiên bản.
 * Đầu vào: template ID được kiểm tra theo resource scope; xử lý: ủy quyền cho detail GET đã chuẩn hóa.
 * Kết quả: trả DTO metadata/cây cùng content_version do Backend xác nhận.
 * Side effect: chỉ một GET read-only; không mutate hoặc rebase bản nháp editor.
 * Quy tắc/Contract: copy-on-write giữ bản cũ bất biến và expected_content_version chỉ do Backend quyết định.
 */
export async function taiNenGiaoAnMau(id) {
  return taiChiTietGiaoAnMau(id)
}

/**
 * Mục đích: tạo giáo án mẫu và phiên bản cây đầu tiên.
 * Đầu vào: metadata bắt buộc và cây days đã đủ trường theo request contract.
 * Xử lý: chuẩn hóa nullable description/notes, kiểm tra thứ tự và POST một lần.
 * Kết quả: envelope giáo án do Backend tạo.
 * Side effect: một mutation; không retry mù và không hard-delete lịch sử.
 * Quy tắc/Contract: days 1..7, sessions_per_week khớp số ngày, status chỉ dùng enum.
 */
export async function taoGiaoAnMau(value = {}) {
  const response = await ketNoiApi.post('/admin/workout-templates', taoPayloadMetadata(value, { includeDays: true }))
  return response.data
}

/**
 * Mục đích: cập nhật metadata giáo án mẫu.
 * Đầu vào: id và payload partial; description có thể null.
 * Xử lý: dựng allow-list metadata rồi PATCH; tuyệt đối không thêm days.
 * Kết quả: envelope mới từ Backend hoặc lỗi 422 trước request.
 * Side effect: một mutation metadata; cây phiên bản và lịch sử được giữ nguyên.
 * Quy tắc/Contract: status transition phải do flow xác nhận nhạy cảm gọi riêng.
 */
export async function capNhatGiaoAnMau(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/workout-templates/${layId(id)}`, taoPayloadMetadata(value, { partial: true }))
  return response.data
}

/**
 * Mục đích: tạo phiên bản giáo án theo cơ chế copy-on-write.
 * Đầu vào: id, new_code, expected_content_version và cây days đầy đủ.
 * Xử lý: giữ null của description/notes, kiểm tra shape rồi POST một lần.
 * Kết quả: phiên bản mới hoặc WORKOUT_TEMPLATE_STALE khi version cũ.
 * Side effect: không tự reload, không tự POST lại và không thay bản nháp caller.
 * Quy tắc/Contract: expected_content_version là optimistic-concurrency token; Backend quyết định 409.
 */
export async function taoPhienBanGiaoAnMau(id, value = {}) {
  const body = {
    new_code: layChuoi(value.new_code, 'new_code', { required: true, max: 40 }),
    expected_content_version: laySo(value.expected_content_version, 'expected_content_version', { required: true, min: 1 }),
    name: layChuoi(value.name, 'name', { required: true, max: 150 }),
    goal: layChuoi(value.goal, 'goal', { required: true, max: 100 }),
    level: layChuoi(value.level, 'level', { required: true, max: 50 }),
    sessions_per_week: laySo(value.sessions_per_week, 'sessions_per_week', { required: true, min: 1, max: 7 }),
    days: taoNgay(value.days, 'days', { required: true }),
  }
  if (Object.prototype.hasOwnProperty.call(value, 'description')) {
    body.description = layChuoi(value.description, 'description', { nullable: true, max: 3000 })
  }
  for (const field of ['status']) {
    if (value[field] === undefined) continue
    if (field === 'status') body.status = layTrangThai(value[field])
  }
  if (body.sessions_per_week !== undefined && body.sessions_per_week !== body.days.length) throw taoLoiDauVao('Số buổi mỗi tuần phải bằng số ngày.', { sessions_per_week: ['Không khớp số ngày.'] })
  const response = await ketNoiApi.post(`/admin/workout-templates/${layId(id)}/revisions`, body)
  return response.data
}

/**
 * Mục đích: phân loại lỗi revision để caller giữ bản nháp trong optimistic concurrency.
 * Đầu vào: lỗi đã chuẩn hóa; xử lý: chỉ nhận đúng HTTP 409 với mã WORKOUT_TEMPLATE_STALE là conflict.
 * Kết quả: trả { laXungDot, maLoi, giuBanNhap } theo mã lỗi và trạng thái HTTP.
 * Side effect: không request, không mutate state và không retry; caller tự tải nền và đối soát expected_content_version.
 * Quy tắc/Contract: copy-on-write bảo toàn lịch sử và việc reconcile phiên bản phải rõ ràng trước lần POST sau.
 */
export function xuLyGiaoAnMauDaCu(error) {
  return {
    laXungDot: error?.httpStatus === 409 && error?.code === 'WORKOUT_TEMPLATE_STALE',
    maLoi: error?.code ?? null,
    giuBanNhap: error?.httpStatus === 409,
  }
}
