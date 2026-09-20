import { defineStore } from 'pinia'
import { datBoDocToken, datXuLy401 } from '../services/api.js'
import {
  dangNhap as dangNhapApi,
  dangXuat as dangXuatApi,
  taiThongTinNguoiDung as taiThongTinNguoiDungApi,
} from '../services/xac_thuc.api.js'
import { xoaDuLieuTaiKhoanNeuDaKhoiTao } from './tai_khoan.store.js'
import { xoaDuLieuHuanLuyenVienNeuDaKhoiTao } from './huan_luyen_vien.store.js'
import { xoaDuLieuPhanCongPtNeuDaKhoiTao } from './phan_cong_pt.store.js'
import { xoaDuLieuDanhMucNeuDaKhoiTao } from './danh_muc.store.js'
import { xoaDuLieuThanhToanNeuDaKhoiTao } from './thanh_toan.store.js'
import {
  docTokenPhienDangNhap,
  docVaiTroDangDung,
  luuTokenPhienDangNhap,
  luuVaiTroDangDung,
  xoaDuLieuPhienDangNhap,
  xoaVaiTroDangDung,
} from '../utils/phien_dang_nhap.js'

export { KHOA_ACTOR_PHIEN, KHOA_TOKEN_PHIEN } from '../utils/phien_dang_nhap.js'

const CAC_VAI_TRO_WEB = Object.freeze(['ADMIN', 'PT', 'RECEPTIONIST'])
const dieuPhoiSau401TheoStore = new WeakMap()

function laDoiTuong(giaTri) {
  return giaTri !== null && typeof giaTri === 'object'
}

function laVaiTroWeb(vaiTro) {
  return CAC_VAI_TRO_WEB.includes(vaiTro)
}

function layVaiTroTuBackend(nguoiDung) {
  if (!Array.isArray(nguoiDung?.roles)) {
    throw new Error('Phan hoi /auth/me khong co danh sach roles hop le.')
  }

  return nguoiDung.roles.filter((vaiTro) => typeof vaiTro === 'string' && vaiTro.trim() !== '')
}

/**
 * Phan biet loi /me co the retry ma khong huy credential cua phien.
 *
 * Dau vao: normalized API error.
 * Cach hoat dong: chi coi network/timeout hoac HTTP 5xx la tam thoi.
 * Ket qua: boolean dung de chon giu token hay cleanup fail-closed.
 * Side effect: khong thay state va khong goi API.
 * Business Rule: 401 va response auth-invalid khong duoc giu session.
 */
