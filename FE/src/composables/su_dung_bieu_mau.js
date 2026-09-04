import { computed, getCurrentScope, nextTick, onScopeDispose, ref } from 'vue'
import { layThongBaoLoiApi } from '../utils/thong_bao_loi.js'

function chuanHoaLoiTheoTruong(fieldErrors) {
  if (fieldErrors === null || typeof fieldErrors !== 'object') {
    return {}
  }

  return Object.fromEntries(
    Object.entries(fieldErrors).flatMap(([tenTruong, danhSach]) => {
      const danhSachAnToan = Array.isArray(danhSach)
        ? danhSach.filter((thongBao) => typeof thongBao === 'string' && thongBao.trim() !== '')
        : []

      return danhSachAnToan.length > 0 ? [[tenTruong, danhSachAnToan]] : []
    }),
  )
}

/**
 * Dat focus vao control loi dau tien hoac vung tom tat loi du phong.
 *
 * Dau vao: fieldErrors normalized, thu tu field, bang anh xa field -> DOM id va id du phong.
 * Cach hoat dong: doi DOM render, uu tien control theo name, sau do id va cuoi cung error summary.
 * Ket qua: true khi focus duoc mot phan tu kha dung, false khi khong co dich hop le.
 * Side effect: thay doi document.activeElement; khong sua draft, error state hay goi API.
 * Accessibility Rule: 422 luon dua nguoi dung den field loi dau hoac summary co the focus.
 */
export async function datFocusVaoTruongLoiDau(
  fieldErrors = {},
  thuTuTruong = [],
  idTheoTruong = {},
  idDuPhong = '',
) {
  await nextTick()

  if (typeof document === 'undefined' || typeof HTMLElement === 'undefined') {
    return false
  }

  const loiDaChuanHoa = chuanHoaLoiTheoTruong(fieldErrors)
  const cacTruongCoLoi = Object.keys(loiDaChuanHoa)
  const thuTuFocus = [
    ...thuTuTruong.filter((tenTruong) => cacTruongCoLoi.includes(tenTruong)),
    ...cacTruongCoLoi.filter((tenTruong) => !thuTuTruong.includes(tenTruong)),
  ]

  for (const tenTruong of thuTuFocus) {
    const dieuKhienTheoTen = Array.from(document.getElementsByName(tenTruong))
      .find((phanTu) => phanTu instanceof HTMLElement && !phanTu.hasAttribute('disabled'))
    const idDieuKhien = typeof idTheoTruong?.[tenTruong] === 'string'
      ? idTheoTruong[tenTruong]
      : ''
    const dieuKhienTheoId = idDieuKhien === '' ? null : document.getElementById(idDieuKhien)
    const dieuKhien = dieuKhienTheoTen
      ?? (dieuKhienTheoId instanceof HTMLElement && !dieuKhienTheoId.hasAttribute('disabled')
        ? dieuKhienTheoId
        : null)

    if (dieuKhien instanceof HTMLElement) {
      dieuKhien.focus({ preventScroll: true })
      return true
    }
  }

  const vungDuPhong = idDuPhong === '' ? null : document.getElementById(idDuPhong)
  if (vungDuPhong instanceof HTMLElement) {
    vungDuPhong.focus({ preventScroll: true })
    return true
  }

  return false
}

/**
 * Quan ly field error, focus loi dau va Retry-After cho bieu mau Foundation.
 *
 * Dau vao: thuTuTruong la danh sach name cua cac control theo thu tu UX mong muon.
 * Cach hoat dong: map normalized 422 errors, focus control loi dau; voi 429 thi bat
 * countdown theo retryAfter va expose trang thai khoa submit cho component.
 * Ket qua: cac ref/computed va handler dung chung cho login/forgot/reset form.
 * Side effect: co the thay activeElement va tao mot interval tam thoi; interval duoc don khi unmount.
 * Business Rule: khong auto-retry, khong lam mat draft va khong tu tao Retry-After.
 */
export function suDungBieuMau(thuTuTruong = []) {
  const loiTongQuat = ref('')
  const loiTheoTruong = ref({})
  const giayChoConLai = ref(0)
  let boDemNguoc = null

  const dangBiGioiHan = computed(() => giayChoConLai.value > 0)
  const thongBaoDemNguoc = computed(() => (
    dangBiGioiHan.value
      ? `Bạn có thể thử lại sau ${giayChoConLai.value} giây.`
      : ''
  ))

  function dungDemNguoc() {
    if (boDemNguoc !== null) {
      clearInterval(boDemNguoc)
      boDemNguoc = null
    }
  }

  function batDauDemNguoc(retryAfter) {
    dungDemNguoc()

    const soGiay = Number.isFinite(retryAfter)
      ? Math.max(0, Math.ceil(retryAfter))
      : 0

    giayChoConLai.value = soGiay

    if (soGiay === 0) {
      return
    }

    const mocKetThuc = Date.now() + soGiay * 1000
    boDemNguoc = setInterval(() => {
      giayChoConLai.value = Math.max(0, Math.ceil((mocKetThuc - Date.now()) / 1000))

      if (giayChoConLai.value === 0) {
        dungDemNguoc()
      }
    }, 1000)
  }

  function layLoiTruong(tenTruong) {
    const thongBao = loiTheoTruong.value?.[tenTruong]?.[0]
    return typeof thongBao === 'string' ? thongBao : ''
  }

  function xoaLoiTruong(tenTruong) {
    if (!Object.prototype.hasOwnProperty.call(loiTheoTruong.value, tenTruong)) {
      return
    }

    const banSao = { ...loiTheoTruong.value }
    delete banSao[tenTruong]
    loiTheoTruong.value = banSao
  }

  function xoaLoiHienThi() {
    loiTongQuat.value = ''
    loiTheoTruong.value = {}
  }

  function datLaiLoiBieuMau() {
    xoaLoiHienThi()
    dungDemNguoc()
    giayChoConLai.value = 0
  }

  async function datFocusVaoTruongLoiDauCuaBieuMau() {
    return datFocusVaoTruongLoiDau(loiTheoTruong.value, thuTuTruong)
  }

  function apDungLoiApi(error, fallback) {
    loiTheoTruong.value = error?.httpStatus === 422
      ? chuanHoaLoiTheoTruong(error.fieldErrors)
      : {}
    loiTongQuat.value = layThongBaoLoiApi(error, fallback)

    if (error?.httpStatus === 429) {
      batDauDemNguoc(error.retryAfter)
    } else {
      dungDemNguoc()
      giayChoConLai.value = 0
    }

    void datFocusVaoTruongLoiDauCuaBieuMau()
  }

  if (getCurrentScope() !== undefined) {
    onScopeDispose(dungDemNguoc)
  }

  return {
    loiTongQuat,
    loiTheoTruong,
    giayChoConLai,
    dangBiGioiHan,
    thongBaoDemNguoc,
    layLoiTruong,
    xoaLoiTruong,
    xoaLoiHienThi,
    datLaiLoiBieuMau,
    datFocusVaoTruongLoiDau: datFocusVaoTruongLoiDauCuaBieuMau,
    apDungLoiApi,
  }
}
