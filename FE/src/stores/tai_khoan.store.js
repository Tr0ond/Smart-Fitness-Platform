import { defineStore, getActivePinia } from 'pinia'
import {
  CAC_CHUYEN_DOI_VAI_TRO,
  CAC_TRANG_THAI_TAI_KHOAN,
  CAC_VAI_TRO_TAI_KHOAN,
  capVaiTro as capVaiTroApi,
  capNhatTrangThaiTaiKhoan as capNhatTrangThaiTaiKhoanApi,
  laIdTaiKhoanHopLe,
  thuHoiVaiTro as thuHoiVaiTroApi,
  taiChiTietTaiKhoan as taiChiTietTaiKhoanApi,
  taiChiTietHoiVien as taiChiTietHoiVienApi,
  taiChiTietNhanVienLeTan as taiChiTietNhanVienLeTanApi,
  taiDanhSachTaiKhoan as taiDanhSachTaiKhoanApi,
  taiDanhSachHoiVien as taiDanhSachHoiVienApi,
  taiDanhSachNhanVienLeTan as taiDanhSachNhanVienLeTanApi,
} from '../services/tai_khoan.api.js'

const BO_LOC_MAC_DINH = Object.freeze({
  search: '',
  status: '',
  role: '',
  per_page: 20,
})

const PHAN_TRANG_MAC_DINH = Object.freeze({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
})

const BO_LOC_HOI_VIEN_MAC_DINH = Object.freeze({
  search: '',
  status: '',
  per_page: 20,
})

const PHAN_TRANG_HOI_VIEN_MAC_DINH = Object.freeze({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
})

const BO_LOC_NHAN_VIEN_LE_TAN_MAC_DINH = Object.freeze({
  search: '',
  status: '',
  per_page: 20,
})

const PHAN_TRANG_NHAN_VIEN_LE_TAN_MAC_DINH = Object.freeze({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
})

function taoBoLoc(boLoc = BO_LOC_MAC_DINH) {
  return {
    search: typeof boLoc?.search === 'string' ? boLoc.search.trim() : '',
    status: typeof boLoc?.status === 'string' ? boLoc.status.trim() : '',
    role: typeof boLoc?.role === 'string' ? boLoc.role.trim() : '',
    per_page: Number.isInteger(boLoc?.per_page) ? boLoc.per_page : 20,
  }
}

function taoBoLocHoiVien(boLoc = BO_LOC_HOI_VIEN_MAC_DINH) {
  return {
    search: typeof boLoc?.search === 'string' ? boLoc.search.trim() : '',
    status: typeof boLoc?.status === 'string' ? boLoc.status.trim() : '',
    per_page: Number.isInteger(boLoc?.per_page) ? boLoc.per_page : 20,
  }
}

function taoBoLocNhanVienLeTan(boLoc = BO_LOC_NHAN_VIEN_LE_TAN_MAC_DINH) {
  return {
    search: typeof boLoc?.search === 'string' ? boLoc.search.trim() : '',
    status: typeof boLoc?.status === 'string' ? boLoc.status.trim() : '',
    per_page: Number.isInteger(boLoc?.per_page) ? boLoc.per_page : 20,
  }
}

function taoLoiAnToan(
  error,
  thongBaoMacDinh = 'Không thể tải dữ liệu tài khoản. Vui lòng thử lại sau.',
) {
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
    ...(error?.outcomeUnknown === true ? { outcomeUnknown: true } : {}),
  })
}

function laCauTrucPhanTrangHopLe(phanTrang) {
  return phanTrang !== null
    && typeof phanTrang === 'object'
    && Number.isInteger(phanTrang.current_page)
    && phanTrang.current_page >= 1
    && Number.isInteger(phanTrang.per_page)
    && phanTrang.per_page >= 1
    && Number.isInteger(phanTrang.total)
    && phanTrang.total >= 0
    && Number.isInteger(phanTrang.last_page)
    && phanTrang.last_page >= 1
}

function laPhanTrangHopLe(phanTrang) {
  return laCauTrucPhanTrangHopLe(phanTrang)
    && phanTrang.current_page <= phanTrang.last_page
}

function taoLoiPhanHoiKhongHopLe() {
  return {
    code: 'ACCOUNT_LIST_RESPONSE_INVALID',
    message: 'Dữ liệu danh sách tài khoản không hợp lệ.',
    fieldErrors: {},
  }
}

function taoLoiChiTietKhongHopLe() {
  return {
    code: 'ACCOUNT_DETAIL_RESPONSE_INVALID',
    message: 'Dữ liệu chi tiết tài khoản không hợp lệ.',
    fieldErrors: {},
  }
}

function taoLoiPhanHoiHoiVienKhongHopLe() {
  return {
    code: 'MEMBER_LIST_RESPONSE_INVALID',
    message: 'Dữ liệu danh sách Hội viên không hợp lệ.',
    fieldErrors: {},
  }
}

function taoLoiChiTietHoiVienKhongHopLe() {
  return {
    httpStatus: 404,
    code: 'MEMBER_ORIENTATION_INVALID',
    message: 'Không thể truy cập dữ liệu này.',
    fieldErrors: {},
    isNetworkError: false,
  }
}

function taoLoiPhanHoiNhanVienLeTanKhongHopLe() {
  return {
    code: 'RECEPTIONIST_LIST_RESPONSE_INVALID',
    message: 'Dữ liệu danh sách Nhân viên lễ tân không hợp lệ.',
    fieldErrors: {},
  }
}

function taoLoiChiTietNhanVienLeTanKhongHopLe() {
  return {
    httpStatus: 404,
    code: 'RECEPTIONIST_ORIENTATION_INVALID',
    message: 'Không thể truy cập dữ liệu này.',
    fieldErrors: {},
    isNetworkError: false,
  }
}

function taoLoiKetQuaKhongXacDinh(
  error,
  thongBao = 'Chưa xác định được kết quả cập nhật trạng thái. Đang kiểm tra lại dữ liệu hiện tại.',
) {
  const loi = taoLoiAnToan(
    {
      ...error,
      outcomeUnknown: true,
      message: thongBao,
    },
    thongBao,
  )

  return Object.freeze(loi)
}

function taoLoiKetQuaVaiTroKhongHopLe() {
  return {
    code: 'ACCOUNT_ROLE_RESPONSE_INVALID',
    message: 'Dữ liệu kết quả vai trò không hợp lệ.',
    fieldErrors: {},
  }
}

function taoLoiKetQuaVaiTroKhongXacDinh(error = {}) {
  return taoLoiKetQuaKhongXacDinh(
    error,
    'Chưa xác định được kết quả thay đổi vai trò. Đang kiểm tra lại dữ liệu hiện tại.',
  )
}

function laKetQuaVaiTroHopLe(phanHoi) {
  const ketQua = phanHoi?.data

  return ketQua !== null
    && typeof ketQua === 'object'
    && laIdTaiKhoanHopLe(ketQua.account_id)
    && CAC_VAI_TRO_TAI_KHOAN.includes(ketQua.role)
    && typeof ketQua.active === 'boolean'
    && typeof ketQua.changed === 'boolean'
    && CAC_CHUYEN_DOI_VAI_TRO.includes(ketQua.transition)
}

function layPhanQuyenTaiKhoan(taiKhoan, maVaiTro) {
  return Array.isArray(taiKhoan?.roles)
    ? taiKhoan.roles.find((vaiTro) => vaiTro?.code === maVaiTro) ?? null
    : null
}

function laVaiTroDatTrangThai(taiKhoan, maVaiTro, hanhDong) {
  const phanQuyen = layPhanQuyenTaiKhoan(taiKhoan, maVaiTro)

  return hanhDong === 'cap'
    ? phanQuyen?.active === true
    : phanQuyen?.active === false
}

function taoKetQuaVaiTroKhongThayDoi(taiKhoan, maVaiTro, hanhDong) {
  const phanQuyen = layPhanQuyenTaiKhoan(taiKhoan, maVaiTro)

  return {
    assignment_id: Number.isSafeInteger(phanQuyen?.assignment_id) ? phanQuyen.assignment_id : null,
    account_id: Number(taiKhoan.id),
    role: maVaiTro,
    active: hanhDong === 'cap',
    changed: false,
    transition: 'UNCHANGED',
    local_noop: true,
  }
}

function taoThongBaoThayDoiVaiTro(transition) {
  return {
    GRANTED: 'Đã cấp vai trò cho tài khoản.',
    REGRANTED: 'Đã cấp lại vai trò cho tài khoản.',
    REVOKED: 'Đã thu hồi vai trò của tài khoản.',
    UNCHANGED: 'Vai trò đã ở đúng trạng thái yêu cầu.',
  }[transition] ?? 'Đã xử lý thay đổi vai trò.'
}

function laTaiKhoanChiTietHopLe(phanHoi) {
  const taiKhoan = phanHoi?.data

  return taiKhoan !== null
    && typeof taiKhoan === 'object'
    && laIdTaiKhoanHopLe(taiKhoan.id)
    && typeof taiKhoan.name === 'string'
    && typeof taiKhoan.email === 'string'
    && CAC_TRANG_THAI_TAI_KHOAN.includes(taiKhoan.status)
    && Array.isArray(taiKhoan.roles)
}

function laTaiKhoanCoVaiTroMemberDangHoatDong(taiKhoan) {
  return Array.isArray(taiKhoan?.roles)
    && taiKhoan.roles.some((vaiTro) => vaiTro?.code === 'MEMBER' && vaiTro?.active === true)
}

