const THONG_BAO_MAC_DINH = Object.freeze({
  401: 'Thông tin xác thực không hợp lệ.',
  403: 'Bạn không có quyền thực hiện thao tác này.',
  404: 'Không tìm thấy tài nguyên yêu cầu.',
  409: 'Yêu cầu đang xung đột với trạng thái hiện tại.',
  422: 'Dữ liệu gửi lên chưa hợp lệ.',
  429: 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
  '5xx': 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.',
  network: 'Không thể kết nối đến máy chủ.',
  unknown: 'Không thể xử lý yêu cầu.',
})

const NOI_DUNG_KY_THUAT = /sqlstate|stack trace|traceback|exception|query|select\s+.+\s+from|authorization|bearer|password|token|vendor[\\/]|app[\\/]/i

function laDoiTuong(giaTri) {
  return giaTri !== null && typeof giaTri === 'object'
}

function layHeader(headers, tenHeader) {
  if (!laDoiTuong(headers)) {
    return null
  }

  if (typeof headers.get === 'function') {
    try {
      const giaTri = headers.get(tenHeader)

      if (giaTri !== undefined && giaTri !== null) {
        return String(giaTri)
      }
    } catch {
      // Fallback sang object headers neu AxiosHeaders khong doc duoc gia tri.
    }
  }

  const tenHeaderCanTim = tenHeader.toLowerCase()
  const capHeader = Object.entries(headers).find(([ten]) => ten.toLowerCase() === tenHeaderCanTim)

  return capHeader ? String(capHeader[1]) : null
}

function layMaLoi(payload) {
  if (typeof payload?.code !== 'string' || payload.code.trim() === '') {
    return null
  }

  return payload.code
}

function laThongBaoKhongAnToan(thongBao) {
  return NOI_DUNG_KY_THUAT.test(thongBao)
}

function layThongBaoAnToan(payload, httpStatus, isNetworkError, code) {
  if (isNetworkError) {
    return THONG_BAO_MAC_DINH.network
  }

  const thongBaoBackend = typeof payload?.message === 'string' ? payload.message.trim() : ''
  const thongBaoKhongAnToan = thongBaoBackend === '' || laThongBaoKhongAnToan(thongBaoBackend)

  if (httpStatus >= 500) {
    return code !== null && !thongBaoKhongAnToan
      ? thongBaoBackend
      : THONG_BAO_MAC_DINH['5xx']
  }

  if ((httpStatus === 404 || httpStatus === 409 || httpStatus === 429) && code === null) {
    return THONG_BAO_MAC_DINH[httpStatus]
  }

  return thongBaoKhongAnToan ? (THONG_BAO_MAC_DINH[httpStatus] ?? THONG_BAO_MAC_DINH.unknown) : thongBaoBackend
}

function chuanHoaLoiTruong(errors) {
  if (!laDoiTuong(errors)) {
    return {}
  }

  return Object.fromEntries(
    Object.entries(errors).flatMap(([truong, thongBao]) => {
      const danhSachThongBao = Array.isArray(thongBao)
        ? thongBao.filter((giaTri) => typeof giaTri === 'string')
        : typeof thongBao === 'string'
          ? [thongBao]
          : []

      const danhSachDaLoc = danhSachThongBao.map((giaTri) => giaTri.trim()).filter(Boolean)

      return danhSachDaLoc.length > 0 ? [[truong, danhSachDaLoc]] : []
    }),
  )
}

function layRetryAfter(headers) {
  const giaTri = layHeader(headers, 'Retry-After')

  if (giaTri === null) {
    return null
  }

  const giaTriDaChuanHoa = giaTri.trim()

  if (/^\d+$/.test(giaTriDaChuanHoa)) {
    return Number(giaTriDaChuanHoa)
  }

  const mocThuLai = Date.parse(giaTriDaChuanHoa)

  return Number.isNaN(mocThuLai)
    ? null
    : Math.max(0, Math.ceil((mocThuLai - Date.now()) / 1000))
}

function layOriginalRequestId(headers) {
  return layHeader(headers, 'X-Request-Id') ?? layHeader(headers, 'X-Correlation-Id')
}

/**
 * Chuan hoa loi Axios va loi response tu Backend thanh contract an toan, on dinh.
 *
 * Dau vao: error co the la Axios error, response loi co data/message/code/errors,
 * hoac object malformed khong co response.
 * Cach hoat dong: phan biet response HTTP voi loi khong nhan duoc response; giu nguyen
 * Backend code, doc Retry-After/request id tu response headers va chi map errors cua 422.
 * Ket qua: tra ve httpStatus, code, message, fieldErrors, retryAfter, isNetworkError
 * va originalRequestId; object khong chua config/request/token/technical trace.
 * Side effect: khong retry, khong navigate, khong logout va khong ghi log du lieu loi.
 * Business Rule: khong invent business error code va khong expose raw technical error.
 */
export function chuanHoaLoiApi(error) {
  const response = laDoiTuong(error?.response) ? error.response : null
  const headers = response?.headers
  const payload = laDoiTuong(response?.data) ? response.data : {}
  const httpStatus = Number.isInteger(response?.status) ? response.status : null
  const isNetworkError = response === null
  const code = layMaLoi(payload)

  return {
    httpStatus,
    code,
    message: layThongBaoAnToan(payload, httpStatus, isNetworkError, code),
    fieldErrors: httpStatus === 422 ? chuanHoaLoiTruong(payload.errors) : {},
    retryAfter: layRetryAfter(headers),
    isNetworkError,
    originalRequestId: layOriginalRequestId(headers),
  }
}
