import { ref } from 'vue'
import { taoKhoaIdempotency } from '../utils/khoa_idempotency.js'

/**
 * Quan ly mot khoa on dinh cho mot logical action cua component.
 *
 * Dau vao: khong co; component goi batDauThaoTac truoc mutation.
 * Cach hoat dong: tao mot khoa o lan bat dau dau tien, giu khoa qua moi retry
 * network/timeout va chi xoa khi action ket thuc terminal hoac bi huy.
 * Ket qua: cung cap API doc/bat dau/ket thuc/huy cho caller gan header.
 * Side effect: chi cap nhat ref noi bo; khong import API, domain store hay router.
 * Business Rule: cung action khong duoc doi khoa; action moi sau cleanup moi tao
 * UUID moi de Backend phan biet cac lan xu ly.
 */
export function suDungThaoTacChongLap() {
  const khoaHienTai = ref(null)

  /**
   * Bat dau hoac tiep tuc logical action voi cung mot khoa.
   *
   * Dau vao: khong co.
   * Cach hoat dong: chi goi taoKhoaIdempotency khi chua co action dang giu khoa.
   * Ket qua: tra ve khoa on dinh cho moi request/retry cua action hien tai.
   * Side effect: tao va luu UUID trong vong doi composable.
   * Business Rule: network/timeout khong phai terminal success, vi vay khong
   * duoc tu xoa khoa trong ham nay.
   */
  function batDauThaoTac() {
    if (khoaHienTai.value === null) {
      khoaHienTai.value = taoKhoaIdempotency()
    }

    return khoaHienTai.value
  }

  /**
   * Doc khoa dang gan voi logical action hien tai.
   *
   * Dau vao: khong co.
   * Cach hoat dong: doc gia tri ref ma khong tao khoa moi.
   * Ket qua: UUID hien tai hoac null neu action chua bat dau/da cleanup.
   * Side effect: khong thay doi state.
   * Business Rule: caller khong dung ket qua null de gia lap mot khoa retry.
   */
  function layKhoaHienTai() {
    return khoaHienTai.value
  }

  /**
   * Ket thuc action terminal sau khi caller da nhan ket qua cuoi.
   *
   * Dau vao: khong co.
   * Cach hoat dong: xoa khoa dang giu trong composable.
   * Ket qua: action ke tiep se duoc cap mot UUID moi.
   * Side effect: reset ref noi bo; khong goi API va khong xoa business state.
   * Business Rule: chi goi sau success/conflict/ket qua terminal da duoc xu ly.
   */
  function ketThucThaoTac() {
    khoaHienTai.value = null
  }

  /**
   * Huy action dang cho va loai bo khoa de tranh retry nham action cu.
   *
   * Dau vao: khong co.
   * Cach hoat dong: reset khoa hien tai, ke ca khi action chua tao request.
   * Ket qua: lan bat dau sau la mot logical action moi.
   * Side effect: reset ref noi bo; khong goi API va khong tao UUID thay the.
   * Business Rule: sau cancel khong duoc tiep tuc dung khoa cua action da huy.
   */
  function huyThaoTac() {
    khoaHienTai.value = null
  }

  return {
    batDauThaoTac,
    layKhoaHienTai,
    ketThucThaoTac,
    huyThaoTac,
  }
}