function laTaiKhoanHoiVienHopLe(taiKhoan) {
  return taiKhoan !== null
    && typeof taiKhoan === 'object'
    && laIdTaiKhoanHopLe(taiKhoan.id)
    && typeof taiKhoan.name === 'string'
    && typeof taiKhoan.email === 'string'
    && CAC_TRANG_THAI_TAI_KHOAN.includes(taiKhoan.status)
    && laTaiKhoanCoVaiTroMemberDangHoatDong(taiKhoan)
}

function laTaiKhoanCoVaiTroNhanVienLeTanDangHoatDong(taiKhoan) {
  return Array.isArray(taiKhoan?.roles)
    && taiKhoan.roles.some((vaiTro) => vaiTro?.code === 'RECEPTIONIST' && vaiTro?.active === true)
}

function laTaiKhoanNhanVienLeTanHopLe(taiKhoan) {
  return taiKhoan !== null
    && typeof taiKhoan === 'object'
    && laIdTaiKhoanHopLe(taiKhoan.id)
    && typeof taiKhoan.name === 'string'
    && typeof taiKhoan.email === 'string'
    && CAC_TRANG_THAI_TAI_KHOAN.includes(taiKhoan.status)
    && laTaiKhoanCoVaiTroNhanVienLeTanDangHoatDong(taiKhoan)
}

function laDanhSachTaiKhoanHopLe(phanHoi, choPhepTrangVuotBien = false) {
  const duLieu = phanHoi?.data
  const phanTrangHopLe = choPhepTrangVuotBien
    ? laCauTrucPhanTrangHopLe(duLieu?.pagination)
    : laPhanTrangHopLe(duLieu?.pagination)

  return Array.isArray(duLieu?.items) && phanTrangHopLe
}

function laDanhSachHoiVienHopLe(phanHoi, choPhepTrangVuotBien = false) {
  const duLieu = phanHoi?.data
  const phanTrangHopLe = choPhepTrangVuotBien
    ? laCauTrucPhanTrangHopLe(duLieu?.pagination)
    : laPhanTrangHopLe(duLieu?.pagination)

  return Array.isArray(duLieu?.items)
    && phanTrangHopLe
    && duLieu.items.every(laTaiKhoanHoiVienHopLe)
}

function laDanhSachNhanVienLeTanHopLe(phanHoi, choPhepTrangVuotBien = false) {
  const duLieu = phanHoi?.data
  const phanTrangHopLe = choPhepTrangVuotBien
    ? laCauTrucPhanTrangHopLe(duLieu?.pagination)
    : laPhanTrangHopLe(duLieu?.pagination)

  return Array.isArray(duLieu?.items)
    && phanTrangHopLe
    && duLieu.items.every(laTaiKhoanNhanVienLeTanHopLe)
}

/**
 * Tai mot trang list va sua duy nhat mot lan neu trang yeu cau vuot last_page moi.
 *
 * Dau vao: API GET, filter allow-list, page, validator/domain error va sequence predicate.
 * Cach hoat dong: validate response dau; neu current_page > last_page thi GET lai last_page
 * dung mot lan, sau do bat buoc response cuoi nam trong bien server authoritative.
 * Ket qua: data list hop le, null khi request da stale, hoac throw loi contract co kiem soat.
 * Side effect: mot GET binh thuong, toi da hai GET khi dataset co lai; khong mutation Backend.
 * Concurrency Rule: khong khoi tao corrective GET neu request khong con la request moi nhat.
 */
async function taiTrangDanhSachCoHieuChinh({
  goiApi,
  boLoc,
  trangYeuCau,
  laPhanHoiHopLe,
  taoLoiKhongHopLe,
  conLaYeuCauHienTai,
}) {
  let phanHoi = await goiApi({ ...boLoc, page: trangYeuCau })

  if (!laPhanHoiHopLe(phanHoi, true)) {
    throw taoLoiKhongHopLe()
  }

  if (!conLaYeuCauHienTai()) {
    return null
  }

  const phanTrang = phanHoi.data.pagination
  if (phanTrang.current_page > phanTrang.last_page) {
    phanHoi = await goiApi({ ...boLoc, page: phanTrang.last_page })

    if (!laPhanHoiHopLe(phanHoi, false)) {
      throw taoLoiKhongHopLe()
    }

    if (!conLaYeuCauHienTai()) {
      return null
    }
  } else if (!laPhanHoiHopLe(phanHoi, false)) {
    throw taoLoiKhongHopLe()
  }

  return phanHoi.data
}

