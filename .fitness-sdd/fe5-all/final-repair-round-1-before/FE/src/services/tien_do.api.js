import ketNoiApi from './api.js'
import { laIdHoiVienHopLe } from './hoi_vien_pt.api.js'

function taoLoiDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: 'INVALID_PT_PROGRESS_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function layIdAnToan(giaTri, truong) {
  if (!laIdHoiVienHopLe(giaTri)) {
    throw taoLoiDauVao('ID phạm vi PT không hợp lệ.', {
      [truong]: ['ID phải là số nguyên dương.'],
    }, 404)
  }

  return String(giaTri).trim()
}

function layNgay(giaTri, truong) {
  if (giaTri === undefined || giaTri === null || giaTri === '') {
    return null
  }

  if (typeof giaTri !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(giaTri.trim())) {
    throw taoLoiDauVao('Khoảng thời gian không hợp lệ.', {
      [truong]: ['Ngày phải theo định dạng YYYY-MM-DD.'],
    })
  }

  return giaTri.trim()
}

function taoQueryKhoang(boLoc = {}) {
  const params = {}
  const from = layNgay(boLoc.from, 'from')
  const to = layNgay(boLoc.to, 'to')

  if (from !== null) params.from = from
  if (to !== null) params.to = to
  return params
}

function laySoNguyenDuong(giaTri, truong, macDinh = null) {
  if (giaTri === undefined || giaTri === null || giaTri === '') {
    return macDinh
  }

  const so = Number(giaTri)
  if (!Number.isSafeInteger(so) || so < 1) {
    throw taoLoiDauVao('Tham số phân trang không hợp lệ.', {
      [truong]: ['Giá trị phải là số nguyên dương.'],
    })
  }

  return so
}

function taoQueryChiSo(boLoc = {}) {
  const params = {}
  const limit = laySoNguyenDuong(boLoc.limit, 'limit')
  const beforeId = laySoNguyenDuong(boLoc.before_id, 'before_id')
  const beforeMeasuredAt = boLoc.before_measured_at

  if (limit !== null) {
    if (limit > 100) {
      throw taoLoiDauVao('Giới hạn chỉ số phải từ 1 đến 100.', { limit: ['Giá trị tối đa là 100.'] })
    }
    params.limit = limit
  }
  if (beforeId !== null) params.before_id = beforeId
  if (beforeMeasuredAt !== undefined && beforeMeasuredAt !== null && beforeMeasuredAt !== '') {
    if (typeof beforeMeasuredAt !== 'string' || Number.isNaN(Date.parse(beforeMeasuredAt))) {
      throw taoLoiDauVao('Mốc chỉ số trước đó không hợp lệ.', {
        before_measured_at: ['Mốc thời gian không hợp lệ.'],
      })
    }
    params.before_measured_at = beforeMeasuredAt
  }

  return params
}

/** Tai tong quan progress PT-scoped cua Member. */
export async function taiTienDoHoiVien(memberId, boLoc = {}) {
  const id = layIdAnToan(memberId, 'member')
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}/progress/overview`, {
    params: taoQueryKhoang(boLoc),
  })

  return phanHoi.data
}

/** Tai lich su chi so co the read-only cua Member trong assignment hien tai. */
export async function taiChiSoCoThe(memberId, boLoc = {}) {
  const id = layIdAnToan(memberId, 'member')
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}/progress/body`, {
    params: taoQueryChiSo(boLoc),
  })

  return phanHoi.data
}

/** Tai tien do bai tap theo exercise ID do Plan chinh thuc cung cap. */
export async function taiTienDoBaiTap(memberId, exerciseId, boLoc = {}) {
  const id = layIdAnToan(memberId, 'member')
  const baiTapId = laySoNguyenDuong(exerciseId, 'exercise')
  const limit = laySoNguyenDuong(boLoc.limit, 'limit')
  if (limit !== null && limit > 100) {
    throw taoLoiDauVao('Giới hạn tiến độ bài tập phải từ 1 đến 100.', {
      limit: ['Giá trị tối đa là 100.'],
    })
  }
  const phanHoi = await ketNoiApi.get(`/pt/members/${id}/progress/exercises/${baiTapId}`, {
    params: { ...taoQueryKhoang(boLoc), ...(limit === null ? {} : { limit }) },
  })

  return phanHoi.data
}
