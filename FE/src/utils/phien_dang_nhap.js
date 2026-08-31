export const KHOA_TOKEN_PHIEN = 'smart_fitness.auth.token'
export const KHOA_ACTOR_PHIEN = 'smart_fitness.auth.actor'

/**
 * Lay sessionStorage neu runtime cho phep truy cap.
 *
 * Dau vao: khong co.
 * Cach hoat dong: kiem tra moi truong browser va bat loi getter storage.
 * Ket qua: doi tuong storage hoac null khi SSR/quyen truy cap khong san sang.
 * Side effect: khong tao storage va khong doc gia tri nhay cam.
 * Security Rule: helper chi duoc dung storage cua tab cho auth session.
 */
function laySessionStorage() {
  if (typeof window === 'undefined') {
    return null
  }

  try {
    return window.sessionStorage
  } catch {
    return null
  }
}

/**
 * Doc mot auth value primitive va chuan hoa whitespace.
 *
 * Dau vao: mot auth key noi bo.
 * Cach hoat dong: getItem trong try/catch, trim string va coi rong la null.
 * Ket qua: string chuan hoa hoac null neu storage loi/thieu value.
 * Side effect: chi doc sessionStorage, khong ghi va khong xoa key khac.
 * Security Rule: khong expose loi storage hoac raw secret cho caller.
 */
function docGiaTriPhien(khoa) {
  const boNhoPhien = laySessionStorage()

  if (boNhoPhien === null) {
    return null
  }

  try {
    const giaTri = boNhoPhien.getItem(khoa)
    const giaTriChuanHoa = typeof giaTri === 'string' ? giaTri.trim() : ''

    return giaTriChuanHoa === '' ? null : giaTriChuanHoa
  } catch {
    return null
  }
}

/**
 * Ghi mot auth value da duoc public API validate.
 *
 * Dau vao: auth key va string da chuan hoa.
 * Cach hoat dong: setItem trong try/catch va quy doi loi thanh thong bao an toan.
 * Ket qua: khong tra du lieu; caller nhan Error neu khong the ghi.
 * Side effect: chi ghi auth key cua helper trong sessionStorage.
 * Security Rule: khong ghi password, reset token, DTO hay raw value vao thong diep.
 */
function luuGiaTriPhien(khoa, giaTri) {
  const boNhoPhien = laySessionStorage()

  if (boNhoPhien === null) {
    throw new Error('Khong the luu phien dang nhap trong sessionStorage.')
  }

  try {
    boNhoPhien.setItem(khoa, giaTri)
  } catch {
    throw new Error('Khong the luu phien dang nhap trong sessionStorage.')
  }
}

/**
 * Xoa mot auth key rieng le va khong lam cleanup bi vo khi storage loi.
 *
 * Dau vao: auth key noi bo.
 * Cach hoat dong: removeItem neu storage kha dung, nuot loi co kiem soat.
 * Ket qua: key duoc xoa neu co the; key domain khac khong bi cham toi.
 * Side effect: co the thay doi sessionStorage cua auth.
 * Security Rule: khong dung clear tren toan bo storage cua tab.
 */
function xoaGiaTriPhien(khoa) {
  const boNhoPhien = laySessionStorage()

  if (boNhoPhien === null) {
    return
  }

  try {
    boNhoPhien.removeItem(khoa)
  } catch {
    // Khong de storage loi lam dut cleanup auth local.
  }
}

/**
 * Doc access token da duoc luu cho phien Auth hien tai.
 *
 * Dau vao: khong co.
 * Cach hoat dong: doc duy nhat khoa auth token tu sessionStorage va trim gia tri.
 * Ket qua: token string chuan hoa hoac null khi khong co/khong doc duoc.
 * Side effect: khong ghi storage va khong doc persistent storage khac.
 * Business Rule: chi token phien duoc phep qua boundary nay; password/reset token
 * va user DTO khong thuoc session helper.
 */
export function docTokenPhienDangNhap() {
  return docGiaTriPhien(KHOA_TOKEN_PHIEN)
}

/**
 * Luu access token cua phien Auth vao sessionStorage.
 *
 * Dau vao: token phai la string khac rong sau khi trim.
 * Cach hoat dong: validate primitive roi ghi vao khoa auth token.
 * Ket qua: khong tra du lieu; loi storage/du lieu duoc bao bang Error an toan.
 * Side effect: ghi smart_fitness.auth.token, khong ghi password hay reset token.
 * Business Rule: khong dua raw token vao thong diep loi va khong dung storage khac.
 */
export function luuTokenPhienDangNhap(token) {
  if (typeof token !== 'string' || token.trim() === '') {
    throw new Error('Token phien dang nhap khong hop le.')
  }

  luuGiaTriPhien(KHOA_TOKEN_PHIEN, token.trim())
}

/**
 * Doc actor context da chon cho phien Auth hien tai.
 *
 * Dau vao: khong co.
 * Cach hoat dong: doc duy nhat khoa actor tu sessionStorage va trim gia tri.
 * Ket qua: actor string chuan hoa hoac null khi khong co/khong doc duoc.
 * Side effect: khong ghi storage va khong tu suy role tu client.
 * Business Rule: Auth Store van phai doi chieu actor voi roles tu Backend /me.
 */
export function docVaiTroDangDung() {
  return docGiaTriPhien(KHOA_ACTOR_PHIEN)
}

/**
 * Luu actor context da duoc Auth Store validate.
 *
 * Dau vao: vaiTro phai la string khac rong; allow-list role do Store/Backend xu ly.
 * Cach hoat dong: validate primitive roi ghi actor context vao sessionStorage.
 * Ket qua: khong tra du lieu; loi duoc bao an toan neu storage khong kha dung.
 * Side effect: ghi smart_fitness.auth.actor, khong ghi account DTO/password/token.
 * Business Rule: helper khong cap quyen va khong tu chon uu tien multi-role.
 */
export function luuVaiTroDangDung(vaiTro) {
  if (typeof vaiTro !== 'string' || vaiTro.trim() === '') {
    throw new Error('Vai tro dang dung khong hop le.')
  }

  luuGiaTriPhien(KHOA_ACTOR_PHIEN, vaiTro.trim())
}

/**
 * Xoa actor context nhung giu access token de revalidate lai roles.
 *
 * Dau vao: khong co.
 * Cach hoat dong: remove rieng khoa actor va bo qua loi storage co kiem soat.
 * Ket qua: actor cu khong con duoc khoi phuc tu sessionStorage.
 * Side effect: chi xoa smart_fitness.auth.actor, giu nguyen key khong lien quan.
 * Business Rule: dung khi Backend /me revoke actor, khong coi day la logout toan bo.
 */
export function xoaVaiTroDangDung() {
  xoaGiaTriPhien(KHOA_ACTOR_PHIEN)
}

/**
 * Xoa toan bo hai gia tri auth do helper so huu.
 *
 * Dau vao: khong co.
 * Cach hoat dong: remove token va actor rieng le, khong xoa toan bo storage cua tab.
 * Ket qua: auth session storage sach trong khi key cua domain khac duoc bao toan.
 * Side effect: xoa smart_fitness.auth.token va smart_fitness.auth.actor; loi remove
 * duoc nuot an toan de cleanup memory cua Store van hoan tat.
 * Business Rule: khong xoa storage khac, password, reset token, user DTO hay cache.
 */
export function xoaDuLieuPhienDangNhap() {
  xoaGiaTriPhien(KHOA_TOKEN_PHIEN)
  xoaGiaTriPhien(KHOA_ACTOR_PHIEN)
}
