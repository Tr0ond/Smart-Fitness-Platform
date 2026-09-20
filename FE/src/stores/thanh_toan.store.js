import { defineStore, getActivePinia } from 'pinia'
import {
  laIdThanhToanHopLe,
  taiChiTietThanhToan as taiChiTietThanhToanApi,
  taiDanhSachCanDoiSoat as taiDanhSachCanDoiSoatApi,
  taiDanhSachThanhToan as taiDanhSachThanhToanApi,
  taiSuKienThanhToan as taiSuKienThanhToanApi,
} from '../services/thanh_toan.api.js'

const BO_LOC_THANH_TOAN_MAC_DINH = Object.freeze({
  order_code: '',
  member: '',
  payment_status: '',
  order_status: '',
  provider_order_code: '',
  provider_reference: '',
  reconciliation_required: '',
  from: '',
  to: '',
  sort_by: '',
  sort_direction: '',
  per_page: 20,
})

const BO_LOC_CAN_DOI_SOAT_MAC_DINH = Object.freeze({
  order_code: '',
  member: '',
  payment_status: '',
  order_status: '',
  provider_order_code: '',
  provider_reference: '',
  from: '',
  to: '',
  sort_by: '',
  sort_direction: '',
  per_page: 20,
})

const BO_LOC_SU_KIEN_MAC_DINH = Object.freeze({
  order_code: '',
  member: '',
  processing_status: '',
  provider_order_code: '',
  provider_reference: '',
  from: '',
  to: '',
  sort_by: '',
  sort_direction: '',
  per_page: 20,
})

const PHAN_TRANG_MAC_DINH = Object.freeze({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
})

function taoBoLoc(boLoc, macDinh) {
  const loc = boLoc !== null && typeof boLoc === 'object' ? boLoc : {}
  const ketQua = {}

  for (const truong of Object.keys(macDinh)) {
    if (truong === 'per_page') {
      ketQua[truong] = Number.isInteger(loc[truong]) ? loc[truong] : macDinh[truong]
    } else {
      ketQua[truong] = typeof loc[truong] === 'string' ? loc[truong].trim() : loc[truong] ?? ''
    }
  }

  return ketQua
}

function taoLoiAnToan(error, thongBaoMacDinh) {
  const fieldErrors = Object.fromEntries(
    Object.entries(error?.fieldErrors ?? {}).flatMap(([truong, thongBao]) => {
      const danhSach = Array.isArray(thongBao)
        ? thongBao.filter((giaTri) => typeof giaTri === 'string' && giaTri.trim() !== '')
        : []

      return danhSach.length > 0 ? [[truong, danhSach.map((giaTri) => giaTri.trim())]] : []
    }),
  )

  return Object.freeze({
    httpStatus: Number.isInteger(error?.httpStatus) ? error.httpStatus : null,
    code: typeof error?.code === 'string' ? error.code : null,
    message: typeof error?.message === 'string' && error.message.trim() !== ''
      ? error.message
      : thongBaoMacDinh,
    fieldErrors,
    retryAfter: Number.isInteger(error?.retryAfter) ? error.retryAfter : null,
    isNetworkError: error?.isNetworkError === true,
    originalRequestId: typeof error?.originalRequestId === 'string'
      ? error.originalRequestId
      : null,
  })
}

function taoLoiPhanHoiKhongHopLe(code, message) {
  return Object.freeze({
    httpStatus: null,
    code,
    message,
    fieldErrors: {},
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  })
}

function laDoiTuong(giaTri) {
  return giaTri !== null && typeof giaTri === 'object' && !Array.isArray(giaTri)
}

function laSoNguyenDuongAnToan(giaTri) {
  return Number.isSafeInteger(giaTri) && giaTri > 0
}

