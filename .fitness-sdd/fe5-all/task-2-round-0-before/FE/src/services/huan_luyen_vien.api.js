import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN = Object.freeze([
  'HOAT_DONG',
  'NGUNG_NHAN_PHAN_CONG',
])

function taoLoiDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: 'INVALID_TRAINER_REQUEST',
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function laIdDuongAnToan(giaTri) {
  if (typeof giaTri === 'number') {
    return Number.isSafeInteger(giaTri) && giaTri > 0
  }

  return typeof giaTri === 'string'
    && /^[1-9]\d*$/.test(giaTri.trim())
    && Number.isSafeInteger(Number(giaTri.trim()))
}

function layIdAnToan(giaTri) {
  if (!laIdDuongAnToan(giaTri)) {
    throw taoLoiDauVao('Không thể truy cập hồ sơ PT này.', {}, 404)
  }

  return String(giaTri).trim()
}

function layKhoaAnToan(khoa) {
  if (typeof khoa !== 'string' || !/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(khoa.trim())) {
    throw taoLoiDauVao('Khóa thao tác không hợp lệ.', {}, 422)
  }

  return khoa.trim()
}

function layChuoiNullable(duLieu, truong) {
  if (!Object.prototype.hasOwnProperty.call(duLieu ?? {}, truong)) {
    return undefined
  }

  const giaTri = duLieu?.[truong]
  if (giaTri === null || giaTri === undefined) {
    return null
  }

  return typeof giaTri === 'string' ? giaTri.trim() : String(giaTri)
}

function layTrangThai(duLieu) {
  const trangThai = duLieu?.status
  if (trangThai === undefined) {
    return undefined
  }
  if (!CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN.includes(trangThai)) {
    throw taoLoiDauVao('Trạng thái hồ sơ PT không hợp lệ.', {
      status: ['Trạng thái hồ sơ PT không hợp lệ.'],
    })
  }

  return trangThai
}

function taoBodyHoSo(duLieu = {}, { baoGomIdentity = false } = {}) {
  const body = {}
  if (baoGomIdentity) {
    if (typeof duLieu.name !== 'string' || duLieu.name.trim() === '') {
      throw taoLoiDauVao('Họ tên là bắt buộc.', { name: ['Họ tên là bắt buộc.'] })
    }
    if (typeof duLieu.email !== 'string' || duLieu.email.trim() === '') {
      throw taoLoiDauVao('Email là bắt buộc.', { email: ['Email là bắt buộc.'] })
    }
    body.name = duLieu.name.trim()
    body.email = duLieu.email.trim()
    const phone = layChuoiNullable(duLieu, 'phone')
    if (phone !== undefined) body.phone = phone
  }

  for (const truong of ['introduction', 'specialties']) {
    const giaTri = layChuoiNullable(duLieu, truong)
    if (giaTri !== undefined) body[truong] = giaTri
  }
  const status = layTrangThai(duLieu)
  if (status !== undefined) body.status = status

  return body
}

/** POST onboarding account moi voi body/header allow-list va key on dinh. */
export async function taoHuanLuyenVien(duLieu = {}, khoa) {
  const key = layKhoaAnToan(khoa)
  const body = taoBodyHoSo(duLieu, { baoGomIdentity: true })
  const phanHoi = await ketNoiApi.post('/admin/trainers', body, {
    headers: { 'Idempotency-Key': key },
  })

  return phanHoi.data
}

/** POST onboarding/update profile account co san, khong gui identity hay role. */
export async function onboardTaiKhoanHuanLuyenVien(accountId, duLieu = {}, khoa) {
  const id = layIdAnToan(accountId)
  const key = layKhoaAnToan(khoa)
  const body = taoBodyHoSo(duLieu)
  const phanHoi = await ketNoiApi.post(`/admin/accounts/${id}/trainer-profile`, body, {
    headers: { 'Idempotency-Key': key },
  })

  return phanHoi.data
}

/** GET hồ sơ PT đầy đủ authoritative của Admin theo account ID. */
export async function taiHoSoHuanLuyenVien(accountId) {
  const id = layIdAnToan(accountId)
  const phanHoi = await ketNoiApi.get(`/admin/accounts/${id}/trainer-profile`)

  return phanHoi.data
}

/** Alias semantic cho edit profile, vẫn dùng đúng POST idempotent Backend. */
export const capNhatHoSoHuanLuyenVien = onboardTaiKhoanHuanLuyenVien

export { laIdDuongAnToan }
