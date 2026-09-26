import ketNoiApi from './api.js'
import { laIdHoiVienHopLe } from './hoi_vien_pt.api.js'

const MAU_UUID_V4 = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
const CAC_LOAI_THAY_DOI = new Set(['TAO_MOI', 'DIEU_CHINH', 'THAY_BAI'])

function taoLoiDauVao(thongBao, fieldErrors = {}) {
  return {
    httpStatus: 422,
    code: 'INVALID_PT_PROPOSAL_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function loiTruong(truong, thongBao) {
  throw taoLoiDauVao(thongBao, { [truong]: [thongBao] })
}

function layIdAnToan(giaTri) {
  if (!laIdHoiVienHopLe(giaTri)) {
    throw taoLoiDauVao('Không thể truy cập đề xuất của học viên này.', {
      member: ['ID học viên phải là số nguyên dương.'],
    })
  }

  return String(giaTri).trim()
}

function laySoNguyen(giaTri, truong, toiThieu, toiDa) {
  const so = Number(giaTri)
  if (!Number.isSafeInteger(so) || so < toiThieu || so > toiDa) {
    loiTruong(truong, 'Giá trị phải là số nguyên trong phạm vi cho phép.')
  }
  return so
}

function layChuoi(giaTri, truong, toiDa) {
  if (typeof giaTri !== 'string' || giaTri.trim() === '' || giaTri.length > toiDa) {
    loiTruong(truong, `Trường này bắt buộc và không được dài quá ${toiDa} ký tự.`)
  }
  return giaTri.trim()
}

function taoExercise(exercise = {}, prefix) {
  const ketQua = {
    exercise_id: laySoNguyen(exercise.exercise_id, `${prefix}.exercise_id`, 1, Number.MAX_SAFE_INTEGER),
    order: laySoNguyen(exercise.order, `${prefix}.order`, 1, 50),
    target_sets: laySoNguyen(exercise.target_sets, `${prefix}.target_sets`, 1, 100),
    min_reps: laySoNguyen(exercise.min_reps, `${prefix}.min_reps`, 1, 1000),
    max_reps: laySoNguyen(exercise.max_reps, `${prefix}.max_reps`, 1, 1000),
    rest_seconds: laySoNguyen(exercise.rest_seconds, `${prefix}.rest_seconds`, 0, 86400),
  }

  if (ketQua.max_reps < ketQua.min_reps) {
    loiTruong(`${prefix}.max_reps`, 'Số lần lặp tối đa phải lớn hơn hoặc bằng số lần lặp tối thiểu.')
  }

  if (exercise.target_weight_kg !== undefined) {
    if (exercise.target_weight_kg !== null
      && (typeof exercise.target_weight_kg !== 'number'
        || !Number.isFinite(exercise.target_weight_kg)
        || exercise.target_weight_kg < 0
        || exercise.target_weight_kg > 9999.99)) {
      loiTruong(`${prefix}.target_weight_kg`, 'Khối lượng phải nằm trong khoảng 0 đến 9999.99 kg.')
    }
    ketQua.target_weight_kg = exercise.target_weight_kg
  }

  if (exercise.notes !== undefined) {
    if (exercise.notes !== null && (typeof exercise.notes !== 'string' || exercise.notes.length > 1000)) {
      loiTruong(`${prefix}.notes`, 'Ghi chú không được dài quá 1000 ký tự.')
    }
    ketQua.notes = exercise.notes
  }

  return ketQua
}

function taoNgayTap(day = {}, index) {
  const prefix = `plan.days.${index}`
  if (!Array.isArray(day.exercises) || day.exercises.length < 1 || day.exercises.length > 50) {
    loiTruong(`${prefix}.exercises`, 'Mỗi ngày cần có từ 1 đến 50 bài tập.')
  }

  return {
    order: laySoNguyen(day.order, `${prefix}.order`, 1, 7),
    weekday: laySoNguyen(day.weekday, `${prefix}.weekday`, 2, 8),
    name: layChuoi(day.name, `${prefix}.name`, 150),
    estimated_minutes: laySoNguyen(day.estimated_minutes, `${prefix}.estimated_minutes`, 1, 1440),
    exercises: day.exercises.map((exercise, exerciseIndex) => taoExercise(
      exercise,
      `${prefix}.exercises.${exerciseIndex}`,
    )),
  }
}

/**
 * Dung allow-list payload proposal tu cac truong FE duoc phep gui.
 * Input: draft UI co the chua field thua; plan/day/exercise duoc validate theo contract.
 * Process: tao object moi, bo moi truong server-owned va kiem tra gioi han co ban.
 * Output: chi change_type/title/explanation/effective_from/plan.
 * Side effect: khong thay doi draft, khong goi API va khong chon proposal source.
 */
export function taoBodyDeXuat(duLieu = {}) {
  if (!CAC_LOAI_THAY_DOI.has(duLieu.change_type)) {
    loiTruong('change_type', 'Loại thay đổi không hợp lệ.')
  }

  if (typeof duLieu.effective_from !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(duLieu.effective_from)) {
    loiTruong('effective_from', 'Ngày áp dụng phải theo định dạng YYYY-MM-DD.')
  }

  if (duLieu.plan === null || typeof duLieu.plan !== 'object' || Array.isArray(duLieu.plan)) {
    loiTruong('plan', 'Nội dung kế hoạch không hợp lệ.')
  }
  if (!Array.isArray(duLieu.plan.days) || duLieu.plan.days.length < 1 || duLieu.plan.days.length > 7) {
    loiTruong('plan.days', 'Kế hoạch cần có từ 1 đến 7 ngày tập.')
  }

  const plan = {
    name: layChuoi(duLieu.plan.name, 'plan.name', 150),
    goal: layChuoi(duLieu.plan.goal, 'plan.goal', 100),
    days: duLieu.plan.days.map(taoNgayTap),
  }

  if (duLieu.plan.template_id !== undefined) {
    plan.template_id = duLieu.plan.template_id === null
      ? null
      : laySoNguyen(duLieu.plan.template_id, 'plan.template_id', 1, Number.MAX_SAFE_INTEGER)
  }

  return {
    change_type: duLieu.change_type,
    title: layChuoi(duLieu.title, 'title', 200),
    explanation: layChuoi(duLieu.explanation, 'explanation', 10000),
    effective_from: duLieu.effective_from,
    plan,
  }
}

function taoHeaderIdempotency(khoa) {
  if (typeof khoa !== 'string' || !MAU_UUID_V4.test(khoa)) {
    throw taoLoiDauVao('Khóa đề xuất phải là UUIDv4.', {
      idempotency_key: ['Khóa thao tác không hợp lệ.'],
    })
  }

  return { 'Idempotency-Key': khoa }
}

/** Tai toi da 100 proposals cua assignment PT hien tai; Backend la thẩm quyền scope. */
export async function taiDanhSachDeXuat(memberId) {
  const id = layIdAnToan(memberId)
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}/proposals`)

  return phanHoi.data
}

/** Gui proposal PT cho Member xac nhan; khong co endpoint apply tu giao dien PT. */
export async function taoDeXuatKeHoach(memberId, duLieu, idempotencyKey) {
  const id = layIdAnToan(memberId)
  const body = taoBodyDeXuat(duLieu)
  const phanHoi = await ketNoiApi.post(`/pt/members/${id}/proposals`, body, {
    headers: taoHeaderIdempotency(idempotencyKey),
  })

  return phanHoi.data
}
