import { defineStore, getActivePinia } from 'pinia'
import {
  laIdHoiVienHopLe,
  taiChiTietHoiVien as taiChiTietHoiVienApi,
  taiDanhSachHoiVienDuocPhanCong,
} from '../services/hoi_vien_pt.api.js'
import {
  taiChiSoCoThe as taiChiSoCoTheApi,
  taiTienDoBaiTap as taiTienDoBaiTapApi,
  taiTienDoHoiVien as taiTienDoHoiVienApi,
} from '../services/tien_do.api.js'
import { taiKeHoachTapHoiVien as taiKeHoachTapHoiVienApi } from '../services/ke_hoach_tap_pt.api.js'
import {
  taiChiTietLichSuTapHoiVien as taiChiTietLichSuTapHoiVienApi,
  taiLichSuTapHoiVien as taiLichSuTapHoiVienApi,
} from '../services/lich_su_tap_pt.api.js'
import {
  taiDanhSachGhiChu as taiDanhSachGhiChuApi,
  themGhiChuHuanLuyen as themGhiChuHuanLuyenApi,
} from '../services/ghi_chu.api.js'

const RESOURCE_KEYS = Object.freeze([
  'danhSach',
  'chiTiet',
  'tienDo',
  'chiSoCoThe',
  'tienDoBaiTap',
  'keHoach',
  'lichSu',
  'chiTietPhien',
  'ghiChu',
  'ghiChuMutation',
])

function taoGeneration() {
  return Object.fromEntries(RESOURCE_KEYS.map((key) => [key, 0]))
}

function taoLoiAnToan(error, fallback = 'Không thể tải dữ liệu học viên.') {
  return Object.freeze({
    httpStatus: Number.isInteger(error?.httpStatus) ? error.httpStatus : null,
    code: typeof error?.code === 'string' ? error.code : null,
    message: typeof error?.message === 'string' && error.message.trim() !== ''
      ? error.message
      : fallback,
    fieldErrors: Object.fromEntries(Object.entries(error?.fieldErrors ?? {})
      .filter(([, values]) => Array.isArray(values))
      .map(([field, values]) => [field, values.filter((value) => typeof value === 'string')])),
    isNetworkError: error?.isNetworkError === true,
    ...(error?.outcomeUnknown === true ? { outcomeUnknown: true } : {}),
  })
}

function taoLoiDauVao(message = 'ID học viên không hợp lệ.') {
  return taoLoiAnToan({
    httpStatus: 404,
    code: 'MEMBER_NOT_FOUND',
    message,
  }, message)
}

function laLoiTamThoi(error) {
  return error?.isNetworkError === true
    || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

function laLoiMatPhamVi(error) {
  return error?.httpStatus === 403 || error?.httpStatus === 404
}

function layDuLieuEnvelope(phanHoi) {
  if (phanHoi !== null && typeof phanHoi === 'object' && Object.prototype.hasOwnProperty.call(phanHoi, 'data')) {
    return phanHoi.data
  }

  return phanHoi
}

function laySoId(giaTri) {
  return laIdHoiVienHopLe(giaTri) ? Number(giaTri) : null
}

function taoDoiTuongTheoId() {
  return {}
}

function taoTrangThaiLichSu() {
  return { items: [], nextCursor: null }
}

function taoTrangThaiTienDo() {
  return { overview: null, body: null, exercises: {} }
}

function layDanhSachHoiVienTuPayload(payload) {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.items)) return payload.items
  return null
}

function layDanhSachTheoPayload(payload) {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.items)) return payload.items
  return null
}

function layLichSuTheoPayload(payload, limit = 20) {
  const items = layDanhSachTheoPayload(payload)
  if (items === null) return null

  const nextCursor = payload?.next_cursor?.before_id
    ?? payload?.nextCursor?.before_id
    ?? payload?.next_cursor
    ?? (items.length >= limit && items.length > 0 ? items[items.length - 1]?.id ?? null : null)

  return { items, nextCursor: nextCursor === null ? null : Number(nextCursor) || null }
}

function layMemberIdTuAssignment(item) {
  const memberId = item?.member?.id ?? item?.member_id ?? item?.id
  return laySoId(memberId)
}

function laAssignmentHienTai(item) {
  if (item?.is_current === false) return false
  return item?.is_current === true || item?.member !== undefined
}

function laSelectedCuaSoHuu(store, memberId, generation, resource) {
  return store.hoiVienDaChonId === memberId && store.requestGeneration[resource] === generation
}

