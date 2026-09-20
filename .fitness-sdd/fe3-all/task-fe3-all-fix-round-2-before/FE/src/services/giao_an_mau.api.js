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

function layChuoi(value, field, { required = false, max = 255 } = {}) {
  if (value === undefined && !required) return undefined
  if (typeof value !== 'string' || (required && value.trim() === '') || value.length > max) throw taoLoiDauVao('Dữ liệu giáo án mẫu không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
  return value.trim()
}

function layTrangThai(value, required = false) {
  if (value === undefined && !required) return undefined
  if (!CAC_TRANG_THAI_GIAO_AN_MAU.includes(value)) throw taoLoiDauVao('Trạng thái giáo án mẫu không hợp lệ.', { status: ['Trạng thái không hợp lệ.'] })
  return value
}

function laySo(value, field, { required = false, min = 0 } = {}) {
  if (value === undefined && !required) return undefined
  const number = Number(value)
  if (!Number.isSafeInteger(number) || number < min) throw taoLoiDauVao('Dữ liệu giáo án mẫu không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
  return number
}

function taoNgay(value, field = 'days', { required = false } = {}) {
  if (value === undefined && !required) return undefined
  if (!Array.isArray(value) || value.length === 0) throw taoLoiDauVao('Giáo án mẫu phải có ngày tập.', { [field]: ['Phải có ít nhất một ngày.'] })
  const orders = new Set()
  return value.map((day, dayIndex) => {
    const order = laySo(day?.order, `${field}.${dayIndex}.order`, { required: true, min: 1 })
    if (orders.has(order)) throw taoLoiDauVao('Thứ tự ngày tập bị trùng.', { [field]: ['Thứ tự ngày phải duy nhất.'] })
    orders.add(order)
    const exercises = Array.isArray(day?.exercises) ? day.exercises : []
    const exerciseOrders = new Set()
    return {
      order,
      name: layChuoi(day?.name, `${field}.${dayIndex}.name`, { required: true, max: 120 }),
      estimated_minutes: laySo(day?.estimated_minutes, `${field}.${dayIndex}.estimated_minutes`, { required: true, min: 1 }),
      exercises: exercises.map((exercise, exerciseIndex) => {
        const exerciseOrder = laySo(exercise?.order, `${field}.${dayIndex}.exercises.${exerciseIndex}.order`, { required: true, min: 1 })
        if (exerciseOrders.has(exerciseOrder)) throw taoLoiDauVao('Thứ tự bài tập bị trùng.', { [field]: ['Thứ tự bài tập phải duy nhất trong ngày.'] })
        exerciseOrders.add(exerciseOrder)
        const minReps = laySo(exercise?.min_reps, 'min_reps', { required: true, min: 1 })
        const maxReps = laySo(exercise?.max_reps, 'max_reps', { required: true, min: minReps })
        return {
          exercise_id: layId(exercise?.exercise_id, 'exercise_id'),
          order: exerciseOrder,
          target_sets: laySo(exercise?.target_sets, 'target_sets', { required: true, min: 1 }),
          min_reps: minReps,
          max_reps: maxReps,
          rest_seconds: laySo(exercise?.rest_seconds, 'rest_seconds', { required: true, min: 0 }),
          ...(exercise?.notes === undefined ? {} : { notes: layChuoi(exercise.notes, 'notes', { max: 1000 }) }),
        }
      }),
    }
  })
}

function taoPayloadMetadata(value = {}, { partial = false, includeDays = false } = {}) {
  const body = {}
  for (const field of ['code', 'name', 'goal', 'level', 'description']) {
    const current = layChuoi(value[field], field, { required: !partial && ['code', 'name', 'goal', 'level'].includes(field), max: field === 'description' ? 3000 : 160 })
    if (current !== undefined) body[field] = current
  }
  const sessions = laySo(value.sessions_per_week, 'sessions_per_week', { required: !partial, min: 1 })
  if (sessions !== undefined) body.sessions_per_week = sessions
  const status = layTrangThai(value.status, !partial)
  if (status !== undefined) body.status = status
  if (includeDays || !partial) {
    body.days = taoNgay(value.days, 'days', { required: true })
    if (body.sessions_per_week !== body.days.length) throw taoLoiDauVao('Số buổi mỗi tuần phải bằng số ngày.', { sessions_per_week: ['Không khớp số ngày.'] })
  }
  return body
}

/** GET template summaries; list content stays server-authoritative. */
export async function taiDanhSachGiaoAnMau() {
  const response = await ketNoiApi.get('/admin/workout-templates')
  return response.data
}

export async function taiChiTietGiaoAnMau(id) {
  const response = await ketNoiApi.get(`/admin/workout-templates/${layId(id)}`)
  return response.data
}

/**
 * Tai nen authoritative cua mot giao an truoc khi mo trinh soan phien ban.
 * Dau vao la template ID; ket qua la detail envelope tu Backend, khong tu tao day/exercise.
 * Side effect chi la mot GET read-only.
 */
export async function taiNenGiaoAnMau(id) {
  return taiChiTietGiaoAnMau(id)
}

/** POST a complete template tree after client shape validation. */
export async function taoGiaoAnMau(value = {}) {
  const response = await ketNoiApi.post('/admin/workout-templates', taoPayloadMetadata(value, { includeDays: true }))
  return response.data
}

/** PATCH metadata/status only; the request intentionally excludes days. */
export async function capNhatGiaoAnMau(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/workout-templates/${layId(id)}`, taoPayloadMetadata(value, { partial: true }))
  return response.data
}

/** POST copy-on-write revision with a stable expected_content_version. */
export async function taoPhienBanGiaoAnMau(id, value = {}) {
  const body = {
    new_code: layChuoi(value.new_code, 'new_code', { required: true, max: 160 }),
    expected_content_version: laySo(value.expected_content_version, 'expected_content_version', { required: true, min: 1 }),
    days: taoNgay(value.days, 'days', { required: true }),
  }
  for (const field of ['name', 'goal', 'level', 'description', 'status', 'sessions_per_week']) {
    if (value[field] === undefined) continue
    if (field === 'status') body.status = layTrangThai(value[field])
    else if (field === 'sessions_per_week') body.sessions_per_week = laySo(value[field], field, { required: true, min: 1 })
    else body[field] = layChuoi(value[field], field, { max: field === 'description' ? 3000 : 160 })
  }
  if (body.sessions_per_week !== undefined && body.sessions_per_week !== body.days.length) throw taoLoiDauVao('Số buổi mỗi tuần phải bằng số ngày.', { sessions_per_week: ['Không khớp số ngày.'] })
  const response = await ketNoiApi.post(`/admin/workout-templates/${layId(id)}/revisions`, body)
  return response.data
}

/**
 * Nhan dien conflict optimistic-concurrency cua revision va bao page giu ban nhap.
 * Ham khong retry va khong sua content_version; caller hien dialog de nguoi dung doi soat.
 */
export function xuLyGiaoAnMauDaCu(error) {
  return {
    laXungDot: error?.httpStatus === 409 && error?.code === 'WORKOUT_TEMPLATE_STALE',
    maLoi: error?.code ?? null,
    giuBanNhap: error?.httpStatus === 409,
  }
}
