import ketNoiApi from './api.js'

export const CAC_TRANG_THAI_TAI_KHOAN = Object.freeze([
  'HOAT_DONG',
  'BI_KHOA',
  'NGUNG_HOAT_DONG',
])

export const CAC_VAI_TRO_TAI_KHOAN = Object.freeze([
  'MEMBER',
  'PT',
  'RECEPTIONIST',
  'ADMIN',
])

export const CAC_CHUYEN_DOI_VAI_TRO = Object.freeze([
  'GRANTED',
  'REGRANTED',
  'REVOKED',
  'UNCHANGED',
])

function taoLoiTaiKhoanDauVao(thongBao, fieldErrors = {}, httpStatus = 422) {
  return {
    httpStatus,
    code: null,
    message: thongBao,
    fieldErrors,
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

/**
 * Kiem tra account id truoc khi chen vao path Admin.
 *
 * Dau vao: gia tri id tu route hoac hanh dong detail/status.
 * Cach hoat dong: chi chap nhan chuoi/so nguyen duong nam trong safe integer,
 * tu choi NaN, so am, so thap phan va object de khong tao request sai path.
 * Ket qua: boolean cho page/store quyet dinh co duoc goi API hay hien unavailable.
 * Side effect: khong goi API, khong chuyen huong va khong thay doi state.
 * Security Rule: route param khong duoc tro thanh authority va khong duoc chen gia tri
 * khong hop le vao URL Backend.
 */
export function laIdTaiKhoanHopLe(taiKhoanId) {
  if (typeof taiKhoanId === 'number') {
    return Number.isSafeInteger(taiKhoanId) && taiKhoanId > 0
  }

  return typeof taiKhoanId === 'string'
    && /^[1-9]\d*$/.test(taiKhoanId.trim())
    && Number.isSafeInteger(Number(taiKhoanId.trim()))
}

function layIdTaiKhoanAnToan(taiKhoanId) {
  if (!laIdTaiKhoanHopLe(taiKhoanId)) {
    throw taoLoiTaiKhoanDauVao(
      'Không thể truy cập dữ liệu tài khoản này.',
      {},
      404,
    )
  }

  return String(taiKhoanId).trim()
}

function taoLoiBoLoc(truong, thongBao) {
  return {
    httpStatus: 422,
    code: 'INVALID_ACCOUNT_FILTER',
    message: thongBao,
    fieldErrors: { [truong]: [thongBao] },
    retryAfter: null,
    isNetworkError: false,
    originalRequestId: null,
  }
}

function layChuoiBoLoc(boLoc, truong) {
  const giaTri = boLoc?.[truong]

  if (giaTri === undefined || giaTri === null) {
    return ''
  }

  return typeof giaTri === 'string' ? giaTri.trim() : String(giaTri)
}

function laySoBoLoc(boLoc, truong) {
  const giaTri = boLoc?.[truong]

  if (giaTri === undefined || giaTri === null || giaTri === '') {
    return null
  }

  return Number.isInteger(giaTri) ? giaTri : Number(giaTri)
}

/**
 * Tai danh sach Account Admin theo dung hop dong GET /admin/accounts cua Backend.
 *
 * Dau vao: boLoc chi gom search, status, role, page va per_page; status/role dung enum
 * Backend, page bat dau tu 1 va per_page trong khoang 1..100.
 * Cach hoat dong: validate UX nhung truong allow-list, tao query params khong co branch,
 * actor, quyen, sort hay du lieu authority client, sau do goi Axios client duy nhat.
 * Ket qua: giu nguyen envelope { data: { items, pagination } } tu Backend.
 * Side effect: phat sinh mot HTTP GET read-only; khong mutation, transaction hay idempotency key.
 * Security Rule: Backend van la authority cho authorization, filtering va DTO an toan.
 */
export async function taiDanhSachTaiKhoan(boLoc = {}) {
  const params = {}
  const search = layChuoiBoLoc(boLoc, 'search')
  const status = layChuoiBoLoc(boLoc, 'status')
  const role = layChuoiBoLoc(boLoc, 'role')
  const page = laySoBoLoc(boLoc, 'page')
  const perPage = laySoBoLoc(boLoc, 'per_page')

  if (search.length > 150) {
    throw taoLoiBoLoc('search', 'Từ khóa không được dài quá 150 ký tự.')
  }

  if (status !== '' && !CAC_TRANG_THAI_TAI_KHOAN.includes(status)) {
    throw taoLoiBoLoc('status', 'Trạng thái tài khoản không hợp lệ.')
  }

  if (role !== '' && !CAC_VAI_TRO_TAI_KHOAN.includes(role)) {
    throw taoLoiBoLoc('role', 'Vai trò tài khoản không hợp lệ.')
  }

  if (page !== null && (!Number.isInteger(page) || page < 1)) {
    throw taoLoiBoLoc('page', 'Trang phải là một số nguyên từ 1 trở lên.')
  }

  if (perPage !== null && (!Number.isInteger(perPage) || perPage < 1 || perPage > 100)) {
    throw taoLoiBoLoc('per_page', 'Số dòng mỗi trang phải nằm trong khoảng từ 1 đến 100.')
  }

  if (search !== '') params.search = search
  if (status !== '') params.status = status
  if (role !== '') params.role = role
  if (page !== null) params.page = page
  if (perPage !== null) params.per_page = perPage

  const phanHoi = await ketNoiApi.get('/admin/accounts', { params })

  return phanHoi.data
}

/**
 * Tai danh sach Account dang co role MEMBER cho man hinh Admin Hội viên.
 *
 * Dau vao: boLoc chi gom search, status, page va per_page tu bo loc Member.
 * Cach hoat dong: dung chung contract Account list, bo qua moi role do caller co the
 * truyen vao va gan co dinh `role=MEMBER` o boundary service; khong them branch hoac
 * truong authority khac vao query.
 * Ket qua: tra envelope Account list `{ data: { items, pagination } }` tu Backend.
 * Side effect: phat sinh GET read-only; khong tao Account, profile, Membership hay role.
 * Security Rule: Backend van xac minh ADMIN/branch; role MEMBER chi la invariant cua view.
 */
export async function taiDanhSachHoiVien(boLoc = {}) {
  return taiDanhSachTaiKhoan({
    search: boLoc?.search,
    status: boLoc?.status,
    page: boLoc?.page,
    per_page: boLoc?.per_page,
    role: 'MEMBER',
  })
}

/**
 * Tai danh sach Account dang co role PT cho man hinh Admin Huan luyen vien.
 *
 * Dau vao: boLoc chi gom search, status, page va per_page tu bo loc PT.
 * Cach hoat dong: dung chung contract Account list, bo qua role/branch va moi truong
 * authority do caller truyen vao, sau do gan co dinh `role=PT` o boundary service.
 * Ket qua: tra envelope Account list `{ data: { items, pagination } }` tu Backend.
 * Side effect: phat sinh GET read-only; khong tao profile PT, phan cong hay loi moi.
 * Security Rule: Backend van xac minh ADMIN/branch; role PT chi la invariant cua view.
 */
export async function taiDanhSachHuanLuyenVien(boLoc = {}) {
  return taiDanhSachTaiKhoan({
    search: boLoc?.search,
    status: boLoc?.status,
    page: boLoc?.page,
    per_page: boLoc?.per_page,
    role: 'PT',
  })
}

/**
 * Tai danh sach Account dang co role RECEPTIONIST cho man hinh Admin Nhan vien le tan.
 *
 * Dau vao: boLoc chi gom search, status, page va per_page tu bo loc Receptionist.
 * Cach hoat dong: dung chung contract Account list, loai moi role do caller truyen vao
 * va gan co dinh `role=RECEPTIONIST` o boundary service; khong them branch/profile hay
 * truong authority khac vao query.
 * Ket qua: tra envelope Account list `{ data: { items, pagination } }` tu Backend.
 * Side effect: phat sinh GET read-only; khong tao profile, ca lam hay quay/ban tiep nhan.
 * Security Rule: Backend van xac minh ADMIN/branch va active role assignment; FE khong
 * coi route id hay filter client la authority.
 */
export async function taiDanhSachNhanVienLeTan(boLoc = {}) {
  return taiDanhSachTaiKhoan({
    search: boLoc?.search,
    status: boLoc?.status,
    page: boLoc?.page,
    per_page: boLoc?.per_page,
    role: 'RECEPTIONIST',
  })
}

/**
 * Tai chi tiet Account Admin theo dung DTO an toan cua GET /admin/accounts/{account}.
 *
 * Dau vao: taiKhoanId la positive integer da duoc kiem tra truoc khi chen vao path.
 * Cach hoat dong: goi single Axios client voi named path Backend, khong them branch,
 * role, token, audit hay truong du lieu ngoai contract.
 * Ket qua: tra ve nguyen envelope `{ data: account }` de Store validate truoc khi commit.
 * Side effect: phat sinh HTTP GET read-only; khong mutation, retry tu dong hay idempotency.
 * Security Rule: Backend van kiem tra ADMIN, branch scope va safe DTO; FE khong tu authorize.
 */
export async function taiChiTietTaiKhoan(taiKhoanId) {
  const id = layIdTaiKhoanAnToan(taiKhoanId)
  const phanHoi = await ketNoiApi.get(`/admin/accounts/${id}`)

  return phanHoi.data
}

/**
 * Tai chi tiet Account cho man hinh Member-oriented cua Admin.
 *
 * Dau vao: taiKhoanId positive integer tu named route.
 * Cach hoat dong: dung chung GET Account detail, khong chen role vao path hay query;
 * Store se xac minh response co active MEMBER role truoc khi hien thi nhu Hội viên.
 * Ket qua: tra envelope `{ data: account }` voi DTO an toan cua Backend.
 * Side effect: phat sinh GET read-only; khong goi Membership/PT/Member-self API.
 * Security Rule: Backend la authority cho account scope; FE khong tin route id hay cache.
 */
export async function taiChiTietHoiVien(taiKhoanId) {
  return taiChiTietTaiKhoan(taiKhoanId)
}

/**
 * Tai chi tiet Account cho man hinh Huấn luyện viên-oriented cua Admin.
 *
 * Dau vao: taiKhoanId positive integer tu named route.
 * Cach hoat dong: uy quyen cho Account detail wrapper dung chung, nen chi phat sinh
 * GET `/admin/accounts/{id}` sau safe-ID validation va khong goi API ho so PT rieng.
 * Ket qua: tra envelope `{ data: account }` de Store xac minh active PT role va DTO.
 * Side effect: phat sinh GET read-only; khong tao/chinh sua ho so, phan cong hay loi moi.
 * Security Rule: Backend la authority cho ADMIN/branch; FE khong tu cap quyen tu route id.
 */
export async function taiChiTietHuanLuyenVien(taiKhoanId) {
  return taiChiTietTaiKhoan(taiKhoanId)
}

/**
 * Tai chi tiet Account cho man hinh Nhan vien le tan-oriented cua Admin.
 *
 * Dau vao: taiKhoanId positive integer tu named route.
 * Cach hoat dong: dung chung GET Account detail, khong chen role vao path/query;
 * Store se fail-closed neu DTO khong co active RECEPTIONIST role.
 * Ket qua: tra envelope `{ data: account }` voi DTO Account an toan cua Backend.
 * Side effect: phat sinh GET read-only; khong goi receptionist profile/shift API.
 * Security Rule: Backend la authority cho scope; FE chi xac nhan orientation truoc render.
 */
export async function taiChiTietNhanVienLeTan(taiKhoanId) {
  return taiChiTietTaiKhoan(taiKhoanId)
}

/**
 * Gui yeu cau cap nhat status Account theo contract PATCH Backend.
 *
 * Dau vao: taiKhoanId positive integer va trangThai dung enum Backend.
 * Cach hoat dong: validate allow-list, chi gui body `{ status }`, khong gui role,
 * branch, actor, reason, current_status hay Idempotency-Key; ket qua tu Backend khong
 * duoc coi la authoritative thay cho GET detail refetch cua Store.
 * Ket qua: tra ve envelope `{ data: account }` do Backend phan hoi.
 * Side effect: phat sinh mutation Account; Backend xu ly transaction, session revoke,
 * audit va last-active-admin guard.
 * Security Rule: Frontend chi dieu phoi UX; authorization va concurrency authority o Backend.
 */
export async function capNhatTrangThaiTaiKhoan(taiKhoanId, trangThai) {
  const id = layIdTaiKhoanAnToan(taiKhoanId)

  if (!CAC_TRANG_THAI_TAI_KHOAN.includes(trangThai)) {
    const thongBao = 'Trạng thái tài khoản không hợp lệ.'
    throw taoLoiTaiKhoanDauVao(thongBao, { status: [thongBao] })
  }

  const phanHoi = await ketNoiApi.patch(`/admin/accounts/${id}/status`, {
    status: trangThai,
  })

  return phanHoi.data
}

/**
 * Kiem tra role path segment truoc khi goi Admin role endpoint.
 *
 * Dau vao: maVaiTro do Store/UI truyen vao.
 * Cach hoat dong: chi chap nhan exact enum Backend, khong trim/upper-case am tham.
 * Ket qua: tra role hop le hoac loi validation an toan truoc request.
 * Side effect: khong goi API va khong thay doi state.
 * Security Rule: khong cho client chen FREE/Premium/role tu y vao path mutation.
 */
function layMaVaiTroAnToan(maVaiTro) {
  if (typeof maVaiTro !== 'string' || !CAC_VAI_TRO_TAI_KHOAN.includes(maVaiTro)) {
    const thongBao = 'Vai trò tài khoản không hợp lệ.'
    throw taoLoiTaiKhoanDauVao(thongBao, { role: [thongBao] })
  }

  return maVaiTro
}

/**
 * Cap vai tro moi hoac cap lai assignment da thu hoi cho Account Admin.
 *
 * Dau vao: taiKhoanId positive integer va maVaiTro dung role enum Backend.
 * Cach hoat dong: validate allow-list, goi PUT khong body va khong header idempotency;
 * Backend tu quyet dinh GRANTED, REGRANTED hoac UNCHANGED tren cung assignment row.
 * Ket qua: tra ve envelope `{ data: roleResult }` de Store refetch Account detail.
 * Side effect: phat sinh mutation role; transaction, audit, profile prerequisite,
 * concurrency va last-admin authority nam o Backend.
 * Security Rule: Frontend khong tu cap quyen va khong tao trainer profile thay Backend.
 */
export async function capVaiTro(taiKhoanId, maVaiTro) {
  const id = layIdTaiKhoanAnToan(taiKhoanId)
  const vaiTro = layMaVaiTroAnToan(maVaiTro)
  const phanHoi = await ketNoiApi.put(`/admin/accounts/${id}/roles/${vaiTro}`)

  return phanHoi.data
}

/**
 * Thu hoi role active cua Account Admin theo assignment history.
 *
 * Dau vao: taiKhoanId positive integer va maVaiTro dung role enum Backend.
 * Cach hoat dong: validate allow-list, goi DELETE khong body va khong header idempotency;
 * Backend update `thu_hoi_luc`, tra REVOKED hoac UNCHANGED va ghi audit trong transaction.
 * Ket qua: tra ve envelope `{ data: roleResult }` de Store refetch detail/list authority.
 * Side effect: phat sinh mutation role; last-active-admin guard va token authority o Backend.
 * Security Rule: Frontend chi dieu phoi confirmation, khong tu tinh so admin cuoi cung.
 */
export async function thuHoiVaiTro(taiKhoanId, maVaiTro) {
  const id = layIdTaiKhoanAnToan(taiKhoanId)
  const vaiTro = layMaVaiTroAnToan(maVaiTro)
  const phanHoi = await ketNoiApi.delete(`/admin/accounts/${id}/roles/${vaiTro}`)

  return phanHoi.data
}
