import { dieuPhoiTheoVaiTro } from '../router/bao_ve_tuyen_duong.js'

const CAC_VAI_TRO_WEB = Object.freeze(['ADMIN', 'PT', 'RECEPTIONIST'])
const TUYEN_DANG_NHAP_THEO_VAI_TRO = Object.freeze({
  ADMIN: 'adminDangNhap',
  PT: 'ptDangNhap',
  RECEPTIONIST: 'leTanDangNhap',
})

/**
 * Lay route login noi bo theo actor da dung truoc khi phien het han.
 *
 * Dau vao: vaiTro co the la ADMIN, PT, RECEPTIONIST hoac gia tri khong hop le.
 * Cach hoat dong: tra route name tu allow-list co dinh, fallback neutral selector.
 * Ket qua: mot route name noi bo da dang ky trong Foundation.
 * Side effect: khong navigate, khong doc token va khong goi API.
 * Business Rule: khong nhan URL/raw redirect va khong tu dat role priority.
 */
export function layTuyenDangNhapTheoVaiTro(vaiTro) {
  return TUYEN_DANG_NHAP_THEO_VAI_TRO[vaiTro] ?? 'chonVaiTro'
}

/**
 * Dieu huong sau 401 cua dung phien hien tai ve login actor an toan.
 *
 * Dau vao: Vue Router va actor da duoc Auth Store chup truoc khi cleanup.
 * Cach hoat dong: map actor qua allow-list route name, bo qua navigation trung lap
 * va replace de protected route khong con nam trong history.
 * Ket qua: true neu da replace, false neu dang o dung route dich.
 * Side effect: thay doi route hien tai; khong xoa session vi Store da cleanup truoc.
 * Business Rule: late 401 token cu khong duoc goi ham nay; khong tao open redirect.
 */
export async function dieuPhoiSau401(router, vaiTro) {
  const tenTuyenDich = layTuyenDangNhapTheoVaiTro(vaiTro)

  if (router?.currentRoute?.value?.name === tenTuyenDich) {
    return false
  }

  await router.replace({ name: tenTuyenDich })
  return true
}

/**
 * Hoan tat flow login cua actor ma khong dua role vao payload Auth.
 *
 * Dau vao: Auth Store da dang nhap thanh cong, router hien tai va actor mong doi cua man hinh.
 * Cach hoat dong: chi auto-chon khi account co dung mot Web role trung actor; multi-role luon de nguoi dung chon.
 * Ket qua: dieu huong neutral selector hoac home da dang ky, khong tao fake destination.
 * Side effect: co the luu actor context qua action Store; khong luu password va khong goi API lan hai.
 * Business Rule: MEMBER-only khong co Web home; actor mismatch khong bi ep vao role dang nhap.
 */
export async function dieuPhoiSauDangNhap(store, router, vaiTroMongDoi) {
  const danhSachVaiTroWeb = Array.isArray(store.vaiTro)
    ? store.vaiTro.filter((vaiTro) => CAC_VAI_TRO_WEB.includes(vaiTro))
    : []

  if (danhSachVaiTroWeb.length !== 1 || danhSachVaiTroWeb[0] !== vaiTroMongDoi) {
    await router.push({ name: 'chonVaiTro' })
    return
  }

  store.chonVaiTroDangDung(vaiTroMongDoi)
  const diemDen = dieuPhoiTheoVaiTro(store, vaiTroMongDoi)

  if (diemDen !== null && diemDen.name !== 'khongCoQuyen') {
    await router.push(diemDen)
    return
  }

  await router.push({ name: 'chonVaiTro' })
}