function laPhanTrangHopLe(phanTrang) {
  return laDoiTuong(phanTrang)
    && Number.isInteger(phanTrang.current_page)
    && phanTrang.current_page >= 1
    && Number.isInteger(phanTrang.per_page)
    && phanTrang.per_page >= 1
    && Number.isInteger(phanTrang.total)
    && phanTrang.total >= 0
    && Number.isInteger(phanTrang.last_page)
    && phanTrang.last_page >= 1
    && phanTrang.current_page <= phanTrang.last_page
}

function laSuKienHopLe(suKien) {
  return laDoiTuong(suKien)
    && laSoNguyenDuongAnToan(suKien.event_id)
    && (suKien.payment_id === null || laSoNguyenDuongAnToan(suKien.payment_id))
    && typeof suKien.processing_status === 'string'
    && suKien.processing_status.trim() !== ''
}

function laIdNeuCo(doiTuong, truong) {
  return !Object.prototype.hasOwnProperty.call(doiTuong, truong)
    || laSoNguyenDuongAnToan(doiTuong[truong])
}

function laTrangThaiNeuCo(doiTuong) {
  return !Object.prototype.hasOwnProperty.call(doiTuong, 'status')
    || (typeof doiTuong.status === 'string' && doiTuong.status.trim() !== '')
}

function laDonMuaHopLe(donMua) {
  return laDoiTuong(donMua)
    && laIdNeuCo(donMua, 'id')
    && laTrangThaiNeuCo(donMua)
}

function laHoiVienHopLe(hoiVien) {
  return laDoiTuong(hoiVien)
    && laIdNeuCo(hoiVien, 'id')
    && laIdNeuCo(hoiVien, 'account_id')
}

function laKyHanHopLe(kyHan) {
  return laDoiTuong(kyHan)
    && laIdNeuCo(kyHan, 'id')
    && laIdNeuCo(kyHan, 'sequence')
    && laTrangThaiNeuCo(kyHan)
}

function laThanhToanHopLe(thanhToan) {
  const coCauTrucCoBan = laDoiTuong(thanhToan)
    && laSoNguyenDuongAnToan(thanhToan.payment_id)
    && typeof thanhToan.status === 'string'
    && thanhToan.status.trim() !== ''

  if (!coCauTrucCoBan) {
    return false
  }

  for (const truong of ['order', 'member', 'membership_term']) {
    if (Object.prototype.hasOwnProperty.call(thanhToan, truong)
      && thanhToan[truong] !== null
      && !laDoiTuong(thanhToan[truong])) {
      return false
    }
  }

  if (thanhToan.order !== undefined && thanhToan.order !== null && !laDonMuaHopLe(thanhToan.order)) {
    return false
  }
  if (thanhToan.member !== undefined && thanhToan.member !== null && !laHoiVienHopLe(thanhToan.member)) {
    return false
  }
  if (thanhToan.membership_term !== undefined
    && thanhToan.membership_term !== null
    && !laKyHanHopLe(thanhToan.membership_term)) {
    return false
  }

  return (!Object.prototype.hasOwnProperty.call(thanhToan, 'events')
    || (Array.isArray(thanhToan.events) && thanhToan.events.every(laSuKienHopLe)))
}

function themTruongDonGianNeuCo(nguon, dich, truong) {
  if (!Object.prototype.hasOwnProperty.call(nguon, truong)) {
    return
  }

  const giaTri = nguon[truong]
  if (giaTri === null || ['string', 'number', 'boolean'].includes(typeof giaTri)) {
    dich[truong] = giaTri
  }
}

function taoDonMuaAnToan(nguon) {
  const ketQua = {}
  themTruongDonGianNeuCo(nguon, ketQua, 'id')
  themTruongDonGianNeuCo(nguon, ketQua, 'code')
  themTruongDonGianNeuCo(nguon, ketQua, 'status')
  themTruongDonGianNeuCo(nguon, ketQua, 'expected_amount')
  themTruongDonGianNeuCo(nguon, ketQua, 'currency')
  themTruongDonGianNeuCo(nguon, ketQua, 'price_locked_at')
  return ketQua
}

