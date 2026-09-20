import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_THANH_TOAN = Object.freeze([
  'DANG_TAO',
  'CHO_THANH_TOAN',
  'THANH_CONG',
  'THAT_BAI',
  'HUY',
  'HET_HAN',
  'CAN_DOI_SOAT',
])

export const CAC_TRANG_THAI_DON_MUA = Object.freeze([
  'CHO_THANH_TOAN',
  'DA_THANH_TOAN',
  'HET_HAN',
  'HUY',
  'CAN_DOI_SOAT',
])

export const CAC_TRANG_THAI_SU_KIEN = Object.freeze([
  'CHO_XU_LY',
  'DA_XU_LY',
  'BI_TU_CHOI',
  'CAN_DOI_SOAT',
  'CHO_THU_LAI',
])

export const CAC_COT_SAP_XEP_THANH_TOAN = Object.freeze([
  'created_at',
  'confirmed_at',
  'expected_amount',
  'received_amount',
])

export const CAC_COT_SAP_XEP_SU_KIEN = Object.freeze([
  'received_at',
  'processed_at',
  'created_at',
])

function taoLoiDauVao(message, fieldErrors = {}, code = 'INVALID_PAYMENT_FILTER', httpStatus = 422) {
  return {
    httpStatus,
    code,
    message,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function layChuoi(boLoc, truong) {
  const giaTri = boLoc?.[truong]

  if (giaTri === undefined || giaTri === null) {
    return ''
  }

  return typeof giaTri === 'string' ? giaTri.trim() : String(giaTri)
}

function laySoNguyen(boLoc, truong, gioiHan = {}) {
  const giaTri = boLoc?.[truong]

  if (giaTri === undefined || giaTri === null || giaTri === '') {
    return null
  }

  const so = typeof giaTri === 'number' ? giaTri : Number(giaTri)
  const toiThieu = gioiHan.min ?? 1
  const toiDa = gioiHan.max ?? Number.MAX_SAFE_INTEGER

  if (!Number.isSafeInteger(so) || so < toiThieu || so > toiDa) {
    throw taoLoiDauVao(
      `${truong} không hợp lệ.`,
      { [truong]: [`${truong} phải là số nguyên trong khoảng cho phép.`] },
    )
  }

  return so
}

function laNgayHopLe(giaTri) {
  if (typeof giaTri !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(giaTri)) {
    return false
  }

  const [nam, thang, ngay] = giaTri.split('-').map(Number)
  const mocUtc = Date.UTC(nam, thang - 1, ngay)
  return new Date(mocUtc).toISOString().slice(0, 10) === giaTri
}

function themChuoi(params, boLoc, truong, gioiHan = {}) {
  const giaTri = layChuoi(boLoc, truong)

  if (giaTri.length > (gioiHan.max ?? Number.MAX_SAFE_INTEGER)) {
    throw taoLoiDauVao(
      `${truong} không hợp lệ.`,
      { [truong]: [`${truong} không được dài quá ${gioiHan.max} ký tự.`] },
    )
  }

  if (giaTri !== '') {
    params[truong] = giaTri
  }

  return giaTri
}

function themEnum(params, boLoc, truong, danhSachChoPhep) {
  const giaTri = layChuoi(boLoc, truong)

  if (giaTri !== '' && !danhSachChoPhep.includes(giaTri)) {
    throw taoLoiDauVao(
      `${truong} không hợp lệ.`,
      { [truong]: [`${truong} không thuộc danh sách được phép.`] },
    )
  }

  if (giaTri !== '') {
    params[truong] = giaTri
  }
}

function themNgay(params, boLoc, truong) {
  const giaTri = layChuoi(boLoc, truong)

  if (giaTri !== '' && !laNgayHopLe(giaTri)) {
    throw taoLoiDauVao(
      `${truong} không hợp lệ.`,
      { [truong]: ['Ngày phải có định dạng YYYY-MM-DD.'] },
    )
  }

  if (giaTri !== '') {
    params[truong] = giaTri
  }

  return giaTri
}

function themBoolean(params, boLoc, truong) {
  const giaTri = boLoc?.[truong]

  if (giaTri === undefined || giaTri === null || giaTri === '') {
    return null
  }

  const hopLe = giaTri === true || giaTri === false || giaTri === 1 || giaTri === 0
    || giaTri === '1' || giaTri === '0' || giaTri === 'true' || giaTri === 'false'

  if (!hopLe) {
    throw taoLoiDauVao(
      `${truong} không hợp lệ.`,
      { [truong]: ['Giá trị phải là true, false, 1 hoặc 0.'] },
    )
  }

  params[truong] = giaTri
  return giaTri
}

function kiemTraKhoangNgay(boLoc, params) {
  const tuNgay = params.from
  const denNgay = params.to

  if (tuNgay && denNgay && denNgay < tuNgay) {
    throw taoLoiDauVao(
      'Khoảng ngày không hợp lệ.',
      { to: ['Đến ngày phải từ Từ ngày trở đi.'] },
    )
  }

  // Đọc boLoc ở đây để giữ helper có cùng boundary với các field khác;
  // không thêm timezone hoặc khoảng ngày mặc định ở phía client.
  return boLoc
}

function taoThamSoThanhToan(boLoc = {}, { batBuocDoiSoat = false } = {}) {
  const params = {}

  themChuoi(params, boLoc, 'order_code', { max: 40 })
  themChuoi(params, boLoc, 'member', { max: 254 })
  themEnum(params, boLoc, 'payment_status', CAC_TRANG_THAI_THANH_TOAN)
  themEnum(params, boLoc, 'order_status', CAC_TRANG_THAI_DON_MUA)
  const maDonCongThanhToan = laySoNguyen(boLoc, 'provider_order_code', { max: Number.MAX_SAFE_INTEGER })
  const trang = laySoNguyen(boLoc, 'page')
  const soDong = laySoNguyen(boLoc, 'per_page', { max: 100 })
  if (maDonCongThanhToan !== null) params.provider_order_code = maDonCongThanhToan
  themChuoi(params, boLoc, 'provider_reference', { max: 150 })
  themBoolean(params, boLoc, 'reconciliation_required')
  themNgay(params, boLoc, 'from')
  themNgay(params, boLoc, 'to')
  themEnum(params, boLoc, 'sort_by', CAC_COT_SAP_XEP_THANH_TOAN)
  themEnum(params, boLoc, 'sort_direction', ['asc', 'desc'])
  if (trang !== null) params.page = trang
  if (soDong !== null) params.per_page = soDong

  kiemTraKhoangNgay(boLoc, params)

  if (batBuocDoiSoat) {
    params.reconciliation_required = 1
  }

  return params
}

function taoThamSoSuKien(boLoc = {}, { batBuocDoiSoat = false } = {}) {
  const params = {}

  themChuoi(params, boLoc, 'order_code', { max: 40 })
  themChuoi(params, boLoc, 'member', { max: 254 })
  themEnum(params, boLoc, 'processing_status', CAC_TRANG_THAI_SU_KIEN)
  const maDonCongThanhToan = laySoNguyen(boLoc, 'provider_order_code', { max: Number.MAX_SAFE_INTEGER })
  const trang = laySoNguyen(boLoc, 'page')
  const soDong = laySoNguyen(boLoc, 'per_page', { max: 100 })
  if (maDonCongThanhToan !== null) params.provider_order_code = maDonCongThanhToan
  themChuoi(params, boLoc, 'provider_reference', { max: 150 })
  themBoolean(params, boLoc, 'reconciliation_required')
  themNgay(params, boLoc, 'from')
  themNgay(params, boLoc, 'to')
  themEnum(params, boLoc, 'sort_by', CAC_COT_SAP_XEP_SU_KIEN)
  themEnum(params, boLoc, 'sort_direction', ['asc', 'desc'])
  if (trang !== null) params.page = trang
  if (soDong !== null) params.per_page = soDong

  kiemTraKhoangNgay(boLoc, params)

  if (batBuocDoiSoat) {
    params.reconciliation_required = 1
  }

  return params
}

/**
 * Kiểm tra ID lần thanh toán trước khi ghép vào URL chi tiết.
 * Input: id từ route hoặc lời gọi Store.
 * Process: chỉ chấp nhận số nguyên dương an toàn, không nhận decimal/NaN/object.
 * Output: boolean dùng cho boundary service; side effect: không gọi mạng.
 */
export function laIdThanhToanHopLe(id) {
  if (typeof id === 'number') {
    return Number.isSafeInteger(id) && id > 0
  }

  return typeof id === 'string'
    && /^[1-9]\d*$/.test(id.trim())
    && Number.isSafeInteger(Number(id.trim()))
}

function layIdAnToan(id) {
  if (!laIdThanhToanHopLe(id)) {
    throw taoLoiDauVao(
      'Không thể truy cập dữ liệu thanh toán này.',
      {},
      'PAYMENT_ID_INVALID',
      404,
    )
  }

  return String(id).trim()
}

/**
 * Tải danh sách lần thanh toán theo allow-list GET của Backend.
 * Input: bộ lọc Payment gồm trạng thái, mã tham chiếu, khoảng ngày và phân trang.
 * Process: kiểm tra enum/range, loại field ngoài contract rồi phát đúng một GET.
 * Output: envelope `{ data: { items, pagination } }` do Backend cung cấp.
 * Side effect: chỉ đọc dữ liệu; không gửi body hoặc thay đổi dữ liệu.
 */
export async function taiDanhSachThanhToan(boLoc = {}) {
  const params = taoThamSoThanhToan(boLoc)
  const phanHoi = await ketNoiApi.get('/admin/payments', { params })
  return phanHoi.data
}

/**
 * Tải một lần thanh toán cùng Safe DTO order/member/term/events.
 * Input: ID dương đã qua kiểm tra ownership boundary ở service.
 * Process: ghép ID an toàn vào route GET detail, để Backend quyết định scope/404.
 * Output: envelope `{ data: payment }`; không suy diễn Membership từ trạng thái Payment.
 * Side effect: chỉ đọc; không ghi lịch sử, không retry tự động.
 */
export async function taiChiTietThanhToan(id) {
  const idAnToan = layIdAnToan(id)
  const phanHoi = await ketNoiApi.get(`/admin/payments/${idAnToan}`)
  return phanHoi.data
}

/**
 * Tải hàng đợi Payment cần đối soát bằng cờ Backend chính thức.
 * Input: filter Payment hợp lệ; cờ `reconciliation_required` luôn do boundary đặt bằng 1.
 * Process: dùng cùng endpoint Payment list với allow-list, không ghép event ở client.
 * Output: envelope phân trang của hàng đợi Payment authoritative.
 * Side effect: GET read-only duy nhất; không cấp quyền, refund hoặc sửa Payment.
 */
export async function taiDanhSachCanDoiSoat(boLoc = {}) {
  const params = taoThamSoThanhToan(boLoc, { batBuocDoiSoat: true })
  const phanHoi = await ketNoiApi.get('/admin/payments', { params })
  return phanHoi.data
}

/**
 * Tải hàng đợi sự kiện thanh toán cần đối soát độc lập với Payment queue.
 * Input: filter event đúng Request Backend, bao gồm processing_status và reference.
 * Process: gửi GET `/admin/payment-events` với cờ reconciliation bắt buộc; giữ nguyên
 * event chưa liên kết để UI hiển thị payment_id null, không client join bù visibility.
 * Output: envelope event DTO phân trang, chỉ gồm field an toàn Backend trả về.
 * Side effect: chỉ đọc; không nhận lại webhook và không thay đổi event history.
 */
export async function taiSuKienThanhToan(boLoc = {}) {
  const params = taoThamSoSuKien(boLoc, { batBuocDoiSoat: true })
  const phanHoi = await ketNoiApi.get('/admin/payment-events', { params })
  return phanHoi.data
}