function laLoiKhongBietKetQua(error) {
  return error?.isNetworkError === true
    || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

/**
 * Xoa du lieu list Account khi phien, actor hoac authority Auth thay doi.
 *
 * Dau vao: tuy chon Pinia instance; neu bo qua thi dung active instance.
 * Cach hoat dong: chi reset store da duoc khoi tao, tang sequence de vo hieu response
 * dang bay, va khong tao store moi trong logout path.
 * Ket qua: list, filter, pagination, loading va error tro ve trang thai ban dau.
 * Side effect: xoa du lieu Account trong memory; khong doc/ghi localStorage hay sessionStorage.
 * Security Rule: khong giu account/role data sau logout, doi actor hoac auth loss.
 */
export function xoaDuLieuTaiKhoanNeuDaKhoiTao(pinia = getActivePinia()) {
  if (!pinia?.state?.value?.tai_khoan) {
    return false
  }

  useTaiKhoanStore(pinia).xoaDuLieu()
  return true
}

export const useTaiKhoanStore = defineStore('tai_khoan', {
  state: () => ({
    danhSachTaiKhoan: [],
    boLoc: taoBoLoc(),
    phanTrang: { ...PHAN_TRANG_MAC_DINH },
    dangTai: false,
    loiTaiDanhSach: null,
    daTaiLanDau: false,
    soThuTuYeuCau: 0,
    taiKhoanDaChon: null,
    dangTaiChiTiet: false,
    loiTaiChiTiet: null,
    daTaiChiTietLanDau: false,
    dangCapNhatTrangThai: false,
    loiCapNhatTrangThai: null,
    thongBaoCapNhatTrangThai: null,
    soThuTuYeuCauChiTiet: 0,
    soThuTuCapNhatTrangThai: 0,
    dangThayDoiVaiTro: false,
    vaiTroDangXuLy: null,
    loiThayDoiVaiTro: null,
    thongBaoThayDoiVaiTro: null,
    ketQuaThayDoiVaiTro: null,
    soThuTuThayDoiVaiTro: 0,
    danhSachHoiVien: [],
    boLocHoiVien: taoBoLocHoiVien(),
    phanTrangHoiVien: { ...PHAN_TRANG_HOI_VIEN_MAC_DINH },
    dangTaiHoiVien: false,
    loiTaiHoiVien: null,
    daTaiHoiVienLanDau: false,
    soThuTuYeuCauHoiVien: 0,
    hoiVienDaChon: null,
    dangTaiChiTietHoiVien: false,
    loiTaiChiTietHoiVien: null,
    daTaiChiTietHoiVienLanDau: false,
    soThuTuYeuCauChiTietHoiVien: 0,
    danhSachNhanVienLeTan: [],
    boLocNhanVienLeTan: taoBoLocNhanVienLeTan(),
    phanTrangNhanVienLeTan: { ...PHAN_TRANG_NHAN_VIEN_LE_TAN_MAC_DINH },
    dangTaiNhanVienLeTan: false,
    loiTaiNhanVienLeTan: null,
    daTaiNhanVienLeTanLanDau: false,
    soThuTuYeuCauNhanVienLeTan: 0,
    nhanVienLeTanDaChon: null,
    dangTaiChiTietNhanVienLeTan: false,
    loiTaiChiTietNhanVienLeTan: null,
    daTaiChiTietNhanVienLeTanLanDau: false,
    soThuTuYeuCauChiTietNhanVienLeTan: 0,
    dangCapNhatTrangThaiNhanVienLeTan: false,
    loiCapNhatTrangThaiNhanVienLeTan: null,
    thongBaoCapNhatTrangThaiNhanVienLeTan: null,
    soThuTuCapNhatTrangThaiNhanVienLeTan: 0,
    dangThuHoiVaiTroNhanVienLeTan: false,
    loiThuHoiVaiTroNhanVienLeTan: null,
    thongBaoThuHoiVaiTroNhanVienLeTan: null,
    ketQuaThuHoiVaiTroNhanVienLeTan: null,
    soThuTuThuHoiVaiTroNhanVienLeTan: 0,
  }),

  actions: {
    /**
     * Tai list Account theo applied filter va mot trang server cu the.
     *
     * Dau vao: boLoc da ap dung tuy chon va trang; action nay khong nhan filter tu URL.
     * Cach hoat dong: goi service voi allow-list, validate envelope items/pagination,
     * chi commit response neu sequence la moi nhat de chan response cu ghi de state.
     * Ket qua: cap nhat list read-only, pagination, loading/error state.
     * Side effect: mot GET Backend; khong mutation Account, role/status hay idempotency key.
     * Concurrency Rule: request sau cung thay the request truoc; response cu bi bo qua.
     */
    async taiDanhSachTaiKhoan({ boLoc = this.boLoc, trang = this.phanTrang.current_page } = {}) {
      const boLocDaChuanHoa = taoBoLoc(boLoc)
      const trangYeuCau = Number.isInteger(trang) && trang >= 1 ? trang : 1
      const soThuTuHienTai = ++this.soThuTuYeuCau
      this.dangTai = true
      this.loiTaiDanhSach = null

      try {
        const duLieu = await taiTrangDanhSachCoHieuChinh({
          goiApi: taiDanhSachTaiKhoanApi,
          boLoc: boLocDaChuanHoa,
          trangYeuCau,
          laPhanHoiHopLe: laDanhSachTaiKhoanHopLe,
          taoLoiKhongHopLe: taoLoiPhanHoiKhongHopLe,
          conLaYeuCauHienTai: () => soThuTuHienTai === this.soThuTuYeuCau,
        })

        if (duLieu === null || soThuTuHienTai !== this.soThuTuYeuCau) {
          return null
        }

        this.danhSachTaiKhoan = duLieu.items
        this.boLoc = boLocDaChuanHoa
        this.phanTrang = { ...duLieu.pagination }
        return duLieu
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuYeuCau) {
          return null
        }

        this.loiTaiDanhSach = taoLoiAnToan(error)
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuYeuCau) {
          this.dangTai = false
          this.daTaiLanDau = true
        }
      }
    },

    /**
     * Tai danh sach Account co role MEMBER cho view Admin Hội viên.
     *
     * Dau vao: boLoc chi co search/status/per_page va trang server muon tai.
     * Cach hoat dong: goi service fixed-role, validate moi item co active MEMBER,
     * commit vao state rieng va dung sequence de response cu khong ghi de query moi.
     * Ket qua: danh sach, pagination, loading va loi cua Member view.
     * Side effect: GET read-only; khong thay doi state Account tong quat, Membership,
     * PT hay Member-self.
     * Security Rule: Backend loc role/branch; Frontend khong cho caller chon role.
     */
    async taiDanhSachHoiVien({
      boLoc = this.boLocHoiVien,
      trang = this.phanTrangHoiVien.current_page,
    } = {}) {
      const boLocDaChuanHoa = taoBoLocHoiVien(boLoc)
      const trangYeuCau = Number.isInteger(trang) && trang >= 1 ? trang : 1
      const soThuTuHienTai = ++this.soThuTuYeuCauHoiVien
      this.dangTaiHoiVien = true
      this.loiTaiHoiVien = null

      try {
        const duLieu = await taiTrangDanhSachCoHieuChinh({
          goiApi: taiDanhSachHoiVienApi,
          boLoc: boLocDaChuanHoa,
          trangYeuCau,
          laPhanHoiHopLe: laDanhSachHoiVienHopLe,
          taoLoiKhongHopLe: taoLoiPhanHoiHoiVienKhongHopLe,
          conLaYeuCauHienTai: () => soThuTuHienTai === this.soThuTuYeuCauHoiVien,
        })

        if (duLieu === null || soThuTuHienTai !== this.soThuTuYeuCauHoiVien) {
          return null
        }

        this.danhSachHoiVien = duLieu.items
        this.boLocHoiVien = boLocDaChuanHoa
        this.phanTrangHoiVien = { ...duLieu.pagination }
        return duLieu
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuYeuCauHoiVien) {
          return null
        }

        this.loiTaiHoiVien = taoLoiAnToan(
          error,
          'Không thể tải danh sách Hội viên. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuYeuCauHoiVien) {
          this.dangTaiHoiVien = false
          this.daTaiHoiVienLanDau = true
        }
      }
    },

    /**
     * Tai chi tiet Account theo id route va chong response cu ghi de detail moi.
     *
     * Dau vao: taiKhoanId positive integer tu route Admin.
     * Cach hoat dong: tang sequence cho moi lan tai, xoa detail cu neu doi account,
     * validate DTO allow-list toi thieu va chi commit response cua request moi nhat.
     * Ket qua: taiKhoanDaChon hoac loi query an toan; 404/403 khong giu du lieu cu.
     * Side effect: phat sinh GET /admin/accounts/{account}; khong logout, mutation hay
     * tu dong retry. Request 5xx/network giu detail cung id neu co de hien retry ro rang.
     * Security Rule: Backend la authority cho ADMIN/branch/safe DTO; FE khong ghep profile
     * Member/PT va khong suy quyen tu route id.
     */
    async taiChiTietTaiKhoan(taiKhoanId) {
      const idHopLe = laIdTaiKhoanHopLe(taiKhoanId)
      const id = idHopLe ? Number(taiKhoanId) : null
      const idHienTai = Number(this.taiKhoanDaChon?.id)
      const soThuTuHienTai = ++this.soThuTuYeuCauChiTiet
      this.dangTaiChiTiet = true
      this.loiTaiChiTiet = null

      if (!idHopLe || idHienTai !== id) {
        this.taiKhoanDaChon = null
      }

      if (!idHopLe) {
        const loi = taoLoiAnToan({
          httpStatus: 404,
          code: 'ACCOUNT_ID_INVALID',
          message: 'Không thể truy cập dữ liệu tài khoản này.',
          fieldErrors: {},
          isNetworkError: false,
        }, 'Không thể truy cập dữ liệu tài khoản này.')

        if (soThuTuHienTai === this.soThuTuYeuCauChiTiet) {
          this.loiTaiChiTiet = loi
          this.dangTaiChiTiet = false
          this.daTaiChiTietLanDau = true
        }

        return null
      }

      try {
        const phanHoi = await taiChiTietTaiKhoanApi(id)

        if (!laTaiKhoanChiTietHopLe(phanHoi)) {
          throw taoLoiChiTietKhongHopLe()
        }

        if (soThuTuHienTai !== this.soThuTuYeuCauChiTiet) {
          return null
        }

        this.taiKhoanDaChon = phanHoi.data
        return phanHoi.data
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuYeuCauChiTiet) {
          return null
        }

        if (error?.httpStatus === 403 || error?.httpStatus === 404) {
          this.taiKhoanDaChon = null
        }

        this.loiTaiChiTiet = taoLoiAnToan(
          error,
          'Không thể tải chi tiết tài khoản. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuYeuCauChiTiet) {
          this.dangTaiChiTiet = false
          this.daTaiChiTietLanDau = true
        }
      }
    },

    /**
     * Tai chi tiet Account theo huong Hội viên va kiem tra orientation an toan.
     *
     * Dau vao: taiKhoanId positive integer tu route Member view.
     * Cach hoat dong: goi GET Account detail, chi commit DTO co active MEMBER role;
     * account PT/RECEPTIONIST/ADMIN hoac role MEMBER da thu hoi bi coi la unavailable,
     * khong hien du lieu cua account do nhu Hội viên.
     * Ket qua: hoiVienDaChon hoac loi query generic theo state rieng cua Member.
     * Side effect: GET read-only; khong goi API profile, Membership, PT hay Member-self.
     * Security Rule: Backend la authority; role check o FE chi la fail-closed presentation.
     */
    async taiChiTietHoiVien(taiKhoanId) {
      const idHopLe = laIdTaiKhoanHopLe(taiKhoanId)
      const id = idHopLe ? Number(taiKhoanId) : null
      const idHienTai = Number(this.hoiVienDaChon?.id)
      const soThuTuHienTai = ++this.soThuTuYeuCauChiTietHoiVien
      this.dangTaiChiTietHoiVien = true
      this.loiTaiChiTietHoiVien = null

      if (!idHopLe || idHienTai !== id) {
        this.hoiVienDaChon = null
      }

      if (!idHopLe) {
        if (soThuTuHienTai === this.soThuTuYeuCauChiTietHoiVien) {
          this.loiTaiChiTietHoiVien = taoLoiAnToan({
            httpStatus: 404,
            code: 'MEMBER_ID_INVALID',
            message: 'Không thể truy cập dữ liệu này.',
            fieldErrors: {},
            isNetworkError: false,
          }, 'Không thể truy cập dữ liệu này.')
          this.dangTaiChiTietHoiVien = false
          this.daTaiChiTietHoiVienLanDau = true
        }

        return null
      }

      try {
        const phanHoi = await taiChiTietHoiVienApi(id)

        if (!laTaiKhoanChiTietHopLe(phanHoi) || !laTaiKhoanHoiVienHopLe(phanHoi.data)) {
          throw taoLoiChiTietHoiVienKhongHopLe()
        }

        if (soThuTuHienTai !== this.soThuTuYeuCauChiTietHoiVien) {
          return null
        }

        this.hoiVienDaChon = phanHoi.data
        return phanHoi.data
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuYeuCauChiTietHoiVien) {
          return null
        }

        if (error?.httpStatus === 403 || error?.httpStatus === 404) {
          this.hoiVienDaChon = null
        }

        this.loiTaiChiTietHoiVien = taoLoiAnToan(
          error,
          'Không thể tải chi tiết Hội viên. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuYeuCauChiTietHoiVien) {
          this.dangTaiChiTietHoiVien = false
          this.daTaiChiTietHoiVienLanDau = true
        }
      }
    },

    /**
     * Tai list Account co role RECEPTIONIST cho view Admin Nhan vien le tan.
     *
     * Dau vao: boLoc chi gom search/status/per_page va trang server.
     * Cach hoat dong: service luon gan role RECEPTIONIST; validate active assignment,
     * commit vao scoped state va dung sequence de response cu khong ghi de query moi.
     * Ket qua: danh sach, pagination, loading va loi cua view Receptionist.
     * Side effect: GET read-only; khong cham Account/Member state, profile hay shift.
     * Security Rule: active role tu Backend la dieu kien orientation; FE fail-closed.
     */
    async taiDanhSachNhanVienLeTan({
      boLoc = this.boLocNhanVienLeTan,
      trang = this.phanTrangNhanVienLeTan.current_page,
    } = {}) {
      const boLocDaChuanHoa = taoBoLocNhanVienLeTan(boLoc)
      const trangYeuCau = Number.isInteger(trang) && trang >= 1 ? trang : 1
      const soThuTuHienTai = ++this.soThuTuYeuCauNhanVienLeTan
      this.dangTaiNhanVienLeTan = true
      this.loiTaiNhanVienLeTan = null

      try {
        const duLieu = await taiTrangDanhSachCoHieuChinh({
          goiApi: taiDanhSachNhanVienLeTanApi,
          boLoc: boLocDaChuanHoa,
          trangYeuCau,
          laPhanHoiHopLe: laDanhSachNhanVienLeTanHopLe,
          taoLoiKhongHopLe: taoLoiPhanHoiNhanVienLeTanKhongHopLe,
          conLaYeuCauHienTai: () => soThuTuHienTai === this.soThuTuYeuCauNhanVienLeTan,
        })

        if (duLieu === null || soThuTuHienTai !== this.soThuTuYeuCauNhanVienLeTan) {
          return null
        }

        this.danhSachNhanVienLeTan = duLieu.items
        this.boLocNhanVienLeTan = boLocDaChuanHoa
        this.phanTrangNhanVienLeTan = { ...duLieu.pagination }
        return duLieu
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuYeuCauNhanVienLeTan) {
          return null
        }

        this.loiTaiNhanVienLeTan = taoLoiAnToan(
          error,
          'Không thể tải danh sách Nhân viên lễ tân. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuYeuCauNhanVienLeTan) {
          this.dangTaiNhanVienLeTan = false
          this.daTaiNhanVienLeTanLanDau = true
        }
      }
    },

    /**
     * Tai detail Account theo huong Nhan vien le tan va fail-closed theo active role.
     *
     * Dau vao: taiKhoanId positive integer tu named route.
     * Cach hoat dong: goi Account detail, chi commit DTO co active RECEPTIONIST role;
     * account khong co role active bi coi la out-of-scope va xoa selected state.
     * Ket qua: nhanVienLeTanDaChon hoac loi an toan trong scoped state.
     * Side effect: GET read-only; khong goi receptionist profile/shift API va khong logout.
     * Security Rule: route id khong cap quyen; Backend + active assignment la authority.
     */
    async taiChiTietNhanVienLeTan(taiKhoanId) {
      const idHopLe = laIdTaiKhoanHopLe(taiKhoanId)
      const id = idHopLe ? Number(taiKhoanId) : null
      const idHienTai = Number(this.nhanVienLeTanDaChon?.id)
      const soThuTuHienTai = ++this.soThuTuYeuCauChiTietNhanVienLeTan
      this.dangTaiChiTietNhanVienLeTan = true
      this.loiTaiChiTietNhanVienLeTan = null

      if (!idHopLe || idHienTai !== id) {
        this.nhanVienLeTanDaChon = null
      }

      if (!idHopLe) {
        if (soThuTuHienTai === this.soThuTuYeuCauChiTietNhanVienLeTan) {
          this.loiTaiChiTietNhanVienLeTan = taoLoiAnToan(
            taoLoiChiTietNhanVienLeTanKhongHopLe(),
            'Không thể truy cập dữ liệu này.',
          )
          this.dangTaiChiTietNhanVienLeTan = false
          this.daTaiChiTietNhanVienLeTanLanDau = true
        }

        return null
      }

      try {
        const phanHoi = await taiChiTietNhanVienLeTanApi(id)

        if (!laTaiKhoanChiTietHopLe(phanHoi)
          || !laTaiKhoanNhanVienLeTanHopLe(phanHoi.data)) {
          throw taoLoiChiTietNhanVienLeTanKhongHopLe()
        }

        if (soThuTuHienTai !== this.soThuTuYeuCauChiTietNhanVienLeTan) {
          return null
        }

        this.nhanVienLeTanDaChon = phanHoi.data
        return phanHoi.data
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuYeuCauChiTietNhanVienLeTan) {
          return null
        }

        if (error?.httpStatus === 403 || error?.httpStatus === 404
          || error?.code === 'RECEPTIONIST_ORIENTATION_INVALID') {
          this.nhanVienLeTanDaChon = null
        }

        this.loiTaiChiTietNhanVienLeTan = taoLoiAnToan(
          error,
          'Không thể tải chi tiết Nhân viên lễ tân. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuYeuCauChiTietNhanVienLeTan) {
          this.dangTaiChiTietNhanVienLeTan = false
          this.daTaiChiTietNhanVienLeTanLanDau = true
        }
      }
    },

    /**
     * Ap dung bo loc Receptionist va reset server page ve 1.
     *
     * Dau vao: search/status/per_page; role va branch neu co bi loai bo.
     * Cach hoat dong: tao snapshot allow-list, luu vao scoped state va GET fixed-role.
     * Ket qua: danh sach theo query moi hoac normalized error.
     * Side effect: khong thay doi Account/Member filter va khong mutation Backend.
     */
    async apDungBoLocNhanVienLeTan(boLoc) {
      this.boLocNhanVienLeTan = taoBoLocNhanVienLeTan(boLoc)
      return this.taiDanhSachNhanVienLeTan({ boLoc: this.boLocNhanVienLeTan, trang: 1 })
    },

    /**
     * Dat lai bo loc Receptionist ve default va tai lai trang dau.
     *
     * Dau vao: khong co.
     * Cach hoat dong: reset search/status/per_page scoped va GET fixed-role page 1.
     * Ket qua: query state sach va list authoritative.
     * Side effect: khong xoa Auth va khong cham Account/Member state.
     */
    async datLaiBoLocNhanVienLeTan() {
      this.boLocNhanVienLeTan = taoBoLocNhanVienLeTan()
      return this.taiDanhSachNhanVienLeTan({ boLoc: this.boLocNhanVienLeTan, trang: 1 })
    },

    /**
     * Chuyen trang Receptionist trong bien server pagination.
     *
     * Dau vao: trang moi trong [1, last_page].
     * Cach hoat dong: chan trang ngoai bien va tai lai bang filter scoped hien tai.
     * Ket qua: list/pagination moi, khong mat search/status.
     * Side effect: GET read-only; khong tu tinh pagination o client.
     */
    async chuyenTrangNhanVienLeTan(trangMoi) {
      if (!Number.isInteger(trangMoi)
        || trangMoi < 1
        || trangMoi > this.phanTrangNhanVienLeTan.last_page
        || trangMoi === this.phanTrangNhanVienLeTan.current_page) {
        return null
      }

      return this.taiDanhSachNhanVienLeTan({
        boLoc: this.boLocNhanVienLeTan,
        trang: trangMoi,
      })
    },

    /**
     * Tai lai list Receptionist voi filter/page dang xem sau loi tam thoi.
     *
     * Dau vao: khong co.
     * Cach hoat dong: retry thu cong GET fixed-role, khong retry 422/403/404 tu dong.
     * Ket qua: list moi hoac loi normalized.
     * Side effect: khong mutation va khong logout.
     */
    async thuLaiDanhSachNhanVienLeTan() {
      return this.taiDanhSachNhanVienLeTan({
        boLoc: this.boLocNhanVienLeTan,
        trang: this.phanTrangNhanVienLeTan.current_page,
      })
    },

    /**
     * Tai lai detail Receptionist voi id hien tai sau loi query tam thoi.
     *
     * Dau vao: id tuy chon, mac dinh selected Receptionist.
     * Cach hoat dong: lap lai GET Account detail va chay lai active-role orientation.
     * Ket qua: DTO authoritative hoac loi an toan.
     * Side effect: GET read-only; khong goi API profile/shift.
     */
    async thuLaiChiTietNhanVienLeTan(taiKhoanId = this.nhanVienLeTanDaChon?.id) {
      return this.taiChiTietNhanVienLeTan(taiKhoanId)
    },

    /**
     * Xoa rieng detail/mutation state Receptionist khi 403, scope exit hoac actor cleanup.
     *
     * Dau vao: khong co.
     * Cach hoat dong: tang sequence detail/mutation va reset selected/error/message.
     * Ket qua: Receptionist detail khong giu DTO actor cu.
     * Side effect: chi thay doi Pinia memory; khong xoa Account/Member state.
     */
    xoaChiTietNhanVienLeTan() {
      this.soThuTuYeuCauChiTietNhanVienLeTan += 1
      this.soThuTuCapNhatTrangThaiNhanVienLeTan += 1
      this.soThuTuThuHoiVaiTroNhanVienLeTan += 1
      this.nhanVienLeTanDaChon = null
      this.dangTaiChiTietNhanVienLeTan = false
      this.loiTaiChiTietNhanVienLeTan = null
      this.daTaiChiTietNhanVienLeTanLanDau = false
      this.dangCapNhatTrangThaiNhanVienLeTan = false
      this.loiCapNhatTrangThaiNhanVienLeTan = null
      this.thongBaoCapNhatTrangThaiNhanVienLeTan = null
      this.dangThuHoiVaiTroNhanVienLeTan = false
      this.loiThuHoiVaiTroNhanVienLeTan = null
      this.thongBaoThuHoiVaiTroNhanVienLeTan = null
      this.ketQuaThuHoiVaiTroNhanVienLeTan = null
    },

    /**
     * Xoa toan bo scoped Receptionist list/detail va vo hieu request dang bay.
     *
     * Dau vao: khong co.
     * Cach hoat dong: tang sequence list/detail/mutation, reset filter/pagination va data.
     * Ket qua: state Receptionist ve default, khong con DTO actor cu.
     * Side effect: chi reset memory; khong goi API, khong logout va khong cham Member.
     * Security Rule: Auth cleanup/authority loss khong de lai du lieu Receptionist.
     */
    xoaDuLieuNhanVienLeTan() {
      this.soThuTuYeuCauNhanVienLeTan += 1
      this.soThuTuYeuCauChiTietNhanVienLeTan += 1
      this.soThuTuCapNhatTrangThaiNhanVienLeTan += 1
      this.soThuTuThuHoiVaiTroNhanVienLeTan += 1
      this.danhSachNhanVienLeTan = []
      this.boLocNhanVienLeTan = taoBoLocNhanVienLeTan()
      this.phanTrangNhanVienLeTan = { ...PHAN_TRANG_NHAN_VIEN_LE_TAN_MAC_DINH }
      this.dangTaiNhanVienLeTan = false
      this.loiTaiNhanVienLeTan = null
      this.daTaiNhanVienLeTanLanDau = false
      this.xoaChiTietNhanVienLeTan()
    },

    /**
     * Ap dung bo loc read-only cua view Member va quay ve trang dau.
     *
     * Dau vao: search/status/per_page tu form Member; role neu co bi loai bo.
     * Cach hoat dong: tao snapshot filter chi gom field Backend cho phep, reset page
     * ve 1 va tai lai qua sequence Member rieng.
     * Ket qua: list Member theo filter moi hoac loi an toan.
     * Side effect: GET read-only; khong anh huong boLoc Account tong quat.
     */
    async apDungBoLocHoiVien(boLoc) {
      this.boLocHoiVien = taoBoLocHoiVien(boLoc)
      return this.taiDanhSachHoiVien({ boLoc: this.boLocHoiVien, trang: 1 })
    },

    /**
     * Dat lai bo loc Member ve gia tri mac dinh va tai lai trang dau.
     *
     * Dau vao: khong co.
     * Cach hoat dong: xoa search/status, giu per_page mac dinh va goi GET fixed-role.
     * Ket qua: Member list va pagination ve trang thai default Backend.
     * Side effect: GET read-only; khong xoa Auth va khong thay doi Account list chung.
     */
    async datLaiBoLocHoiVien() {
      this.boLocHoiVien = taoBoLocHoiVien()
      return this.taiDanhSachHoiVien({ boLoc: this.boLocHoiVien, trang: 1 })
    },

    /**
     * Chuyen trang Member trong bien server pagination.
     *
     * Dau vao: trang moi la so nguyen trong last_page hien tai.
     * Cach hoat dong: tu choi trang ngoai bien va tai lai voi applied Member filter.
     * Ket qua: pagination/list moi, khong mat search/status.
     * Side effect: GET read-only; khong sua du lieu Account hay role.
     */
    async chuyenTrangHoiVien(trangMoi) {
      if (!Number.isInteger(trangMoi)
        || trangMoi < 1
        || trangMoi > this.phanTrangHoiVien.last_page
        || trangMoi === this.phanTrangHoiVien.current_page) {
        return null
      }

      return this.taiDanhSachHoiVien({
        boLoc: this.boLocHoiVien,
        trang: trangMoi,
      })
    },

    /**
     * Thu lai Member list voi cung filter/page sau loi 5xx hoac network.
     *
     * Dau vao: khong co.
     * Cach hoat dong: chi goi GET fixed-role theo applied state, khong auto retry
     * va khong reset context dang xem.
     * Ket qua: list moi hoac loi an toan tren Member scope.
     * Side effect: GET read-only; 403 duoc page xu ly cleanup/navigation.
     */
    async thuLaiDanhSachHoiVien() {
      return this.taiDanhSachHoiVien({
        boLoc: this.boLocHoiVien,
        trang: this.phanTrangHoiVien.current_page,
      })
    },

    /**
     * Thu lai detail Member voi cung id hien tai sau loi query tam thoi.
     *
     * Dau vao: tuy chon id, mac dinh dung hoiVienDaChon hien tai.
     * Cach hoat dong: lap lai GET Account detail read-only va chay lai orientation check;
     * khong doi id, khong goi mutation va khong tu suy Membership.
     * Ket qua: DTO Member authoritative hoac loi generic.
     * Side effect: cap nhat scoped Member detail state va co the dieu huong 403 o page.
     */
    async thuLaiChiTietHoiVien(taiKhoanId = this.hoiVienDaChon?.id) {
      return this.taiChiTietHoiVien(taiKhoanId)
    },

    /**
     * Xoa rieng Member detail khi doi route hoac unmount ma van giu list/filter.
     *
     * Dau vao: khong co.
     * Cach hoat dong: tang sequence detail va reset selected/loading/error detail scoped.
     * Ket qua: response cua id cu khong the ghi de detail id moi sau navigation.
     * Side effect: chi thay doi Pinia memory; khong goi API va khong xoa Member list.
     * Security Rule: route moi khong tam thoi hien DTO cua route truoc.
     */
    xoaChiTietHoiVien() {
      this.soThuTuYeuCauChiTietHoiVien += 1
      this.hoiVienDaChon = null
      this.dangTaiChiTietHoiVien = false
      this.loiTaiChiTietHoiVien = null
      this.daTaiChiTietHoiVienLanDau = false
    },

    /**
     * Xoa rieng scoped Member list/detail khi 403, logout hoac role context thay doi.
     *
     * Dau vao: khong co.
     * Cach hoat dong: tang hai sequence de vo hieu response dang bay va reset moi state
     * Member, khong cham vao list/detail/mutation Account tong quat.
     * Ket qua: Member view khong con du lieu actor cu.
     * Side effect: chi thay doi Pinia memory; khong goi API va khong xoa Auth session.
     * Security Rule: khong giu DTO Member sau khi mat quyen hoac mat context.
     */
    xoaDuLieuHoiVien() {
      this.soThuTuYeuCauHoiVien += 1
      this.danhSachHoiVien = []
      this.boLocHoiVien = taoBoLocHoiVien()
      this.phanTrangHoiVien = { ...PHAN_TRANG_HOI_VIEN_MAC_DINH }
      this.dangTaiHoiVien = false
      this.loiTaiHoiVien = null
      this.daTaiHoiVienLanDau = false
      this.xoaChiTietHoiVien()
    },

    /**
     * Lam moi detail Account hien tai sau query loi, conflict hoac ket qua mutation chua ro.
     *
     * Dau vao: tuy chon taiKhoanId; mac dinh dung account dang duoc chon trong Store.
     * Cach hoat dong: chi goi lai GET detail voi cung id, khong gui PATCH va khong doi
     * filter list; page dung ket qua nay de cho phep thao tac tiep theo co can thiet.
     * Ket qua: tra ve DTO moi hoac null khi query that bai/route id khong hop le.
     * Side effect: phat sinh GET read-only va cap nhat query state detail.
     * Security Rule: refetch la buoc xac nhan authoritative, khong coi cached status la truth.
     */
    async lamMoiTaiKhoan(taiKhoanId = this.taiKhoanDaChon?.id) {
      return this.taiChiTietTaiKhoan(taiKhoanId)
    },

    /**
     * Cap nhat status Account sau khi page da confirm va sau do refetch authority.
     *
     * Dau vao: taiKhoanId positive integer va trangThai dung enum Backend.
     * Cach hoat dong: chan submit lap, khong optimistic update, goi PATCH body allow-list,
     * GET lai detail authoritative va tai lai list voi filter/page hien tai. 409 cung
     * refetch de hien trang thai moi; 5xx/network danh dau outcome unknown va refetch truoc
     * khi cho thao tac moi, khong blind retry.
     * Ket qua: DTO sau refetch, unchanged/daXacNhan hoac loi an toan theo status.
     * Side effect: PATCH status co the revoke session va audit o Backend; GET detail/list
     * dong bo lai cache FE. Frontend disabled chi la UX, khong thay the concurrency guard.
     * Security Rule: last-active-admin authority, authorization va transaction chi o Backend.
     */
    async capNhatTrangThaiTaiKhoan(taiKhoanId, trangThai) {
      const idHopLe = laIdTaiKhoanHopLe(taiKhoanId)

      if (this.dangCapNhatTrangThai || this.dangThayDoiVaiTro) {
        return null
      }

      if (!idHopLe || !CAC_TRANG_THAI_TAI_KHOAN.includes(trangThai)) {
        this.loiCapNhatTrangThai = taoLoiAnToan({
          httpStatus: 422,
          message: 'Trạng thái tài khoản không hợp lệ.',
          fieldErrors: { status: ['Trạng thái tài khoản không hợp lệ.'] },
          isNetworkError: false,
        }, 'Dữ liệu cập nhật trạng thái chưa hợp lệ.')
        return null
      }

      const taiKhoanHienTai = this.taiKhoanDaChon
      if (!taiKhoanHienTai || Number(taiKhoanHienTai.id) !== Number(taiKhoanId)) {
        this.loiCapNhatTrangThai = taoLoiAnToan({
          httpStatus: 404,
          message: 'Không thể truy cập dữ liệu tài khoản này.',
          fieldErrors: {},
          isNetworkError: false,
        }, 'Không thể truy cập dữ liệu tài khoản này.')
        return null
      }

      if (taiKhoanHienTai.status === trangThai) {
        this.loiCapNhatTrangThai = null
        this.thongBaoCapNhatTrangThai = null
        return { data: taiKhoanHienTai, unchanged: true }
      }

      const soThuTuHienTai = ++this.soThuTuCapNhatTrangThai
      this.dangCapNhatTrangThai = true
      this.loiCapNhatTrangThai = null
      this.thongBaoCapNhatTrangThai = null

      try {
        await capNhatTrangThaiTaiKhoanApi(Number(taiKhoanId), trangThai)

        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThai) {
          return null
        }

        const taiKhoanSauKhiTaiLai = await this.taiChiTietTaiKhoan(Number(taiKhoanId))

        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThai) {
          return null
        }

        if (!taiKhoanSauKhiTaiLai || Number(this.taiKhoanDaChon?.id) !== Number(taiKhoanId)) {
          this.loiCapNhatTrangThai = taoLoiKetQuaKhongXacDinh({
            httpStatus: null,
            isNetworkError: true,
          })
          return null
        }

        await this.taiDanhSachTaiKhoan({
          boLoc: this.boLoc,
          trang: this.phanTrang.current_page,
        })

        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThai) {
          return null
        }

        this.thongBaoCapNhatTrangThai = 'Đã cập nhật trạng thái tài khoản.'
        return { data: taiKhoanSauKhiTaiLai, changed: true }
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThai) {
          return null
        }

        if (error?.httpStatus === 409 || laLoiKhongBietKetQua(error)) {
          const taiKhoanSauKhiTaiLai = await this.taiChiTietTaiKhoan(Number(taiKhoanId))

          if (soThuTuHienTai !== this.soThuTuCapNhatTrangThai) {
            return null
          }

          if (taiKhoanSauKhiTaiLai) {
            await this.taiDanhSachTaiKhoan({
              boLoc: this.boLoc,
              trang: this.phanTrang.current_page,
            })

            if (soThuTuHienTai !== this.soThuTuCapNhatTrangThai) {
              return null
            }

            if (laLoiKhongBietKetQua(error)
              && taiKhoanSauKhiTaiLai.status === trangThai) {
              this.loiCapNhatTrangThai = null
              this.thongBaoCapNhatTrangThai = 'Trạng thái đã được Backend xác nhận.'
              return { data: taiKhoanSauKhiTaiLai, daXacNhan: true }
            }
          }

          this.loiCapNhatTrangThai = error?.httpStatus === 409
            ? taoLoiAnToan(error, 'Yêu cầu đang xung đột với trạng thái hiện tại.')
            : taoLoiKetQuaKhongXacDinh(error)
          return null
        }

        if (error?.httpStatus === 403 || error?.httpStatus === 404) {
          this.taiKhoanDaChon = null
        }

        this.loiCapNhatTrangThai = taoLoiAnToan(
          error,
          'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuCapNhatTrangThai) {
          this.dangCapNhatTrangThai = false
        }
      }
    },

    /**
     * Dieu phoi grant/regrant role sau khi page da confirm va refetch authority.
     *
     * Dau vao: taiKhoanId, maVaiTro enum va hanhDong cap/thuHoi tu cac action public.
     * Cach hoat dong: chan mutation song song, khong optimistic update, goi dung PUT
     * hoac DELETE, GET detail authoritative roi refetch list cung filter/page. Conflict,
     * timeout va 5xx duoc refetch; timeout khong blind retry va target cu hien unknown.
     * Ket qua: tra DTO detail cung role result, unchanged/no-op hoac loi an toan.
     * Side effect: Backend moi ghi assignment/audit/last-admin; Store chi dong bo memory.
     * Security Rule: Frontend khong cap quyen, tao profile PT, tinh last-admin hay audit.
     */
    async thayDoiVaiTro(taiKhoanId, maVaiTro, hanhDong) {
      const idHopLe = laIdTaiKhoanHopLe(taiKhoanId)
      const vaiTroHopLe = CAC_VAI_TRO_TAI_KHOAN.includes(maVaiTro)

      if (this.dangCapNhatTrangThai || this.dangThayDoiVaiTro) {
        return null
      }

      if (!idHopLe || !vaiTroHopLe || !['cap', 'thuHoi'].includes(hanhDong)) {
        this.loiThayDoiVaiTro = taoLoiAnToan({
          httpStatus: 422,
          code: 'INVALID_ACCOUNT_ROLE',
          message: 'Vai trò tài khoản không hợp lệ.',
          fieldErrors: { role: ['Vai trò tài khoản không hợp lệ.'] },
          isNetworkError: false,
        }, 'Dữ liệu vai trò chưa hợp lệ.')
        return null
      }

      const taiKhoanHienTai = this.taiKhoanDaChon
      if (!taiKhoanHienTai || Number(taiKhoanHienTai.id) !== Number(taiKhoanId)) {
        this.loiThayDoiVaiTro = taoLoiAnToan({
          httpStatus: 404,
          code: 'ACCOUNT_NOT_SELECTED',
          message: 'Không thể truy cập dữ liệu tài khoản này.',
          fieldErrors: {},
          isNetworkError: false,
        }, 'Không thể truy cập dữ liệu tài khoản này.')
        return null
      }

      const phanQuyenHienTai = layPhanQuyenTaiKhoan(taiKhoanHienTai, maVaiTro)
      if (hanhDong === 'cap' && phanQuyenHienTai?.active === true) {
        const ketQua = taoKetQuaVaiTroKhongThayDoi(taiKhoanHienTai, maVaiTro, hanhDong)
        this.loiThayDoiVaiTro = null
        this.ketQuaThayDoiVaiTro = ketQua
        this.thongBaoThayDoiVaiTro = taoThongBaoThayDoiVaiTro(ketQua.transition)
        return { data: taiKhoanHienTai, role: ketQua, unchanged: true }
      }

      if (hanhDong === 'thuHoi' && phanQuyenHienTai?.active === false) {
        const ketQua = taoKetQuaVaiTroKhongThayDoi(taiKhoanHienTai, maVaiTro, hanhDong)
        this.loiThayDoiVaiTro = null
        this.ketQuaThayDoiVaiTro = ketQua
        this.thongBaoThayDoiVaiTro = taoThongBaoThayDoiVaiTro(ketQua.transition)
        return { data: taiKhoanHienTai, role: ketQua, unchanged: true }
      }

      if (hanhDong === 'thuHoi' && phanQuyenHienTai === null) {
        this.loiThayDoiVaiTro = taoLoiAnToan({
          httpStatus: 404,
          code: 'ROLE_ASSIGNMENT_NOT_FOUND',
          message: 'Tài khoản chưa từng được cấp vai trò này.',
          fieldErrors: {},
          isNetworkError: false,
        }, 'Không thể thu hồi vai trò chưa từng được cấp.')
        return null
      }

      const soThuTuHienTai = ++this.soThuTuThayDoiVaiTro
      this.dangThayDoiVaiTro = true
      this.vaiTroDangXuLy = maVaiTro
      this.loiThayDoiVaiTro = null
      this.thongBaoThayDoiVaiTro = null
      this.ketQuaThayDoiVaiTro = null

      const goiMutation = hanhDong === 'cap' ? capVaiTroApi : thuHoiVaiTroApi

      try {
        const phanHoi = await goiMutation(Number(taiKhoanId), maVaiTro)

        if (soThuTuHienTai !== this.soThuTuThayDoiVaiTro) {
          return null
        }

        if (!laKetQuaVaiTroHopLe(phanHoi)) {
          throw taoLoiKetQuaVaiTroKhongHopLe()
        }

        const taiKhoanSauKhiTaiLai = await this.taiChiTietTaiKhoan(Number(taiKhoanId))

        if (soThuTuHienTai !== this.soThuTuThayDoiVaiTro) {
          return null
        }

        if (!taiKhoanSauKhiTaiLai || Number(this.taiKhoanDaChon?.id) !== Number(taiKhoanId)) {
          this.loiThayDoiVaiTro = [403, 404].includes(this.loiTaiChiTiet?.httpStatus)
            ? taoLoiAnToan(this.loiTaiChiTiet, 'Không thể truy cập dữ liệu tài khoản này.')
            : taoLoiKetQuaVaiTroKhongXacDinh()
          return null
        }

        await this.taiDanhSachTaiKhoan({
          boLoc: this.boLoc,
          trang: this.phanTrang.current_page,
        })

        if (soThuTuHienTai !== this.soThuTuThayDoiVaiTro) {
          return null
        }

        this.ketQuaThayDoiVaiTro = phanHoi.data
        this.thongBaoThayDoiVaiTro = taoThongBaoThayDoiVaiTro(phanHoi.data.transition)
        return { data: taiKhoanSauKhiTaiLai, role: phanHoi.data, changed: phanHoi.data.changed }
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuThayDoiVaiTro) {
          return null
        }

        if (error?.httpStatus === 409 || laLoiKhongBietKetQua(error)) {
          const taiKhoanSauKhiTaiLai = await this.taiChiTietTaiKhoan(Number(taiKhoanId))

          if (soThuTuHienTai !== this.soThuTuThayDoiVaiTro) {
            return null
          }

          if (taiKhoanSauKhiTaiLai) {
            await this.taiDanhSachTaiKhoan({
              boLoc: this.boLoc,
              trang: this.phanTrang.current_page,
            })

            if (soThuTuHienTai !== this.soThuTuThayDoiVaiTro) {
              return null
            }

            if (laLoiKhongBietKetQua(error)
              && laVaiTroDatTrangThai(taiKhoanSauKhiTaiLai, maVaiTro, hanhDong)) {
              const ketQua = {
                role: maVaiTro,
                account_id: Number(taiKhoanId),
                active: hanhDong === 'cap',
                changed: true,
                transition: 'UNCHANGED',
                outcome_confirmed_after_unknown: true,
              }
              this.loiThayDoiVaiTro = null
              this.ketQuaThayDoiVaiTro = ketQua
              this.thongBaoThayDoiVaiTro = 'Thay đổi vai trò đã được Backend xác nhận sau khi kết nối gián đoạn.'
              return { data: taiKhoanSauKhiTaiLai, role: ketQua, daXacNhan: true }
            }
          }

          this.loiThayDoiVaiTro = error?.httpStatus === 409
            ? taoLoiAnToan(error, 'Yêu cầu thay đổi vai trò đang xung đột với trạng thái hiện tại.')
            : taoLoiKetQuaVaiTroKhongXacDinh(error)
          return null
        }

        if (error?.httpStatus === 403 || error?.httpStatus === 404) {
          this.taiKhoanDaChon = null
        }

        this.loiThayDoiVaiTro = taoLoiAnToan(
          error,
          'Không thể thay đổi vai trò tài khoản. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuThayDoiVaiTro) {
          this.dangThayDoiVaiTro = false
          this.vaiTroDangXuLy = null
        }
      }
    },

    /**
     * Cap role moi hoac cap lai role revoked qua mot endpoint PUT duy nhat.
     *
     * Dau vao: Account detail da duoc tai, taiKhoanId va role enum.
     * Cach hoat dong: chuyen sang orchestration chung; active role la target-state no-op.
     * Ket qua: role result va detail authoritative, hoac loi conflict/profile an toan.
     * Side effect: co the ghi assignment/audit Backend; khong optimistic update.
     * Security Rule: Frontend khong tu tao trainer profile hay bypass ADMIN middleware.
     */
    async capVaiTro(taiKhoanId, maVaiTro) {
      return this.thayDoiVaiTro(taiKhoanId, maVaiTro, 'cap')
    },

    /**
     * Thu hoi role active qua UPDATE assignment va refetch detail/list.
     *
     * Dau vao: Account detail da duoc tai, taiKhoanId va role enum.
     * Cach hoat dong: chuyen sang orchestration chung; revoked role la target-state no-op.
     * Ket qua: REVOKED/UNCHANGED theo Backend hoac loi last-admin/conflict an toan.
     * Side effect: Backend ghi revoked timestamp/audit va co the lam mat authority ngay.
     * Security Rule: Frontend khong tu tinh so Admin hoat dong cuoi cung.
     */
    async thuHoiVaiTro(taiKhoanId, maVaiTro) {
      return this.thayDoiVaiTro(taiKhoanId, maVaiTro, 'thuHoi')
    },

    /**
     * Cap nhat status Account trong scoped Receptionist detail.
     *
     * Dau vao: id, status enum va selected Account co active RECEPTIONIST role.
     * Cach hoat dong: dung PATCH Account generic voi body `{ status }`, khong optimistic,
     * GET detail/list authoritative va xu ly 409/unknown theo cung semantics T04.
     * Ket qua: DTO sau refetch, unchanged/daXacNhan hoac loi normalized rieng scope.
     * Side effect: Backend co the revoke token/audit; Frontend chi dong bo state Receptionist.
     * Security Rule: khong tu dem Admin, khong gui Idempotency-Key, khong bypass Backend.
     */
    async capNhatTrangThaiNhanVienLeTan(taiKhoanId, trangThai) {
      const idHopLe = laIdTaiKhoanHopLe(taiKhoanId)

      if (this.dangCapNhatTrangThaiNhanVienLeTan || this.dangThuHoiVaiTroNhanVienLeTan) {
        return null
      }

      if (!idHopLe || !CAC_TRANG_THAI_TAI_KHOAN.includes(trangThai)) {
        this.loiCapNhatTrangThaiNhanVienLeTan = taoLoiAnToan({
          httpStatus: 422,
          message: 'Trạng thái tài khoản không hợp lệ.',
          fieldErrors: { status: ['Trạng thái tài khoản không hợp lệ.'] },
          isNetworkError: false,
        }, 'Dữ liệu cập nhật trạng thái chưa hợp lệ.')
        return null
      }

      const taiKhoanHienTai = this.nhanVienLeTanDaChon
      if (!taiKhoanHienTai
        || Number(taiKhoanHienTai.id) !== Number(taiKhoanId)
        || !laTaiKhoanCoVaiTroNhanVienLeTanDangHoatDong(taiKhoanHienTai)) {
        this.loiCapNhatTrangThaiNhanVienLeTan = taoLoiAnToan({
          httpStatus: 404,
          message: 'Không thể truy cập dữ liệu này.',
          fieldErrors: {},
          isNetworkError: false,
        }, 'Không thể truy cập dữ liệu này.')
        return null
      }

      if (taiKhoanHienTai.status === trangThai) {
        this.loiCapNhatTrangThaiNhanVienLeTan = null
        this.thongBaoCapNhatTrangThaiNhanVienLeTan = null
        return { data: taiKhoanHienTai, unchanged: true }
      }

      const soThuTuHienTai = ++this.soThuTuCapNhatTrangThaiNhanVienLeTan
      this.dangCapNhatTrangThaiNhanVienLeTan = true
      this.loiCapNhatTrangThaiNhanVienLeTan = null
      this.thongBaoCapNhatTrangThaiNhanVienLeTan = null

      try {
        await capNhatTrangThaiTaiKhoanApi(Number(taiKhoanId), trangThai)

        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThaiNhanVienLeTan) {
          return null
        }

        const taiKhoanSauKhiTaiLai = await this.taiChiTietNhanVienLeTan(Number(taiKhoanId))

        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThaiNhanVienLeTan) {
          return null
        }

        if (!taiKhoanSauKhiTaiLai
          || Number(this.nhanVienLeTanDaChon?.id) !== Number(taiKhoanId)) {
          this.loiCapNhatTrangThaiNhanVienLeTan = taoLoiKetQuaKhongXacDinh({
            httpStatus: null,
            isNetworkError: true,
          })
          return null
        }

        await this.taiDanhSachNhanVienLeTan({
          boLoc: this.boLocNhanVienLeTan,
          trang: this.phanTrangNhanVienLeTan.current_page,
        })

        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThaiNhanVienLeTan) {
          return null
        }

        this.thongBaoCapNhatTrangThaiNhanVienLeTan = 'Đã cập nhật trạng thái tài khoản.'
        return { data: taiKhoanSauKhiTaiLai, changed: true }
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuCapNhatTrangThaiNhanVienLeTan) {
          return null
        }

        if (error?.httpStatus === 409 || laLoiKhongBietKetQua(error)) {
          const taiKhoanSauKhiTaiLai = await this.taiChiTietNhanVienLeTan(Number(taiKhoanId))

          if (soThuTuHienTai !== this.soThuTuCapNhatTrangThaiNhanVienLeTan) {
            return null
          }

          if (taiKhoanSauKhiTaiLai) {
            await this.taiDanhSachNhanVienLeTan({
              boLoc: this.boLocNhanVienLeTan,
              trang: this.phanTrangNhanVienLeTan.current_page,
            })

            if (soThuTuHienTai !== this.soThuTuCapNhatTrangThaiNhanVienLeTan) {
              return null
            }

            if (laLoiKhongBietKetQua(error)
              && taiKhoanSauKhiTaiLai.status === trangThai) {
              this.loiCapNhatTrangThaiNhanVienLeTan = null
              this.thongBaoCapNhatTrangThaiNhanVienLeTan = 'Trạng thái đã được Backend xác nhận.'
              return { data: taiKhoanSauKhiTaiLai, daXacNhan: true }
            }
          }

          this.loiCapNhatTrangThaiNhanVienLeTan = error?.httpStatus === 409
            ? taoLoiAnToan(error, 'Yêu cầu đang xung đột với trạng thái hiện tại.')
            : taoLoiKetQuaKhongXacDinh(error)
          return null
        }

        if (error?.httpStatus === 403 || error?.httpStatus === 404) {
          this.nhanVienLeTanDaChon = null
        }

        this.loiCapNhatTrangThaiNhanVienLeTan = taoLoiAnToan(
          error,
          'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuCapNhatTrangThaiNhanVienLeTan) {
          this.dangCapNhatTrangThaiNhanVienLeTan = false
        }
      }
    },

    /**
     * Thu hoi duy nhat role RECEPTIONIST tren detail scoped.
     *
     * Dau vao: taiKhoanId; role khong nhan tu caller va luon la RECEPTIONIST.
     * Cach hoat dong: DELETE role khong body/header, refetch detail/list authoritative,
     * khong optimistic va khong co nut grant/regrant trong Receptionist view.
     * Ket qua: role result, scopeExited/daXacNhan hoac loi normalized rieng scope.
     * Side effect: Backend ghi revoke/audit va co the lam mat authority; UI chi dong bo.
     * Security Rule: khong client-count Admin, khong idempotency key, khong fake profile.
     */
    async thuHoiVaiTroLeTan(taiKhoanId) {
      const idHopLe = laIdTaiKhoanHopLe(taiKhoanId)

      if (this.dangThuHoiVaiTroNhanVienLeTan || this.dangCapNhatTrangThaiNhanVienLeTan) {
        return null
      }

      const taiKhoanHienTai = this.nhanVienLeTanDaChon
      if (!idHopLe
        || !taiKhoanHienTai
        || Number(taiKhoanHienTai.id) !== Number(taiKhoanId)
        || !laTaiKhoanCoVaiTroNhanVienLeTanDangHoatDong(taiKhoanHienTai)) {
        this.loiThuHoiVaiTroNhanVienLeTan = taoLoiAnToan({
          httpStatus: 404,
          code: 'RECEPTIONIST_ASSIGNMENT_NOT_FOUND',
          message: 'Không thể truy cập dữ liệu này.',
          fieldErrors: {},
          isNetworkError: false,
        }, 'Không thể truy cập dữ liệu này.')
        return null
      }

      const soThuTuHienTai = ++this.soThuTuThuHoiVaiTroNhanVienLeTan
      this.dangThuHoiVaiTroNhanVienLeTan = true
      this.loiThuHoiVaiTroNhanVienLeTan = null
      this.thongBaoThuHoiVaiTroNhanVienLeTan = null
      this.ketQuaThuHoiVaiTroNhanVienLeTan = null

      try {
        const phanHoi = await thuHoiVaiTroApi(Number(taiKhoanId), 'RECEPTIONIST')

        if (soThuTuHienTai !== this.soThuTuThuHoiVaiTroNhanVienLeTan) {
          return null
        }

        if (!laKetQuaVaiTroHopLe(phanHoi) || phanHoi.data.role !== 'RECEPTIONIST') {
          throw taoLoiKetQuaVaiTroKhongHopLe()
        }

        const taiKhoanSauKhiTaiLai = await this.taiChiTietNhanVienLeTan(Number(taiKhoanId))

        if (soThuTuHienTai !== this.soThuTuThuHoiVaiTroNhanVienLeTan) {
          return null
        }

        if (taiKhoanSauKhiTaiLai) {
          this.loiThuHoiVaiTroNhanVienLeTan = taoLoiKetQuaVaiTroKhongXacDinh()
          return null
        }

        if (this.loiTaiChiTietNhanVienLeTan?.httpStatus === 403) {
          this.loiThuHoiVaiTroNhanVienLeTan = taoLoiAnToan(
            this.loiTaiChiTietNhanVienLeTan,
            'Không thể truy cập dữ liệu này.',
          )
          return null
        }

        await this.taiDanhSachNhanVienLeTan({
          boLoc: this.boLocNhanVienLeTan,
          trang: this.phanTrangNhanVienLeTan.current_page,
        })

        if (soThuTuHienTai !== this.soThuTuThuHoiVaiTroNhanVienLeTan) {
          return null
        }

        this.nhanVienLeTanDaChon = null
        this.loiTaiChiTietNhanVienLeTan = null
        this.ketQuaThuHoiVaiTroNhanVienLeTan = phanHoi.data
        this.thongBaoThuHoiVaiTroNhanVienLeTan = 'Đã thu hồi vai trò Nhân viên lễ tân.'
        return {
          data: null,
          role: phanHoi.data,
          changed: phanHoi.data.changed,
          scopeExited: true,
        }
      } catch (error) {
        if (soThuTuHienTai !== this.soThuTuThuHoiVaiTroNhanVienLeTan) {
          return null
        }

        if (error?.httpStatus === 409 || laLoiKhongBietKetQua(error)) {
          const taiKhoanSauKhiTaiLai = await this.taiChiTietNhanVienLeTan(Number(taiKhoanId))

          if (soThuTuHienTai !== this.soThuTuThuHoiVaiTroNhanVienLeTan) {
            return null
          }

          if (!taiKhoanSauKhiTaiLai
            && this.loiTaiChiTietNhanVienLeTan?.httpStatus !== 403) {
            await this.taiDanhSachNhanVienLeTan({
              boLoc: this.boLocNhanVienLeTan,
              trang: this.phanTrangNhanVienLeTan.current_page,
            })

            if (soThuTuHienTai !== this.soThuTuThuHoiVaiTroNhanVienLeTan) {
              return null
            }

            this.nhanVienLeTanDaChon = null
            this.loiThuHoiVaiTroNhanVienLeTan = null
            this.ketQuaThuHoiVaiTroNhanVienLeTan = {
              account_id: Number(taiKhoanId),
              role: 'RECEPTIONIST',
              active: false,
              changed: true,
              transition: 'REVOKED',
              outcome_confirmed_after_unknown: true,
            }
            this.thongBaoThuHoiVaiTroNhanVienLeTan = 'Vai trò đã được Backend xác nhận sau khi kết nối gián đoạn.'
            return {
              data: null,
              role: this.ketQuaThuHoiVaiTroNhanVienLeTan,
              daXacNhan: true,
              scopeExited: true,
            }
          }

          if (taiKhoanSauKhiTaiLai) {
            await this.taiDanhSachNhanVienLeTan({
              boLoc: this.boLocNhanVienLeTan,
              trang: this.phanTrangNhanVienLeTan.current_page,
            })

            if (soThuTuHienTai !== this.soThuTuThuHoiVaiTroNhanVienLeTan) {
              return null
            }
          }

          this.loiThuHoiVaiTroNhanVienLeTan = error?.httpStatus === 409
            ? taoLoiAnToan(error, 'Yêu cầu thu hồi vai trò đang xung đột với trạng thái hiện tại.')
            : taoLoiKetQuaVaiTroKhongXacDinh(error)
          return null
        }

        if (error?.httpStatus === 403 || error?.httpStatus === 404) {
          this.nhanVienLeTanDaChon = null
        }

        this.loiThuHoiVaiTroNhanVienLeTan = taoLoiAnToan(
          error,
          'Không thể thu hồi vai trò Nhân viên lễ tân. Vui lòng thử lại sau.',
        )
        return null
      } finally {
        if (soThuTuHienTai === this.soThuTuThuHoiVaiTroNhanVienLeTan) {
          this.dangThuHoiVaiTroNhanVienLeTan = false
        }
      }
    },

    /**
     * Ap dung bo loc Account moi va quay ve trang dau.
     *
     * Dau vao: search/status/role/per_page tu form da duoc page gioi han theo Backend.
     * Cach hoat dong: thay applied filter trong store, reset page ve 1 va tai lai list.
     * Ket qua: list phan anh dung filter moi, khong giu trang cu co the da vuot ket qua.
     * Side effect: phat sinh GET read-only; khong thay doi account hay role tren Backend.
     * Business Rule: filter va pagination la mot query state; filter moi luon page 1.
     */
    async apDungBoLocTaiKhoan(boLoc) {
      this.boLoc = taoBoLoc(boLoc)
      return this.taiDanhSachTaiKhoan({ boLoc: this.boLoc, trang: 1 })
    },

    /**
     * Dat lai filter Account ve default Backend va tai trang dau.
     *
     * Dau vao: khong co.
     * Cach hoat dong: xoa search/status/role, giu per_page mac dinh va goi lai list page 1.
     * Ket qua: applied filter sach va pagination duoc dong bo voi response moi.
     * Side effect: phat sinh GET read-only; khong xoa phien Auth va khong mutation.
     */
    async datLaiBoLocTaiKhoan() {
      this.boLoc = taoBoLoc()
      return this.taiDanhSachTaiKhoan({ boLoc: this.boLoc, trang: 1 })
    },

    /**
     * Chuyen trang Account trong bien server pagination Backend tra ve.
     *
     * Dau vao: trang moi la so nguyen trong current last_page.
     * Cach hoat dong: chan trang ngoai bien va tai lai bang applied filter hien tai.
     * Ket qua: page doi nhung search/status/role khong bi mat.
     * Side effect: phat sinh GET read-only; khong tu chinh sua total/last_page o client.
     */
    async chuyenTrangTaiKhoan(trangMoi) {
      if (!Number.isInteger(trangMoi)
        || trangMoi < 1
        || trangMoi > this.phanTrang.last_page
        || trangMoi === this.phanTrang.current_page) {
        return null
      }

      return this.taiDanhSachTaiKhoan({ boLoc: this.boLoc, trang: trangMoi })
    },

    /**
     * Thu lai request list Account bang dung applied filter va trang hien tai.
     *
     * Dau vao: khong co.
     * Cach hoat dong: khong reset query state, chi goi lai GET voi current page/filter.
     * Ket qua: du lieu moi neu Backend san sang, hoac loi an toan neu van that bai.
     * Side effect: phat sinh GET read-only; khong auto retry va khong logout.
     */
    async thuLaiTaiDanhSachTaiKhoan() {
      return this.taiDanhSachTaiKhoan({
        boLoc: this.boLoc,
        trang: this.phanTrang.current_page,
      })
    },

    /**
     * Xoa Account detail dang duoc chon khi Backend tu choi quyen truy cap.
     *
     * Dau vao: khong co.
     * Cach hoat dong: vo hieu response detail dang bay va xoa du lieu detail/error
     * cua actor hien tai, khong logout va khong thay doi list Backend.
     * Ket qua: UI khong con hien detail da mat quyen.
     * Side effect: chi thay doi state Pinia trong memory; khong goi API.
     * Security Rule: 403 khong duoc giu selected Account hoac tu dong suy ra quyen moi.
     */
    xoaTaiKhoanDaChon() {
      this.soThuTuYeuCauChiTiet += 1
      this.soThuTuCapNhatTrangThai += 1
      this.soThuTuThayDoiVaiTro += 1
      this.taiKhoanDaChon = null
      this.dangTaiChiTiet = false
      this.loiTaiChiTiet = null
      this.daTaiChiTietLanDau = false
      this.dangCapNhatTrangThai = false
      this.loiCapNhatTrangThai = null
      this.thongBaoCapNhatTrangThai = null
      this.dangThayDoiVaiTro = false
      this.vaiTroDangXuLy = null
      this.loiThayDoiVaiTro = null
      this.thongBaoThayDoiVaiTro = null
      this.ketQuaThayDoiVaiTro = null
    },

    /**
     * Reset toan bo Account list state khi Auth cleanup.
     *
     * Dau vao: khong co.
     * Cach hoat dong: tang sequence, reset du lieu memory va vo hieu response dang bay.
     * Ket qua: khong con account list, filter hay error cua actor cu.
     * Side effect: chi thay doi Pinia memory; khong persist va khong goi API.
     */
    xoaDuLieu() {
      this.soThuTuYeuCau += 1
      this.soThuTuYeuCauChiTiet += 1
      this.soThuTuCapNhatTrangThai += 1
      this.soThuTuThayDoiVaiTro += 1
      this.danhSachTaiKhoan = []
      this.boLoc = taoBoLoc()
      this.phanTrang = { ...PHAN_TRANG_MAC_DINH }
      this.dangTai = false
      this.loiTaiDanhSach = null
      this.daTaiLanDau = false
      this.taiKhoanDaChon = null
      this.dangTaiChiTiet = false
      this.loiTaiChiTiet = null
      this.daTaiChiTietLanDau = false
      this.dangCapNhatTrangThai = false
      this.loiCapNhatTrangThai = null
      this.thongBaoCapNhatTrangThai = null
      this.dangThayDoiVaiTro = false
      this.vaiTroDangXuLy = null
      this.loiThayDoiVaiTro = null
      this.thongBaoThayDoiVaiTro = null
      this.ketQuaThayDoiVaiTro = null
      this.xoaDuLieuHoiVien()
      this.xoaDuLieuNhanVienLeTan()
    },
  },
})