function taoHoiVienAnToan(nguon) {
  const ketQua = {}
  themTruongDonGianNeuCo(nguon, ketQua, 'id')
  themTruongDonGianNeuCo(nguon, ketQua, 'code')
  themTruongDonGianNeuCo(nguon, ketQua, 'account_id')
  themTruongDonGianNeuCo(nguon, ketQua, 'email')
  return ketQua
}

function taoKyHanAnToan(nguon) {
  const ketQua = {}
  themTruongDonGianNeuCo(nguon, ketQua, 'id')
  themTruongDonGianNeuCo(nguon, ketQua, 'status')
  themTruongDonGianNeuCo(nguon, ketQua, 'sequence')
  themTruongDonGianNeuCo(nguon, ketQua, 'package_name')
  themTruongDonGianNeuCo(nguon, ketQua, 'package_version')
  themTruongDonGianNeuCo(nguon, ketQua, 'purchase_price')
  return ketQua
}

function taoSuKienAnToan(suKien) {
  const ketQua = {}
  themTruongDonGianNeuCo(suKien, ketQua, 'event_id')
  themTruongDonGianNeuCo(suKien, ketQua, 'payment_id')
  themTruongDonGianNeuCo(suKien, ketQua, 'channel')
  themTruongDonGianNeuCo(suKien, ketQua, 'provider_order_code')
  themTruongDonGianNeuCo(suKien, ketQua, 'provider_payment_link_id')
  themTruongDonGianNeuCo(suKien, ketQua, 'provider_reference')
  themTruongDonGianNeuCo(suKien, ketQua, 'amount')
  themTruongDonGianNeuCo(suKien, ketQua, 'currency')
  themTruongDonGianNeuCo(suKien, ketQua, 'provider_result_code')
  themTruongDonGianNeuCo(suKien, ketQua, 'processing_status')
  themTruongDonGianNeuCo(suKien, ketQua, 'receipt_count')
  themTruongDonGianNeuCo(suKien, ketQua, 'received_at')
  themTruongDonGianNeuCo(suKien, ketQua, 'last_received_at')
  themTruongDonGianNeuCo(suKien, ketQua, 'processed_at')
  themTruongDonGianNeuCo(suKien, ketQua, 'reconciliation_reason')
  themTruongDonGianNeuCo(suKien, ketQua, 'created_at')
  return ketQua
}

function taoThanhToanAnToan(thanhToan) {
  const ketQua = {}
  themTruongDonGianNeuCo(thanhToan, ketQua, 'payment_id')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'attempt')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'channel')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'provider_order_code')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'provider_payment_link_id')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'provider_reference')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'expected_amount')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'received_amount')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'currency')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'status')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'reconciliation_reason')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'paid_at')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'confirmed_at')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'expires_at')

  if (Object.prototype.hasOwnProperty.call(thanhToan, 'order')) {
    ketQua.order = thanhToan.order === null ? null : taoDonMuaAnToan(thanhToan.order)
  }
  if (Object.prototype.hasOwnProperty.call(thanhToan, 'member')) {
    ketQua.member = thanhToan.member === null ? null : taoHoiVienAnToan(thanhToan.member)
  }
  if (Object.prototype.hasOwnProperty.call(thanhToan, 'membership_term')) {
    ketQua.membership_term = thanhToan.membership_term === null
      ? null
      : taoKyHanAnToan(thanhToan.membership_term)
  }
  if (Object.prototype.hasOwnProperty.call(thanhToan, 'events')) {
    ketQua.events = thanhToan.events.map(taoSuKienAnToan)
  }

  themTruongDonGianNeuCo(thanhToan, ketQua, 'created_at')
  themTruongDonGianNeuCo(thanhToan, ketQua, 'updated_at')
  return ketQua
}

function taoPhanTrangAnToan(phanTrang) {
  return {
    current_page: phanTrang.current_page,
    per_page: phanTrang.per_page,
    total: phanTrang.total,
    last_page: phanTrang.last_page,
  }
}

