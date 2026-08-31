import ketNoiApi from './api.js'

/**
 * Gui thong tin dang nhap theo allow-list cua Backend Auth.
 *
 * Dau vao: thongTinDangNhap gom email, password va tuy chon device_name.
 * Cach hoat dong: tao payload chi gom ba field Backend cho phep, sau do goi POST /auth/login.
 * Ket qua: tra ve body response thuc te cua Backend, bao gom data.access_token va user.
 * Side effect: tao token phia Backend; Frontend service khong luu password/token.
 * Business Rule: khong tu suy role, khong navigate va khong tu chuan hoa loi lan nua.
 */
export async function dangNhap(thongTinDangNhap) {
  const payload = {
    email: thongTinDangNhap?.email,
    password: thongTinDangNhap?.password,
  }

  if (Object.prototype.hasOwnProperty.call(thongTinDangNhap ?? {}, 'device_name')) {
    payload.device_name = thongTinDangNhap.device_name
  }

  const phanHoi = await ketNoiApi.post('/auth/login', payload)

  return phanHoi.data
}

/**
 * Tai lai account va role hien tai tu Auth Backend.
 *
 * Dau vao: khong co; Axios tu dong lay Bearer token qua accessor dang ky voi client.
 * Cach hoat dong: goi GET /auth/me va tra nguyen body allow-list de Store revalidate authority.
 * Ket qua: body co data user voi id, name, email, status va roles theo source Backend.
 * Side effect: Backend cap nhat last-used token; Frontend khong cache user vao storage.
 * Business Rule: roles tu /me thay the role state cu, khong tin role tam tu login response.
 */
export async function taiThongTinNguoiDung() {
  const phanHoi = await ketNoiApi.get('/auth/me')

  return phanHoi.data
}

/**
 * Thu hoi phien dang nhap hien tai o Backend.
 *
 * Dau vao: khong co; request dung Bearer token hien tai neu Store dang co phien.
 * Cach hoat dong: goi POST /auth/logout va tra body response thuc te.
 * Ket qua: Backend xac nhan thu hoi current token.
 * Side effect: token hien tai bi revoke; local cleanup luon do Auth Store dam bao.
 * Business Rule: remote logout la best-effort, khong navigate va khong anh huong token thiet bi khac.
 */
export async function dangXuat() {
  const phanHoi = await ketNoiApi.post('/auth/logout')

  return phanHoi.data
}

/**
 * Gui yeu cau dat lai mat khau theo contract enumeration-safe cua Backend.
 *
 * Dau vao: email do nguoi dung nhap.
 * Cach hoat dong: chi gui field email toi POST /auth/forgot-password; Backend luon tra thong bao tong quat.
 * Ket qua: tra body response thuc te, khong suy dien email co ton tai hay khong.
 * Side effect: Backend xep job xu ly; Frontend khong luu email, token hay password.
 * Business Rule: khong lookup account o Frontend, khong retry va khong them idempotency key.
 */
export async function guiYeuCauDatLaiMatKhau(email) {
  const phanHoi = await ketNoiApi.post('/auth/forgot-password', { email })

  return phanHoi.data
}

/**
 * Dat lai mat khau bang raw reset token va mat khau moi theo exact Backend fields.
 *
 * Dau vao: thongTinDatLai gom token, password va password_confirmation.
 * Cach hoat dong: allow-list payload toi POST /auth/reset-password, khong gui email hay user id.
 * Ket qua: tra body response thuc te cua Backend.
 * Side effect: Backend consume token mot lan va thu hoi cac phien cu; Frontend khong luu token.
 * Business Rule: khong retry, khong log token va khong navigate trong service.
 */
export async function datLaiMatKhau(thongTinDatLai) {
  const payload = {
    token: thongTinDatLai?.token,
    password: thongTinDatLai?.password,
    password_confirmation: thongTinDatLai?.password_confirmation,
  }
  const phanHoi = await ketNoiApi.post('/auth/reset-password', payload)

  return phanHoi.data
}