function tangGeneration(store, resource) {
  store.requestGeneration[resource] += 1
  return store.requestGeneration[resource]
}

function tangTatCaGeneration(store) {
  for (const resource of RESOURCE_KEYS) {
    store.requestGeneration[resource] += 1
  }
}

function xoaCacheHoiVien(store, memberId) {
  const id = String(memberId)
  delete store.chiTietTheoHoiVien[id]
  delete store.tienDoTheoHoiVien[id]
  delete store.keHoachTheoHoiVien[id]
  delete store.lichSuTheoHoiVien[id]
  delete store.chiTietPhienTheoHoiVien[id]
  delete store.ghiChuTheoHoiVien[id]
}

/**
 * Cleanup idempotent cho Auth Store khi logout, actor switch hoac 401 dung token.
 *
 * Input: Pinia instance hien tai (co the khong co khi module duoc import som).
 * Process: chi reset PT member state neu store da duoc khoi tao, khong import Router/Auth.
 * Output: true neu co state duoc don, false neu chua co store.
 * Side effect: invalidates moi generation de late response khong commit duoc du lieu nhay cam.
 */
export function xoaDuLieuHoiVienPtNeuDaKhoiTao(pinia = getActivePinia()) {
  if (!pinia?.state?.value?.hoi_vien_pt) {
    return false
  }

  useHoiVienPtStore(pinia).xoaDuLieu()
  return true
}

// Ten helper phu de Auth Store goi ma khong phu thuoc ten component.
export const xoaDuLieuHoiVienPTNeuDaKhoiTao = xoaDuLieuHoiVienPtNeuDaKhoiTao