function taoDanhSachAnToan(duLieu, taoDongAnToan) {
  return {
    items: duLieu.items.map(taoDongAnToan),
    pagination: taoPhanTrangAnToan(duLieu.pagination),
  }
}

function laDanhSachHopLe(duLieu, kiemTraDong) {
  return laDoiTuong(duLieu)
    && Array.isArray(duLieu.items)
    && duLieu.items.every(kiemTraDong)
    && laPhanTrangHopLe(duLieu.pagination)
}

function layTrangAnToan(trang) {
  const soTrang = Number(trang)
  return Number.isSafeInteger(soTrang) && soTrang > 0 ? soTrang : 1
}

function layLoiKhongHopLeId() {
  return taoLoiAnToan({
    httpStatus: 404,
    code: 'PAYMENT_NOT_FOUND',
    message: 'Không tìm thấy thông tin thanh toán.',
  }, 'Không tìm thấy thông tin thanh toán.')
}

function laLoiCamQuyen(error) {
  return error?.httpStatus === 403
}

function taoState() {
  return {
    danhSachThanhToan: [],
    boLocThanhToan: taoBoLoc(BO_LOC_THANH_TOAN_MAC_DINH, BO_LOC_THANH_TOAN_MAC_DINH),
    phanTrangThanhToan: { ...PHAN_TRANG_MAC_DINH },
    dangTaiThanhToan: false,
    loiThanhToan: null,
    daTaiThanhToanLanDau: false,
    soThuTuThanhToan: 0,

    chiTietThanhToan: null,
    dangTaiChiTiet: false,
    loiChiTiet: null,
    daTaiChiTietLanDau: false,
    soThuTuChiTiet: 0,

    danhSachCanDoiSoat: [],
    boLocCanDoiSoat: taoBoLoc(BO_LOC_CAN_DOI_SOAT_MAC_DINH, BO_LOC_CAN_DOI_SOAT_MAC_DINH),
    phanTrangCanDoiSoat: { ...PHAN_TRANG_MAC_DINH },
    dangTaiCanDoiSoat: false,
    loiCanDoiSoat: null,
    daTaiCanDoiSoatLanDau: false,
    soThuTuCanDoiSoat: 0,

    danhSachSuKienThanhToan: [],
    boLocSuKienThanhToan: taoBoLoc(BO_LOC_SU_KIEN_MAC_DINH, BO_LOC_SU_KIEN_MAC_DINH),
    phanTrangSuKienThanhToan: { ...PHAN_TRANG_MAC_DINH },
    dangTaiSuKienThanhToan: false,
    loiSuKienThanhToan: null,
    daTaiSuKienThanhToanLanDau: false,
    soThuTuSuKienThanhToan: 0,
  }
}

/**
 * Xóa Payment state chỉ khi Pinia đã tạo store này.
 * Input: Pinia instance tùy chọn, mặc định là active instance.
 * Process: tăng request sequence trước khi reset để response đang bay không commit.
 * Output: true khi store tồn tại, false khi cleanup không khởi tạo domain ngoài ý muốn.
 * Side effect: xóa list/detail/reconciliation khỏi memory, không đụng storage Auth.
 */
export function xoaDuLieuThanhToanNeuDaKhoiTao(pinia = getActivePinia()) {
  if (!pinia?.state?.value?.thanh_toan) {
    return false
  }

  useThanhToanStore(pinia).xoaDuLieu()
  return true
}

