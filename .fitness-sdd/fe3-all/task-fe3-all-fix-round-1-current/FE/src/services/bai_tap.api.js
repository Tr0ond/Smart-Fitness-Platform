import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_BAI_TAP = Object.freeze(['HOAT_DONG', 'NGUNG_SU_DUNG'])
export const CAC_VAI_TRO_NHOM_CO = Object.freeze(['CHINH', 'PHU'])

function taoLoiDauVao(message, fieldErrors = {}) {
  return {
    httpStatus: 422,
    code: 'INVALID_EXERCISE_REQUEST',
    message,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

export function laIdBaiTapHopLe(value) {
  return (typeof value === 'number' && Number.isSafeInteger(value) && value > 0)
    || (typeof value === 'string' && /^[1-9]\d*$/.test(value.trim()) && Number.isSafeInteger(Number(value.trim())))
}

function layId(value, field = 'id') {
  if (!laIdBaiTapHopLe(value)) throw taoLoiDauVao('ID bài tập không hợp lệ.', { [field]: ['ID phải là số nguyên dương.'] })
  return Number(value)
}

function layChuoi(value, field, { required = false, max = 255 } = {}) {
  if (value === undefined && !required) return undefined
  if (typeof value !== 'string' || (required && value.trim() === '') || value.length > max) throw taoLoiDauVao('Dữ liệu bài tập không hợp lệ.', { [field]: ['Giá trị không hợp lệ.'] })
  return value.trim()
}

function layTrangThai(value, required = false) {
  if (value === undefined && !required) return undefined
  if (!CAC_TRANG_THAI_BAI_TAP.includes(value)) throw taoLoiDauVao('Trạng thái bài tập không hợp lệ.', { status: ['Trạng thái không hợp lệ.'] })
  return value
}

function taoDanhSachId(value, field) {
  if (!Array.isArray(value)) throw taoLoiDauVao('Quan hệ bài tập phải là mảng.', { [field]: ['Phải là mảng.'] })
  const seen = new Set()
  return value.map((item) => {
    const id = layId(item, field)
    if (seen.has(id)) throw taoLoiDauVao('Quan hệ bài tập bị trùng.', { [field]: ['Không được trùng ID.'] })
    seen.add(id)
    return id
  })
}

export function taoPayloadQuanHeBaiTap({ equipmentIds = [], muscleGroups = [] } = {}) {
  const equipment_ids = taoDanhSachId(equipmentIds, 'equipment_ids')
  if (!Array.isArray(muscleGroups)) throw taoLoiDauVao('Nhóm cơ bài tập phải là mảng.', { muscle_groups: ['Phải là mảng.'] })
  const seen = new Set()
  const muscle_groups = muscleGroups.map((item) => {
    const id = layId(item?.id, 'muscle_groups')
    if (!CAC_VAI_TRO_NHOM_CO.includes(item?.role)) throw taoLoiDauVao('Vai trò nhóm cơ không hợp lệ.', { muscle_groups: ['Vai trò phải là CHINH hoặc PHU.'] })
    if (seen.has(id)) throw taoLoiDauVao('Nhóm cơ bài tập bị trùng.', { muscle_groups: ['Không được trùng ID.'] })
    seen.add(id)
    return { id, role: item.role }
  })
  return { equipment_ids, muscle_groups }
}

function taoPayloadBaiTap(value = {}, { partial = false } = {}) {
  const body = {}
  for (const field of ['code', 'name', 'difficulty', 'instructions', 'image_path', 'video_path']) {
    const current = layChuoi(value[field], field, { required: !partial && ['code', 'name', 'difficulty'].includes(field), max: field === 'instructions' ? 5000 : 2000 })
    if (current !== undefined) body[field] = current
  }
  if (value.metadata !== undefined) {
    if (value.metadata === null || typeof value.metadata !== 'object' || Array.isArray(value.metadata)) throw taoLoiDauVao('Metadata bài tập không hợp lệ.', { metadata: ['Phải là object.'] })
    body.metadata = value.metadata
  }
  const status = layTrangThai(value.status, !partial)
  if (status !== undefined) body.status = status
  if (!partial || value.equipment_ids !== undefined || value.equipmentIds !== undefined || value.muscle_groups !== undefined || value.muscleGroups !== undefined) {
    const relations = taoPayloadQuanHeBaiTap({
      equipmentIds: value.equipment_ids ?? value.equipmentIds ?? [],
      muscleGroups: value.muscle_groups ?? value.muscleGroups ?? [],
    })
    Object.assign(body, relations)
  }
  return body
}

/** GET exercise list with the contract search/status allow-list. */
export async function taiDanhSachBaiTap(filters = {}) {
  const params = {}
  if (filters.search !== undefined && String(filters.search).trim() !== '') params.search = String(filters.search).trim()
  if (filters.status !== undefined && String(filters.status).trim() !== '') params.status = layTrangThai(String(filters.status).trim())
  const response = await ketNoiApi.get('/admin/exercises', { params })
  return response.data
}

/** GET exercise detail with equipment AND and muscle-group relations. */
export async function taiChiTietBaiTap(id) {
  const response = await ketNoiApi.get(`/admin/exercises/${layId(id)}`)
  return response.data
}

/** POST exercise and its complete relation arrays in one request. */
export async function taoBaiTap(value = {}) {
  const response = await ketNoiApi.post('/admin/exercises', taoPayloadBaiTap(value))
  return response.data
}

/** PATCH exercise metadata/status and optional relation replacement. */
export async function capNhatBaiTap(id, value = {}) {
  const response = await ketNoiApi.patch(`/admin/exercises/${layId(id)}`, taoPayloadBaiTap(value, { partial: true }))
  return response.data
}