function laLoiTamThoiTaiThongTinNguoiDung(error) {
  return error?.isNetworkError === true
    || error?.httpStatus === null
    || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

/**
 * Tao error allow-list de selector hien trang thai restore tam thoi.
 *
 * Dau vao: normalized API error cua /me.
 * Cach hoat dong: chi copy status/code/message/network flag da chuan hoa.
 * Ket qua: object bat bien khong chua request, config, header hay token.
 * Side effect: khong log va khong sua error goc.
 * Security Rule: UI khong nhan raw Axios error hoac credential.
 */
function taoLoiKhoiPhucAnToan(error) {
  const thongBao = typeof error?.message === 'string' && error.message.trim() !== ''
    ? error.message
    : 'Không thể xác minh phiên làm việc lúc này. Vui lòng thử lại.'

  return Object.freeze({
    httpStatus: Number.isInteger(error?.httpStatus) ? error.httpStatus : null,
    code: typeof error?.code === 'string' ? error.code : null,
    message: thongBao,
    isNetworkError: error?.isNetworkError === true,
  })
}

export const useXacThucStore = defineStore('xac_thuc', {
  state: () => ({
    token: null,
    nguoiDung: null,
    vaiTro: [],
    vaiTroDangDung: null,
    daKhoiPhucPhien: false,
    dangKhoiPhucPhien: false,
    dangDangNhap: false,
    loiKhoiPhucPhien: null,
  }),

  actions: {
    /**
     * Noi Auth Store voi Axios bang accessor callback, khong tao vong phu thuoc module.
     *
     * Dau vao: callback dieu phoi tuy chon do app shell cung cap sau khi phien hien tai bi 401.
     * Cach hoat dong: dang ky doc token tu state, doi chieu token snapshot cua request 401
     * voi token hien tai, sau do moi cleanup va bao actor cu cho callback ben ngoai.
     * Ket qua: request dung token moi nhat; late 401 cua token cu bi bo qua.
     * Side effect: cap nhat callback noi bo cua single Axios client; callback ngoai co the navigate.
     * Business Rule: Store khong import Router; chi 401 cua dung phien hien tai moi duoc xoa session.
     */
    dangKyCoCheAuth(xuLySau401) {
      if (xuLySau401 !== undefined) {
        if (typeof xuLySau401 === 'function') {
          dieuPhoiSau401TheoStore.set(this, xuLySau401)
        } else {
          dieuPhoiSau401TheoStore.delete(this)
        }
      }

      datBoDocToken(() => this.token)
      datXuLy401(({ tokenDaGui } = {}) => {
        const tokenHienTai = typeof this.token === 'string' ? this.token.trim() : ''

        if (typeof tokenDaGui !== 'string' || tokenDaGui === '' || tokenDaGui !== tokenHienTai) {
          return false
        }

        const actorDaDung = laVaiTroWeb(this.vaiTroDangDung)
          ? this.vaiTroDangDung
          : docVaiTroDangDung()
        const dieuPhoiSau401 = dieuPhoiSau401TheoStore.get(this)

        this.xoaPhienDangNhap()

        if (typeof dieuPhoiSau401 === 'function') {
          try {
            Promise.resolve(dieuPhoiSau401(laVaiTroWeb(actorDaDung) ? actorDaDung : null)).catch(() => {})
          } catch {
            // Loi dieu huong khong duoc che mat normalized 401 cua request goc.
          }
        }

        return true
      })
    },

    /**
     * Xoa toan bo thong tin phien Auth local theo mot cleanup path duy nhat.
     *
     * Dau vao: khong co.
     * Cach hoat dong: reset token memory, account, roles, actor va hai khoa sessionStorage auth.
     * Ket qua: Store ve trang thai chua xac thuc an toan.
     * Side effect: xoa smart_fitness.auth.token va smart_fitness.auth.actor; khong xoa storage cua domain khac.
     * Business Rule: cleanup khong navigate, khong log token va khong tao role moi.
     */
    xoaPhienDangNhap() {
      this.token = null
      this.nguoiDung = null
      this.vaiTro = []
      this.vaiTroDangDung = null
      this.daKhoiPhucPhien = false
      this.dangKhoiPhucPhien = false
      this.dangDangNhap = false
      this.loiKhoiPhucPhien = null
      xoaDuLieuPhienDangNhap()
      xoaDuLieuTaiKhoanNeuDaKhoiTao()
      xoaDuLieuHuanLuyenVienNeuDaKhoiTao()
      xoaDuLieuPhanCongPtNeuDaKhoiTao()
      xoaDuLieuDanhMucNeuDaKhoiTao()
      xoaDuLieuThanhToanNeuDaKhoiTao()
    },

    /**
     * Khoa protected content khi /me tam thoi khong san sang ma van giu token de retry.
     *
     * Dau vao: normalized network/timeout/5xx error cua GET /auth/me.
     * Cach hoat dong: xoa user/roles/active actor khoi memory dang render, giu token va actor
     * trong sessionStorage, sau do luu mot error allow-list an toan cho selector.
     * Ket qua: Store khong con dat dieu kien daXacThuc nhung co the thu lai /me cung token.
     * Side effect: protected shell bien mat; khong xoa credential session va khong navigate.
     * Business Rule: cached user/role khong duoc dung khi /me loi tam thoi.
     */
    ghiNhanLoiKhoiPhucTamThoi(error) {
      this.nguoiDung = null
      this.vaiTro = []
      this.vaiTroDangDung = null
      this.loiKhoiPhucPhien = taoLoiKhoiPhucAnToan(error)
      xoaDuLieuTaiKhoanNeuDaKhoiTao()
      xoaDuLieuHuanLuyenVienNeuDaKhoiTao()
      xoaDuLieuPhanCongPtNeuDaKhoiTao()
      xoaDuLieuDanhMucNeuDaKhoiTao()
      xoaDuLieuThanhToanNeuDaKhoiTao()
    },

    /**
     * Revalidate account sau khi /auth/me tra ve va thay the role state cu.
     *
     * Dau vao: nguoiDung allow-list trong data cua GET /auth/me.
     * Cach hoat dong: validate object/roles, luu user memory va thay toan bo vaiTro bang response hien tai.
     * Ket qua: tra ve current user; actor cu bi loai neu khong con la Web role hop le.
     * Side effect: co the xoa actor context trong sessionStorage.
     * Business Rule: Backend /me la authority; MEMBER khong duoc bien thanh Web role, Free/Premium khong phai role.
     */
    capNhatTheoThongTinHienTai(nguoiDung) {
      if (!laDoiTuong(nguoiDung)) {
        this.xoaPhienDangNhap()
        throw new Error('Phan hoi /auth/me khong hop le.')
      }

      const actorTruocDo = this.vaiTroDangDung
      const danhSachVaiTro = layVaiTroTuBackend(nguoiDung)

      this.nguoiDung = nguoiDung
      this.vaiTro = danhSachVaiTro

      if (!laVaiTroWeb(this.vaiTroDangDung) || !danhSachVaiTro.includes(this.vaiTroDangDung)) {
        this.vaiTroDangDung = null
        xoaVaiTroDangDung()
      }

      if (actorTruocDo !== this.vaiTroDangDung) {
        xoaDuLieuTaiKhoanNeuDaKhoiTao()
        xoaDuLieuHuanLuyenVienNeuDaKhoiTao()
        xoaDuLieuPhanCongPtNeuDaKhoiTao()
        xoaDuLieuDanhMucNeuDaKhoiTao()
        xoaDuLieuThanhToanNeuDaKhoiTao()
      }

      return nguoiDung
    },

    /**
     * Dang nhap va revalidate ngay account/roles bang GET /auth/me.
     *
     * Dau vao: thongTinDangNhap theo contract Backend, co email/password va device_name tuy chon.
     * Cach hoat dong: xoa phien cu, goi login, luu access_token vao memory + sessionStorage, sau do goi /me.
     * Ket qua: tra current user sau khi /me thanh cong.
     * Side effect: tao phien local; 401/response auth-invalid xoa token, con network/timeout/5xx
     * cua /me giu token, khoa protected content va de selector cho retry.
     * Business Rule: token login response chua la authority role cuoi; khong tu chon role.
     */
    async dangNhap(thongTinDangNhap) {
      this.dangKyCoCheAuth()
      this.xoaPhienDangNhap()
      this.dangDangNhap = true
      let daNhanToken = false

      try {
        const phanHoiDangNhap = await dangNhapApi(thongTinDangNhap)
        const duLieuDangNhap = phanHoiDangNhap?.data
        const tokenMoi = typeof duLieuDangNhap?.access_token === 'string'
          ? duLieuDangNhap.access_token.trim()
          : ''

        if (tokenMoi === '' || duLieuDangNhap?.token_type !== 'Bearer') {
          throw new Error('Phan hoi dang nhap khong co Bearer token hop le.')
        }

        luuTokenPhienDangNhap(tokenMoi)
        this.token = tokenMoi
        daNhanToken = true

        const nguoiDungHienTai = await this.taiThongTinNguoiDung()
        this.daKhoiPhucPhien = true

        return nguoiDungHienTai
      } catch (error) {
        if (daNhanToken && this.token && laLoiTamThoiTaiThongTinNguoiDung(error)) {
          this.ghiNhanLoiKhoiPhucTamThoi(error)
          this.daKhoiPhucPhien = true
          return null
        }

        this.xoaPhienDangNhap()
        throw error
      } finally {
        this.dangDangNhap = false
      }
    },

    /**
     * Tai va revalidate current user tu GET /auth/me.
     *
     * Dau vao: khong co; token hien tai duoc doc qua accessor cua Axios.
     * Cach hoat dong: neu chua co token thi khong goi Backend; neu co thi cap nhat user/roles tu response.
     * Ket qua: tra ve current user hoac null khi chua co phien.
     * Side effect: thay role state cu va co the clear actor da bi revoke.
     * Business Rule: khong dung cached user lam authority sau reload; 401 duoc Axios hook don dep.
     */
    async taiThongTinNguoiDung() {
      this.dangKyCoCheAuth()

      if (!this.token) {
        return null
      }

      const phanHoi = await taiThongTinNguoiDungApi()
      const nguoiDung = phanHoi?.data

      return this.capNhatTheoThongTinHienTai(nguoiDung)
    },

    /**
     * Khoi phuc sessionStorage token va bat buoc revalidate bang /auth/me.
     *
     * Dau vao: token/actor context da luu trong sessionStorage neu co.
     * Cach hoat dong: doc token vao memory, xoa authority cache va goi /me; 401/auth-invalid
     * clear session, con network/timeout/5xx giu token va ghi loi tam thoi de retry.
     * Ket qua: true khi /me thanh cong, false khi khong co token, token invalid hoac loi tam thoi.
     * Side effect: cap nhat Pinia state; chi xoa storage khi phien khong con hop le.
     * Business Rule: actor chi duoc khoi phuc neu nam trong roles hien tai cua Backend; khong auto-priority multi-role.
     */
    async khoiPhucPhien() {
      this.dangKyCoCheAuth()
      this.daKhoiPhucPhien = false
      this.dangKhoiPhucPhien = true
      this.loiKhoiPhucPhien = null

      const tokenDaLuu = docTokenPhienDangNhap()
      const actorDaLuu = docVaiTroDangDung()

      if (typeof tokenDaLuu !== 'string' || tokenDaLuu.trim() === '') {
        this.xoaPhienDangNhap()
        this.daKhoiPhucPhien = true
        return false
      }

      this.token = tokenDaLuu
      this.nguoiDung = null
      this.vaiTro = []
      this.vaiTroDangDung = null

      try {
        const nguoiDung = await this.taiThongTinNguoiDung()

        if (laVaiTroWeb(actorDaLuu) && this.vaiTro.includes(actorDaLuu)) {
          this.vaiTroDangDung = actorDaLuu
          luuVaiTroDangDung(actorDaLuu)
        } else {
          xoaVaiTroDangDung()
        }

        return nguoiDung !== null
      } catch (error) {
        if (this.token && laLoiTamThoiTaiThongTinNguoiDung(error)) {
          this.ghiNhanLoiKhoiPhucTamThoi(error)
          return false
        }

        this.xoaPhienDangNhap()
        return false
      } finally {
        this.dangKhoiPhucPhien = false
        this.daKhoiPhucPhien = true
      }
    },

    /**
     * Chon actor context da duoc Backend cap role, khong tu dat thu tu uu tien.
     *
     * Dau vao: vaiTro phai la ADMIN, PT hoac RECEPTIONIST dang co trong vaiTro hien tai.
     * Cach hoat dong: kiem tra allow-list + roles authority roi luu actor context toi sessionStorage.
     * Ket qua: tra ve actor da chon.
     * Side effect: thay vaiTroDangDung va ghi mot string actor; khong luu user/password/token moi.
     * Business Rule: khong chon MEMBER, FREE/Premium, role tuy y va khong auto-priority multi-role.
     */
    chonVaiTroDangDung(vaiTro) {
      if (!laVaiTroWeb(vaiTro) || !this.vaiTro.includes(vaiTro)) {
        throw new Error('Vai tro actor khong hop le trong roles hien tai.')
      }

      const actorTruocDo = this.vaiTroDangDung
      this.vaiTroDangDung = vaiTro
      luuVaiTroDangDung(vaiTro)

      if (actorTruocDo !== vaiTro) {
        xoaDuLieuTaiKhoanNeuDaKhoiTao()
        xoaDuLieuHuanLuyenVienNeuDaKhoiTao()
        xoaDuLieuPhanCongPtNeuDaKhoiTao()
        xoaDuLieuDanhMucNeuDaKhoiTao()
        xoaDuLieuThanhToanNeuDaKhoiTao()
      }

      return vaiTro
    },

    /**
     * Logout remote theo best-effort va luon don dep local session.
     *
     * Dau vao: khong co; request logout dung current Bearer neu token dang ton tai.
     * Cach hoat dong: thu POST /auth/logout, nuot loi network/5xx/401, sau do chay cleanup path duy nhat.
     * Ket qua: true neu remote thanh cong hoặc khong co token; false neu remote logout that bai.
     * Side effect: luon xoa token/user/roles/actor local, khong navigate va khong xoa storage domain khac.
     * Business Rule: nguoi dung khong bi mac ket local session vi remote logout khong san sang.
     */
    async dangXuat() {
      this.dangKyCoCheAuth()
      const coToken = typeof this.token === 'string' && this.token !== ''
      let remoteThanhCong = true

      try {
        if (coToken) {
          await dangXuatApi()
        }
      } catch {
        remoteThanhCong = false
      } finally {
        this.xoaPhienDangNhap()
      }

      return remoteThanhCong
    },
  },
})