export const useThanhToanStore = defineStore('thanh_toan', {
  state: taoState,

  getters: {
    danhSachThanhToanCanDoiSoat: (state) => state.danhSachCanDoiSoat,
    danhSachSuKien: (state) => state.danhSachSuKienThanhToan,
  },

  actions: {
    /**
     * Tải Payment list với server pagination và request sequence riêng.
     * Input: filter Payment đã nhập và trang muốn đọc.
     * Process: phát GET, fail-closed nếu envelope/DTO không đúng, bỏ qua response cũ;
     * 403 reset toàn bộ Payment state để page điều hướng cấm quyền.
     * Output: dữ liệu list hợp lệ hoặc null nếu response đã stale.
     * Side effect: chỉ đọc Backend; 401 để Auth interceptor dùng shared handling.
     */
    async taiDanhSachThanhToan({ boLoc = this.boLocThanhToan, trang = 1 } = {}) {
      const boLocDaChuanHoa = taoBoLoc(boLoc, BO_LOC_THANH_TOAN_MAC_DINH)
      const trangYeuCau = layTrangAnToan(trang)
      const sequence = ++this.soThuTuThanhToan
      this.dangTaiThanhToan = true
      this.loiThanhToan = null

      try {
        const phanHoi = await taiDanhSachThanhToanApi({ ...boLocDaChuanHoa, page: trangYeuCau })
        const duLieu = phanHoi?.data

        if (!laDanhSachHopLe(duLieu, laThanhToanHopLe)) {
          throw taoLoiPhanHoiKhongHopLe(
            'PAYMENT_LIST_RESPONSE_INVALID',
            'Dữ liệu danh sách thanh toán không hợp lệ.',
          )
        }

        if (sequence !== this.soThuTuThanhToan) {
          return null
        }

        const duLieuAnToan = taoDanhSachAnToan(duLieu, taoThanhToanAnToan)
        this.danhSachThanhToan = duLieuAnToan.items
        this.boLocThanhToan = boLocDaChuanHoa
        this.phanTrangThanhToan = duLieuAnToan.pagination
        this.daTaiThanhToanLanDau = true
        return duLieuAnToan
      } catch (error) {
        if (sequence !== this.soThuTuThanhToan) {
          return null
        }

        const loi = taoLoiAnToan(error, 'Không thể tải danh sách thanh toán. Vui lòng thử lại sau.')
        if (laLoiCamQuyen(loi)) {
          this.xoaDuLieu()
        } else {
          this.loiThanhToan = loi
        }
        throw loi
      } finally {
        if (sequence === this.soThuTuThanhToan) {
          this.dangTaiThanhToan = false
        }
      }
    },

    /**
     * Tải detail Payment theo ID dương và invalidate detail cũ trước khi đọc.
     * Input: payment id từ route; Backend vẫn quyết định branch ownership/404.
     * Process: tăng sequence, phát GET, validate safe DTO và dọn detail khi 403/404.
     * Output: Payment detail hoặc null nếu request cũ đã bị vô hiệu.
     * Side effect: không thay đổi list và không gửi bất kỳ mutation control nào.
     */
    async taiChiTietThanhToan(id) {
      const sequence = ++this.soThuTuChiTiet
      this.chiTietThanhToan = null
      this.dangTaiChiTiet = true
      this.loiChiTiet = null
      this.daTaiChiTietLanDau = false

      try {
        if (!laIdThanhToanHopLe(id)) {
          throw layLoiKhongHopLeId()
        }

        const phanHoi = await taiChiTietThanhToanApi(id)
        const duLieu = phanHoi?.data

        if (!laThanhToanHopLe(duLieu)) {
          throw taoLoiPhanHoiKhongHopLe(
            'PAYMENT_DETAIL_RESPONSE_INVALID',
            'Dữ liệu chi tiết thanh toán không hợp lệ.',
          )
        }

        if (sequence !== this.soThuTuChiTiet) {
          return null
        }

        const duLieuAnToan = taoThanhToanAnToan(duLieu)
        this.chiTietThanhToan = duLieuAnToan
        this.daTaiChiTietLanDau = true
        return duLieuAnToan
      } catch (error) {
        if (sequence !== this.soThuTuChiTiet) {
          return null
        }

        const loi = taoLoiAnToan(error, 'Không thể tải chi tiết thanh toán. Vui lòng thử lại sau.')
        this.chiTietThanhToan = null
        if (laLoiCamQuyen(loi)) {
          this.xoaDuLieu()
        } else {
          this.loiChiTiet = loi
        }
        throw loi
      } finally {
        if (sequence === this.soThuTuChiTiet) {
          this.dangTaiChiTiet = false
        }
      }
    },

    /**
     * Tải Payment queue authoritative cho màn hình đối soát.
     * Input: filter Payment độc lập với event filter và trang hiện tại.
     * Process: gọi API với cờ reconciliation bắt buộc; không tìm/ghép event ở client.
     * Output: list Payment safe DTO và pagination Backend.
     * Side effect: chỉ đọc; 403 dọn domain để page chuyển khung cấm quyền.
     */
    async taiDanhSachCanDoiSoat({ boLoc = this.boLocCanDoiSoat, trang = 1 } = {}) {
      const boLocDaChuanHoa = taoBoLoc(boLoc, BO_LOC_CAN_DOI_SOAT_MAC_DINH)
      const trangYeuCau = layTrangAnToan(trang)
      const sequence = ++this.soThuTuCanDoiSoat
      this.dangTaiCanDoiSoat = true
      this.loiCanDoiSoat = null

      try {
        const phanHoi = await taiDanhSachCanDoiSoatApi({ ...boLocDaChuanHoa, page: trangYeuCau })
        const duLieu = phanHoi?.data

        if (!laDanhSachHopLe(duLieu, laThanhToanHopLe)) {
          throw taoLoiPhanHoiKhongHopLe(
            'RECONCILIATION_PAYMENT_RESPONSE_INVALID',
            'Dữ liệu hàng đợi thanh toán cần đối soát không hợp lệ.',
          )
        }

        if (sequence !== this.soThuTuCanDoiSoat) {
          return null
        }

        const duLieuAnToan = taoDanhSachAnToan(duLieu, taoThanhToanAnToan)
        this.danhSachCanDoiSoat = duLieuAnToan.items
        this.boLocCanDoiSoat = boLocDaChuanHoa
        this.phanTrangCanDoiSoat = duLieuAnToan.pagination
        this.daTaiCanDoiSoatLanDau = true
        return duLieuAnToan
      } catch (error) {
        if (sequence !== this.soThuTuCanDoiSoat) {
          return null
        }

        const loi = taoLoiAnToan(error, 'Không thể tải hàng đợi thanh toán cần đối soát.')
        if (laLoiCamQuyen(loi)) {
          this.xoaDuLieu()
        } else {
          this.loiCanDoiSoat = loi
        }
        throw loi
      } finally {
        if (sequence === this.soThuTuCanDoiSoat) {
          this.dangTaiCanDoiSoat = false
        }
      }
    },

    /**
     * Tải event queue độc lập, giữ nguyên event chưa liên kết có payment_id null.
     * Input: event filter đúng contract Backend và trang server.
     * Process: gọi endpoint event riêng, validate từng event và bỏ qua response stale.
     * Output: danh sách event safe DTO; không join với Payment queue.
     * Side effect: chỉ đọc; không tự tạo link detail cho event chưa liên kết.
     */
    async taiSuKienThanhToan({ boLoc = this.boLocSuKienThanhToan, trang = 1 } = {}) {
      const boLocDaChuanHoa = taoBoLoc(boLoc, BO_LOC_SU_KIEN_MAC_DINH)
      const trangYeuCau = layTrangAnToan(trang)
      const sequence = ++this.soThuTuSuKienThanhToan
      this.dangTaiSuKienThanhToan = true
      this.loiSuKienThanhToan = null

      try {
        const phanHoi = await taiSuKienThanhToanApi({ ...boLocDaChuanHoa, page: trangYeuCau })
        const duLieu = phanHoi?.data

        if (!laDanhSachHopLe(duLieu, laSuKienHopLe)) {
          throw taoLoiPhanHoiKhongHopLe(
            'RECONCILIATION_EVENT_RESPONSE_INVALID',
            'Dữ liệu hàng đợi sự kiện cần đối soát không hợp lệ.',
          )
        }

        if (sequence !== this.soThuTuSuKienThanhToan) {
          return null
        }

        const duLieuAnToan = taoDanhSachAnToan(duLieu, taoSuKienAnToan)
        this.danhSachSuKienThanhToan = duLieuAnToan.items
        this.boLocSuKienThanhToan = boLocDaChuanHoa
        this.phanTrangSuKienThanhToan = duLieuAnToan.pagination
        this.daTaiSuKienThanhToanLanDau = true
        return duLieuAnToan
      } catch (error) {
        if (sequence !== this.soThuTuSuKienThanhToan) {
          return null
        }

        const loi = taoLoiAnToan(error, 'Không thể tải hàng đợi sự kiện cần đối soát.')
        if (laLoiCamQuyen(loi)) {
          this.xoaDuLieu()
        } else {
          this.loiSuKienThanhToan = loi
        }
        throw loi
      } finally {
        if (sequence === this.soThuTuSuKienThanhToan) {
          this.dangTaiSuKienThanhToan = false
        }
      }
    },

    apDungBoLocThanhToan(boLoc) {
      return this.taiDanhSachThanhToan({ boLoc, trang: 1 })
    },

    datLaiBoLocThanhToan() {
      return this.taiDanhSachThanhToan({ boLoc: BO_LOC_THANH_TOAN_MAC_DINH, trang: 1 })
    },

    chuyenTrangThanhToan(trang) {
      return this.taiDanhSachThanhToan({ boLoc: this.boLocThanhToan, trang })
    },

    apDungBoLocCanDoiSoat(boLoc) {
      return this.taiDanhSachCanDoiSoat({ boLoc, trang: 1 })
    },

    datLaiBoLocCanDoiSoat() {
      return this.taiDanhSachCanDoiSoat({ boLoc: BO_LOC_CAN_DOI_SOAT_MAC_DINH, trang: 1 })
    },

    chuyenTrangCanDoiSoat(trang) {
      return this.taiDanhSachCanDoiSoat({ boLoc: this.boLocCanDoiSoat, trang })
    },

    apDungBoLocSuKienThanhToan(boLoc) {
      return this.taiSuKienThanhToan({ boLoc, trang: 1 })
    },

    datLaiBoLocSuKienThanhToan() {
      return this.taiSuKienThanhToan({ boLoc: BO_LOC_SU_KIEN_MAC_DINH, trang: 1 })
    },

    chuyenTrangSuKienThanhToan(trang) {
      return this.taiSuKienThanhToan({ boLoc: this.boLocSuKienThanhToan, trang })
    },

    xoaChiTietThanhToan() {
      this.soThuTuChiTiet += 1
      this.chiTietThanhToan = null
      this.dangTaiChiTiet = false
      this.loiChiTiet = null
      this.daTaiChiTietLanDau = false
    },

    xoaDuLieu() {
      this.soThuTuThanhToan += 1
      this.soThuTuChiTiet += 1
      this.soThuTuCanDoiSoat += 1
      this.soThuTuSuKienThanhToan += 1
      const soThuTuThanhToan = this.soThuTuThanhToan
      const soThuTuChiTiet = this.soThuTuChiTiet
      const soThuTuCanDoiSoat = this.soThuTuCanDoiSoat
      const soThuTuSuKienThanhToan = this.soThuTuSuKienThanhToan
      Object.assign(this.$state, taoState())
      this.soThuTuThanhToan = soThuTuThanhToan
      this.soThuTuChiTiet = soThuTuChiTiet
      this.soThuTuCanDoiSoat = soThuTuCanDoiSoat
      this.soThuTuSuKienThanhToan = soThuTuSuKienThanhToan
    },
  },
})

export {
  laDanhSachHopLe,
  laPhanTrangHopLe,
  laSuKienHopLe,
  laThanhToanHopLe,
}
