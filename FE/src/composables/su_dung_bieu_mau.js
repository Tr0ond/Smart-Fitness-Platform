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

  async function datFocusVaoTruongLoiDau() {
    await nextTick()

    if (typeof document === 'undefined') {
      return false
    }

    const cacTruongCoLoi = Object.keys(loiTheoTruong.value)
    const thuTuFocus = [
      ...thuTuTruong.filter((tenTruong) => cacTruongCoLoi.includes(tenTruong)),
      ...cacTruongCoLoi.filter((tenTruong) => !thuTuTruong.includes(tenTruong)),
    ]

    for (const tenTruong of thuTuFocus) {
      const dieuKhien = Array.from(document.getElementsByName(tenTruong))
        .find((phanTu) => phanTu instanceof HTMLElement && !phanTu.hasAttribute('disabled'))

      if (dieuKhien instanceof HTMLElement) {
        dieuKhien.focus({ preventScroll: true })
        return true
      }
    }

    return false
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

    void datFocusVaoTruongLoiDau()
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
    datFocusVaoTruongLoiDau,
    apDungLoiApi,
  }
}
