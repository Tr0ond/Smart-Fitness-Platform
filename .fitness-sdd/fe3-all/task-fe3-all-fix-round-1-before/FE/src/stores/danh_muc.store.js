import { defineStore, getActivePinia } from 'pinia'
import {
  capNhatBaiTap,
  taiChiTietBaiTap,
  taiDanhSachBaiTap,
  taoBaiTap,
} from '../services/bai_tap.api.js'
import {
  capNhatDungCu,
  taiDanhSachDungCu,
  taoDungCu,
} from '../services/dung_cu.api.js'
import {
  capNhatGiaoAnMau,
  taiChiTietGiaoAnMau,
  taiDanhSachGiaoAnMau,
  taoGiaoAnMau,
  taoPhienBanGiaoAnMau,
} from '../services/giao_an_mau.api.js'
import {
  capNhatGoiTap,
  taiChiTietGoiTap,
  taiDanhSachGoiTap,
  taoGoiTap,
  thayTheQuyenLoiGoiTap,
} from '../services/goi_tap.api.js'
import {
  capNhatNhomCo,
  taiDanhSachNhomCo,
  taoNhomCo,
} from '../services/nhom_co.api.js'

function taoLoiAnToan(error, fallback) {
  const fieldErrors = Object.fromEntries(Object.entries(error?.fieldErrors ?? {})
    .filter(([, values]) => Array.isArray(values))
    .map(([field, values]) => [field, values.filter((value) => typeof value === 'string')]))
  return Object.freeze({
    httpStatus: Number.isInteger(error?.httpStatus) ? error.httpStatus : null,
    code: typeof error?.code === 'string' ? error.code : null,
    message: typeof error?.message === 'string' && error.message.trim() !== '' ? error.message : fallback,
    fieldErrors,
    isNetworkError: error?.isNetworkError === true,
    ...(error?.outcomeUnknown === true ? { outcomeUnknown: true } : {}),
  })
}