export const useHoiVienPtStore = defineStore('hoi_vien_pt', {
  state: () => ({
    danhSachHoiVien: [],
    dangTaiDanhSachHoiVien: false,
    loiDanhSachHoiVien: null,
    daTaiDanhSachHoiVien: false,
    hoiVienDaChonId: null,
    chiTietHoiVien: null,
    dangTaiChiTietHoiVien: false,
    loiChiTietHoiVien: null,
    tienDo: taoTrangThaiTienDo(),
    dangTaiTienDo: false,
    loiTienDo: null,
    keHoachTap: null,
    dangTaiKeHoachTap: false,
    loiKeHoachTap: null,
    lichSuTap: taoTrangThaiLichSu(),
    dangTaiLichSuTap: false,
    loiLichSuTap: null,
    dangTaiChiTietPhien: false,
    loiChiTietPhien: null,
    chiTietPhien: null,
    danhSachGhiChu: [],
    dangTaiGhiChu: false,
    loiGhiChu: null,
    dangThemGhiChu: false,
    loiThemGhiChu: null,
    ketQuaThemGhiChu: null,
    requestGeneration: taoGeneration(),
    chiTietTheoHoiVien: taoDoiTuongTheoId(),
    tienDoTheoHoiVien: taoDoiTuongTheoId(),
    keHoachTheoHoiVien: taoDoiTuongTheoId(),
    lichSuTheoHoiVien: taoDoiTuongTheoId(),
    chiTietPhienTheoHoiVien: taoDoiTuongTheoId(),
    ghiChuTheoHoiVien: taoDoiTuongTheoId(),
  }),

  actions: {
    /** Chon Member bang ID presentation; Backend van la authority assignment. */
    chonHoiVien(memberId) {
      const id = laySoId(memberId)
      if (id === null) {
        this.xoaHoiVienDangChon()
        return false
      }

      if (this.hoiVienDaChonId === id) {
        return true
      }

      this.xoaHoiVienDangChon()
      this.hoiVienDaChonId = id
      return true
    },

    /** Kiem tra ID co nam trong inventory assignment hien tai cua PT hay khong. */
    xacMinhHoiVienConTrongPhamVi(memberId) {
      const id = laySoId(memberId)
      if (id === null) return false
      return this.danhSachHoiVien.some((item) => layMemberIdTuAssignment(item) === id && laAssignmentHienTai(item))
    },

    async taiDanhSachHoiVienDuocPhanCong({ force = false } = {}) {
      if (this.dangTaiDanhSachHoiVien || (this.daTaiDanhSachHoiVien && !force)) {
        return this.danhSachHoiVien
      }

      const generation = tangGeneration(this, 'danhSach')
      this.dangTaiDanhSachHoiVien = true
      this.loiDanhSachHoiVien = null

      try {
        const phanHoi = await taiDanhSachHoiVienDuocPhanCong()
        const payload = layDuLieuEnvelope(phanHoi)
        const danhSach = layDanhSachHoiVienTuPayload(payload)
        if (danhSach === null) {
          throw new Error('Dữ liệu danh sách học viên không hợp lệ.')
        }
        if (this.requestGeneration.danhSach !== generation) return null

        this.danhSachHoiVien = danhSach
        this.daTaiDanhSachHoiVien = true
        const selectedId = this.hoiVienDaChonId
        if (selectedId !== null && !this.xacMinhHoiVienConTrongPhamVi(selectedId)) {
          this.xuLyMatPhamVi(selectedId)
        }
        return danhSach
      } catch (error) {
        if (this.requestGeneration.danhSach !== generation) return null
        if (laLoiMatPhamVi(error)) {
          const scopeResult = this.xuLyMatPhamVi(this.hoiVienDaChonId, error)
          this.loiDanhSachHoiVien = taoLoiAnToan(error, 'Phạm vi hội viên không còn khả dụng.')
          return scopeResult
        }
        this.loiDanhSachHoiVien = taoLoiAnToan(error, 'Không thể tải danh sách học viên được phân công.')
        this.daTaiDanhSachHoiVien = true
        return null
      } finally {
        if (this.requestGeneration.danhSach === generation) this.dangTaiDanhSachHoiVien = false
      }
    },

    async taiChiTietHoiVien(memberId) {
      const id = laySoId(memberId)
      if (id === null) {
        this.loiChiTietHoiVien = taoLoiDauVao()
        this.chiTietHoiVien = null
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'chiTiet')
      this.dangTaiChiTietHoiVien = true
      this.loiChiTietHoiVien = null
      this.chiTietHoiVien = null

      try {
        const phanHoi = await taiChiTietHoiVienApi(id)
        const payload = layDuLieuEnvelope(phanHoi)
        if (payload === null || typeof payload !== 'object' || payload.member === undefined) {
          throw new Error('Dữ liệu chi tiết học viên không hợp lệ.')
        }
        if (!laSelectedCuaSoHuu(this, id, generation, 'chiTiet')) return null
        this.chiTietTheoHoiVien[String(id)] = payload
        this.chiTietHoiVien = payload
        return payload
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'chiTiet')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiChiTietHoiVien = taoLoiAnToan(error, 'Không thể tải chi tiết học viên.')
        this.chiTietHoiVien = laLoiTamThoi(error) ? this.chiTietHoiVien : null
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'chiTiet')) this.dangTaiChiTietHoiVien = false
      }
    },

    async taiTienDoHoiVien(memberId, boLoc = {}) {
      const id = laySoId(memberId)
      if (id === null) {
        this.loiTienDo = taoLoiDauVao()
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'tienDo')
      this.dangTaiTienDo = true
      this.loiTienDo = null

      try {
        const phanHoi = await taiTienDoHoiVienApi(id, boLoc)
        const payload = layDuLieuEnvelope(phanHoi)
        if (payload === null || typeof payload !== 'object') throw new Error('Dữ liệu tổng quan tiến độ không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'tienDo')) return null
        const hienTai = this.tienDoTheoHoiVien[String(id)] ?? taoTrangThaiTienDo()
        this.tienDoTheoHoiVien[String(id)] = { ...hienTai, overview: payload }
        this.tienDo = this.tienDoTheoHoiVien[String(id)]
        return payload
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'tienDo')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiTienDo = taoLoiAnToan(error, 'Không thể tải tổng quan tiến độ.')
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'tienDo')) this.dangTaiTienDo = false
      }
    },

    async taiChiSoCoThe(memberId, boLoc = {}) {
      const id = laySoId(memberId)
      if (id === null) {
        this.loiTienDo = taoLoiDauVao()
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'chiSoCoThe')
      this.dangTaiTienDo = true
      this.loiTienDo = null

      try {
        const phanHoi = await taiChiSoCoTheApi(id, boLoc)
        const payload = layDuLieuEnvelope(phanHoi)
        if (payload === null || typeof payload !== 'object') throw new Error('Dữ liệu chỉ số cơ thể không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'chiSoCoThe')) return null
        const hienTai = this.tienDoTheoHoiVien[String(id)] ?? taoTrangThaiTienDo()
        this.tienDoTheoHoiVien[String(id)] = { ...hienTai, body: payload }
        this.tienDo = this.tienDoTheoHoiVien[String(id)]
        return payload
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'chiSoCoThe')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiTienDo = taoLoiAnToan(error, 'Không thể tải chỉ số cơ thể.')
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'chiSoCoThe')) this.dangTaiTienDo = false
      }
    },

    async taiTienDoBaiTap(memberId, exerciseId, boLoc = {}) {
      const id = laySoId(memberId)
      const baiTap = Number(exerciseId)
      if (id === null || !Number.isSafeInteger(baiTap) || baiTap < 1) {
        this.loiTienDo = taoLoiDauVao('Bài tập tiến độ không hợp lệ.')
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'tienDoBaiTap')
      this.dangTaiTienDo = true
      this.loiTienDo = null

      try {
        const phanHoi = await taiTienDoBaiTapApi(id, baiTap, boLoc)
        const payload = layDuLieuEnvelope(phanHoi)
        if (payload === null || typeof payload !== 'object') throw new Error('Dữ liệu tiến độ bài tập không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'tienDoBaiTap')) return null
        const hienTai = this.tienDoTheoHoiVien[String(id)] ?? taoTrangThaiTienDo()
        this.tienDoTheoHoiVien[String(id)] = {
          ...hienTai,
          exercises: { ...hienTai.exercises, [baiTap]: payload },
        }
        this.tienDo = this.tienDoTheoHoiVien[String(id)]
        return payload
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'tienDoBaiTap')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiTienDo = taoLoiAnToan(error, 'Không thể tải tiến độ bài tập.')
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'tienDoBaiTap')) this.dangTaiTienDo = false
      }
    },

    async taiKeHoachTapHoiVien(memberId) {
      const id = laySoId(memberId)
      if (id === null) {
        this.loiKeHoachTap = taoLoiDauVao()
        this.keHoachTap = null
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'keHoach')
      this.dangTaiKeHoachTap = true
      this.loiKeHoachTap = null
      this.keHoachTap = null

      try {
        const phanHoi = await taiKeHoachTapHoiVienApi(id)
        const payload = layDuLieuEnvelope(phanHoi)
        if (payload === null || typeof payload !== 'object') throw new Error('Dữ liệu kế hoạch tập không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'keHoach')) return null
        this.keHoachTheoHoiVien[String(id)] = payload
        this.keHoachTap = payload
        return payload
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'keHoach')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiKeHoachTap = taoLoiAnToan(error, 'Không thể tải kế hoạch tập chính thức.')
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'keHoach')) this.dangTaiKeHoachTap = false
      }
    },

    async taiLichSuTapHoiVien(memberId, { limit = 20, before_id: beforeId, append = false } = {}) {
      const id = laySoId(memberId)
      if (id === null) {
        this.loiLichSuTap = taoLoiDauVao()
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const trangThaiCu = this.lichSuTheoHoiVien[String(id)] ?? taoTrangThaiLichSu()
      const cursor = beforeId ?? (append ? trangThaiCu.nextCursor : undefined)
      const generation = tangGeneration(this, 'lichSu')
      this.dangTaiLichSuTap = true
      this.loiLichSuTap = null
      if (!append) {
        this.lichSuTap = taoTrangThaiLichSu()
      }

      try {
        const phanHoi = await taiLichSuTapHoiVienApi(id, { limit, before_id: cursor })
        const payload = layDuLieuEnvelope(phanHoi)
        const moi = layLichSuTheoPayload(payload, limit)
        if (moi === null) throw new Error('Dữ liệu lịch sử buổi tập không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'lichSu')) return null
        const items = append ? [...trangThaiCu.items, ...moi.items] : moi.items
        const capNhat = { items, nextCursor: moi.nextCursor }
        this.lichSuTheoHoiVien[String(id)] = capNhat
        this.lichSuTap = capNhat
        return capNhat
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'lichSu')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiLichSuTap = taoLoiAnToan(error, 'Không thể tải lịch sử buổi tập.')
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'lichSu')) this.dangTaiLichSuTap = false
      }
    },

    async taiChiTietLichSuTapHoiVien(memberId, sessionId) {
      const id = laySoId(memberId)
      const session = Number(sessionId)
      if (id === null || !Number.isSafeInteger(session) || session < 1) {
        this.loiChiTietPhien = taoLoiDauVao()
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'chiTietPhien')
      this.dangTaiChiTietPhien = true
      this.loiChiTietPhien = null

      try {
        const phanHoi = await taiChiTietLichSuTapHoiVienApi(id, session)
        const payload = layDuLieuEnvelope(phanHoi)
        if (payload === null || typeof payload !== 'object') throw new Error('Dữ liệu chi tiết phiên tập không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'chiTietPhien')) return null
        this.chiTietPhienTheoHoiVien[String(id)] = {
          ...(this.chiTietPhienTheoHoiVien[String(id)] ?? {}),
          [session]: payload,
        }
        this.chiTietPhien = payload
        return payload
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'chiTietPhien')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiChiTietPhien = taoLoiAnToan(error, 'Không thể tải chi tiết phiên tập.')
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'chiTietPhien')) this.dangTaiChiTietPhien = false
      }
    },

    async taiDanhSachGhiChu(memberId) {
      const id = laySoId(memberId)
      if (id === null) {
        this.loiGhiChu = taoLoiDauVao()
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'ghiChu')
      this.dangTaiGhiChu = true
      this.loiGhiChu = null

      try {
        const phanHoi = await taiDanhSachGhiChuApi(id)
        const payload = layDuLieuEnvelope(phanHoi)
        const danhSach = layDanhSachTheoPayload(payload)
        if (danhSach === null) throw new Error('Dữ liệu ghi chú không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'ghiChu')) return null
        this.ghiChuTheoHoiVien[String(id)] = danhSach
        this.danhSachGhiChu = danhSach
        return danhSach
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'ghiChu')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        this.loiGhiChu = taoLoiAnToan(error, 'Không thể tải ghi chú huấn luyện.')
        return null
      } finally {
        if (laSelectedCuaSoHuu(this, id, generation, 'ghiChu')) this.dangTaiGhiChu = false
      }
    },

    async themGhiChuHuanLuyen(memberId, duLieu = {}) {
      const id = laySoId(memberId)
      if (id === null || this.dangThemGhiChu) {
        this.loiThemGhiChu = taoLoiDauVao()
        return null
      }
      if (this.hoiVienDaChonId !== id) this.chonHoiVien(id)
      const generation = tangGeneration(this, 'ghiChuMutation')
      this.dangThemGhiChu = true
      this.loiThemGhiChu = null
      this.ketQuaThemGhiChu = null

      try {
        const phanHoi = await themGhiChuHuanLuyenApi(id, duLieu)
        const payload = layDuLieuEnvelope(phanHoi)
        if (payload === null || typeof payload !== 'object') throw new Error('Dữ liệu ghi chú mới không hợp lệ.')
        if (!laSelectedCuaSoHuu(this, id, generation, 'ghiChuMutation')) return null
        this.ketQuaThemGhiChu = payload
        await this.taiDanhSachGhiChu(id)
        if (!laSelectedCuaSoHuu(this, id, generation, 'ghiChuMutation')) return null
        return payload
      } catch (error) {
        if (!laSelectedCuaSoHuu(this, id, generation, 'ghiChuMutation')) return null
        if (laLoiMatPhamVi(error)) return this.xuLyMatPhamVi(id, error)
        if (laLoiTamThoi(error)) {
          // POST khong co stable key; refetch la reconcile duy nhat, khong blind retry.
          try {
            await this.taiDanhSachGhiChu(id)
          } catch {
            // Giữ draft ở page; chỉ lưu trạng thái outcome unknown an toàn.
          }
          this.loiThemGhiChu = taoLoiAnToan({
            ...error,
            outcomeUnknown: true,
            message: 'Chưa xác định được ghi chú đã được lưu. Hãy kiểm tra lại danh sách ghi chú.',
          }, 'Chưa xác định được kết quả ghi chú.')
        } else {
          this.loiThemGhiChu = taoLoiAnToan(error, 'Không thể thêm ghi chú huấn luyện.')
        }
        return null
      } finally {
        if (this.requestGeneration.ghiChuMutation === generation) this.dangThemGhiChu = false
      }
    },

    /** Purge atomically khi 403/404 member-scoped hoac assignment list mat Member. */
    xuLyMatPhamVi(memberId, error = null) {
      const id = laySoId(memberId)
      tangTatCaGeneration(this)
      if (id !== null) xoaCacheHoiVien(this, id)
      if (id === null || this.hoiVienDaChonId === id) {
        this.hoiVienDaChonId = null
        this.chiTietHoiVien = null
        this.tienDo = taoTrangThaiTienDo()
        this.keHoachTap = null
        this.lichSuTap = taoTrangThaiLichSu()
        this.chiTietPhien = null
        this.danhSachGhiChu = []
      }
      this.loiChiTietHoiVien = error ? taoLoiAnToan(error, 'Học viên không còn trong phạm vi phân công hiện tại.') : null
      this.loiTienDo = error ? taoLoiAnToan(error, 'Học viên không còn trong phạm vi phân công hiện tại.') : null
      this.loiKeHoachTap = error ? taoLoiAnToan(error, 'Học viên không còn trong phạm vi phân công hiện tại.') : null
      this.loiLichSuTap = error ? taoLoiAnToan(error, 'Học viên không còn trong phạm vi phân công hiện tại.') : null
      this.loiGhiChu = error ? taoLoiAnToan(error, 'Học viên không còn trong phạm vi phân công hiện tại.') : null
      this.loiChiTietPhien = error ? taoLoiAnToan(error, 'Học viên không còn trong phạm vi phân công hiện tại.') : null
      this.loiThemGhiChu = error ? taoLoiAnToan(error, 'Học viên không còn trong phạm vi phân công hiện tại.') : null
      this.dangTaiDanhSachHoiVien = false
      this.dangTaiChiTietHoiVien = false
      this.dangTaiTienDo = false
      this.dangTaiKeHoachTap = false
      this.dangTaiLichSuTap = false
      this.dangTaiChiTietPhien = false
      this.dangTaiGhiChu = false
      this.dangThemGhiChu = false
      return { scopeLost: true, memberId: id }
    },

    xoaHoiVienDangChon() {
      tangTatCaGeneration(this)
      if (this.hoiVienDaChonId !== null) xoaCacheHoiVien(this, this.hoiVienDaChonId)
      this.hoiVienDaChonId = null
      this.chiTietHoiVien = null
      this.tienDo = taoTrangThaiTienDo()
      this.keHoachTap = null
      this.lichSuTap = taoTrangThaiLichSu()
      this.chiTietPhien = null
      this.danhSachGhiChu = []
      this.dangTaiChiTietHoiVien = false
      this.dangTaiTienDo = false
      this.dangTaiKeHoachTap = false
      this.dangTaiLichSuTap = false
      this.dangTaiChiTietPhien = false
      this.dangTaiGhiChu = false
      this.loiChiTietHoiVien = null
      this.loiTienDo = null
      this.loiKeHoachTap = null
      this.loiLichSuTap = null
      this.loiChiTietPhien = null
      this.loiGhiChu = null
      this.dangThemGhiChu = false
      this.loiThemGhiChu = null
      this.ketQuaThemGhiChu = null
    },

    xoaDuLieu() {
      tangTatCaGeneration(this)
      this.danhSachHoiVien = []
      this.dangTaiDanhSachHoiVien = false
      this.loiDanhSachHoiVien = null
      this.daTaiDanhSachHoiVien = false
      this.hoiVienDaChonId = null
      this.chiTietHoiVien = null
      this.dangTaiChiTietHoiVien = false
      this.loiChiTietHoiVien = null
      this.tienDo = taoTrangThaiTienDo()
      this.dangTaiTienDo = false
      this.loiTienDo = null
      this.keHoachTap = null
      this.dangTaiKeHoachTap = false
      this.loiKeHoachTap = null
      this.lichSuTap = taoTrangThaiLichSu()
      this.dangTaiLichSuTap = false
      this.loiLichSuTap = null
      this.dangTaiChiTietPhien = false
      this.loiChiTietPhien = null
      this.chiTietPhien = null
      this.danhSachGhiChu = []
      this.dangTaiGhiChu = false
      this.loiGhiChu = null
      this.dangThemGhiChu = false
      this.loiThemGhiChu = null
      this.ketQuaThemGhiChu = null
      this.chiTietTheoHoiVien = taoDoiTuongTheoId()
      this.tienDoTheoHoiVien = taoDoiTuongTheoId()
      this.keHoachTheoHoiVien = taoDoiTuongTheoId()
      this.lichSuTheoHoiVien = taoDoiTuongTheoId()
      this.chiTietPhienTheoHoiVien = taoDoiTuongTheoId()
      this.ghiChuTheoHoiVien = taoDoiTuongTheoId()
    },
  },
})

export { layDuLieuEnvelope, taoLoiAnToan }