function laLoiTamThoi(error) {
  return error?.isNetworkError === true || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

function layMang(phanHoi) {
  const data = phanHoi?.data ?? phanHoi
  if (!Array.isArray(data)) throw { code: 'CATALOG_LIST_RESPONSE_INVALID', message: 'Du lieu danh sach danh muc khong hop le.' }
  return data
}

function layBanGhi(phanHoi) {
  const data = phanHoi?.data ?? phanHoi
  if (data === null || typeof data !== 'object' || Array.isArray(data)) throw { code: 'CATALOG_DETAIL_RESPONSE_INVALID', message: 'Du lieu chi tiet danh muc khong hop le.' }
  return data
}

function taoTaiNguyen() {
  return {
    danhSach: [],
    chiTiet: null,
    dangTai: false,
    dangTaiChiTiet: false,
    loi: null,
    loiChiTiet: null,
    daTaiLanDau: false,
    soThuTu: 0,
    soThuTuChiTiet: 0,
    dangMutation: false,
    loiMutation: null,
    ketQuaMutation: null,
    cacheHetHanLuc: 0,
  }
}

function taoState() {
  return {
    goiTap: taoTaiNguyen(),
    dungCu: taoTaiNguyen(),
    nhomCo: taoTaiNguyen(),
    baiTap: taoTaiNguyen(),
    giaoAnMau: taoTaiNguyen(),
    boLocBaiTap: { search: '', status: '' },
  }
}

function layTaiNguyen(store, khoa) {
  return store[khoa]
}

async function taiDanhSachTaiNguyen(store, khoa, taiApi, fallback, { cacheTrongMs = 0, boQuaCache = false } = {}) {
  const resource = layTaiNguyen(store, khoa)
  if (!boQuaCache && cacheTrongMs > 0 && resource.daTaiLanDau && resource.cacheHetHanLuc > Date.now()) {
    return resource.danhSach
  }
  const sequence = ++resource.soThuTu
  resource.dangTai = true
  resource.loi = null
  try {
    const data = layMang(await taiApi())
    if (sequence !== resource.soThuTu) return null
    resource.danhSach = data
    resource.daTaiLanDau = true
    resource.cacheHetHanLuc = cacheTrongMs > 0 ? Date.now() + cacheTrongMs : 0
    return data
  } catch (error) {
    if (sequence === resource.soThuTu) {
      resource.loi = taoLoiAnToan(error, fallback)
      resource.daTaiLanDau = true
    }
    return null
  } finally {
    if (sequence === resource.soThuTu) resource.dangTai = false
  }
}

async function taiChiTietTaiNguyen(store, khoa, id, taiApi, fallback) {
  const resource = layTaiNguyen(store, khoa)
  const sequence = ++resource.soThuTuChiTiet
  resource.dangTaiChiTiet = true
  resource.loiChiTiet = null
  resource.chiTiet = null
  try {
    const data = layBanGhi(await taiApi(id))
    if (sequence !== resource.soThuTuChiTiet) return null
    resource.chiTiet = data
    return data
  } catch (error) {
    if (sequence === resource.soThuTuChiTiet) resource.loiChiTiet = taoLoiAnToan(error, fallback)
    return null
  } finally {
    if (sequence === resource.soThuTuChiTiet) resource.dangTaiChiTiet = false
  }
}

async function thucHienMutation(store, khoa, mutation, fallback, { taiLai = true } = {}) {
  const resource = layTaiNguyen(store, khoa)
  if (resource.dangMutation) return null
  resource.dangMutation = true
  resource.loiMutation = null
  resource.ketQuaMutation = null
  try {
    const result = layBanGhi(await mutation())
    resource.ketQuaMutation = result
    resource.loiMutation = null
    resource.cacheHetHanLuc = 0
    if (taiLai) {
      resource.soThuTu += 1
      resource.daTaiLanDau = false
    }
    return result
  } catch (error) {
    resource.loiMutation = taoLoiAnToan({
      ...error,
      ...(laLoiTamThoi(error) ? { outcomeUnknown: true } : {}),
    }, fallback)
    return null
  } finally {
    resource.dangMutation = false
  }
}

/**
 * Clear catalog state only when the Pinia instance has already created it.
 * This shared cleanup is called from logout, actor-role loss, and current-token 401 paths.
 */
export function xoaDuLieuDanhMucNeuDaKhoiTao(pinia = getActivePinia()) {
  if (!pinia?.state?.value?.danh_muc) return false
  useDanhMucStore(pinia).xoaDuLieu()
  return true
}

export const useDanhMucStore = defineStore('danh_muc', {
  state: taoState,

  getters: {
    danhSachGoiTap: (state) => state.goiTap.danhSach,
    danhSachDungCu: (state) => state.dungCu.danhSach,
    danhSachNhomCo: (state) => state.nhomCo.danhSach,
    danhSachBaiTap: (state) => state.baiTap.danhSach,
    danhSachGiaoAnMau: (state) => state.giaoAnMau.danhSach,
    chiTietGoiTap: (state) => state.goiTap.chiTiet,
    chiTietBaiTap: (state) => state.baiTap.chiTiet,
    chiTietGiaoAnMau: (state) => state.giaoAnMau.chiTiet,
  },

  actions: {
    async taiDanhSachGoiTap() {
      return taiDanhSachTaiNguyen(this, 'goiTap', taiDanhSachGoiTap, 'Khong the tai danh sach goi tap.')
    },
    async taiChiTietGoiTap(id) {
      return taiChiTietTaiNguyen(this, 'goiTap', id, taiChiTietGoiTap, 'Khong the tai chi tiet goi tap.')
    },
    async taoGoiTap(payload) {
      return thucHienMutation(this, 'goiTap', () => taoGoiTap(payload), 'Khong the tao goi tap.')
    },
    async capNhatGoiTap(id, payload) {
      return thucHienMutation(this, 'goiTap', () => capNhatGoiTap(id, payload), 'Khong the cap nhat goi tap.')
    },
    async thayTheQuyenLoiGoiTap(id, payload) {
      return thucHienMutation(this, 'goiTap', () => thayTheQuyenLoiGoiTap(id, payload), 'Khong the cap nhat quyen loi goi tap.')
    },

    async taiDanhSachDungCu({ boQuaCache = false } = {}) {
      return taiDanhSachTaiNguyen(this, 'dungCu', taiDanhSachDungCu, 'Khong the tai danh sach dung cu.', { cacheTrongMs: 30000, boQuaCache })
    },
    async taoDungCu(payload) {
      return thucHienMutation(this, 'dungCu', () => taoDungCu(payload), 'Khong the tao dung cu.')
    },
    async capNhatDungCu(id, payload) {
      return thucHienMutation(this, 'dungCu', () => capNhatDungCu(id, payload), 'Khong the cap nhat dung cu.')
    },

    async taiDanhSachNhomCo({ boQuaCache = false } = {}) {
      return taiDanhSachTaiNguyen(this, 'nhomCo', taiDanhSachNhomCo, 'Khong the tai danh sach nhom co.', { cacheTrongMs: 30000, boQuaCache })
    },
    async taoNhomCo(payload) {
      return thucHienMutation(this, 'nhomCo', () => taoNhomCo(payload), 'Khong the tao nhom co.')
    },
    async capNhatNhomCo(id, payload) {
      return thucHienMutation(this, 'nhomCo', () => capNhatNhomCo(id, payload), 'Khong the cap nhat nhom co.')
    },

    async taiDanhSachBaiTap(boLoc = this.boLocBaiTap) {
      this.boLocBaiTap = { search: String(boLoc?.search ?? '').trim(), status: String(boLoc?.status ?? '').trim() }
      return taiDanhSachTaiNguyen(this, 'baiTap', () => taiDanhSachBaiTap(this.boLocBaiTap), 'Khong the tai danh sach bai tap.')
    },
    async taiChiTietBaiTap(id) {
      return taiChiTietTaiNguyen(this, 'baiTap', id, taiChiTietBaiTap, 'Khong the tai chi tiet bai tap.')
    },
    async taoBaiTap(payload) {
      return thucHienMutation(this, 'baiTap', () => taoBaiTap(payload), 'Khong the tao bai tap.')
    },
    async capNhatBaiTap(id, payload) {
      return thucHienMutation(this, 'baiTap', () => capNhatBaiTap(id, payload), 'Khong the cap nhat bai tap.')
    },

    async taiDanhSachGiaoAnMau() {
      return taiDanhSachTaiNguyen(this, 'giaoAnMau', taiDanhSachGiaoAnMau, 'Khong the tai danh sach giao an mau.')
    },
    async taiChiTietGiaoAnMau(id) {
      return taiChiTietTaiNguyen(this, 'giaoAnMau', id, taiChiTietGiaoAnMau, 'Khong the tai chi tiet giao an mau.')
    },
    async taiNenGiaoAnMau(id) {
      return this.taiChiTietGiaoAnMau(id)
    },
    async taoGiaoAnMau(payload) {
      return thucHienMutation(this, 'giaoAnMau', () => taoGiaoAnMau(payload), 'Khong the tao giao an mau.')
    },
    async capNhatGiaoAnMau(id, payload) {
      return thucHienMutation(this, 'giaoAnMau', () => capNhatGiaoAnMau(id, payload), 'Khong the cap nhat giao an mau.')
    },
    async taoPhienBanGiaoAnMau(id, payload) {
      return thucHienMutation(this, 'giaoAnMau', () => taoPhienBanGiaoAnMau(id, payload), 'Khong the tao phien ban giao an mau.', { taiLai: false })
    },

    xoaDuLieu() {
      for (const resource of Object.values(this.$state).filter((value) => value?.danhSach && value?.soThuTu !== undefined)) {
        resource.soThuTu += 1
        resource.soThuTuChiTiet += 1
        resource.danhSach = []
        resource.chiTiet = null
        resource.dangTai = false
        resource.dangTaiChiTiet = false
        resource.loi = null
        resource.loiChiTiet = null
        resource.daTaiLanDau = false
        resource.dangMutation = false
        resource.loiMutation = null
        resource.ketQuaMutation = null
        resource.cacheHetHanLuc = 0
      }
      this.boLocBaiTap = { search: '', status: '' }
    },
  },
})

export { taoLoiAnToan }
