import { defineStore, getActivePinia } from 'pinia'
import { taoDeXuatKeHoach as taoDeXuatKeHoachApi, taiDanhSachDeXuat as taiDanhSachDeXuatApi, taoBodyDeXuat } from '../services/de_xuat.api.js'
import { taoKhoaIdempotency } from '../utils/khoa_idempotency.js'
import { laIdHoiVienHopLe } from '../services/hoi_vien_pt.api.js'

function taoBanNhap() {
  return {
    change_type: 'TAO_MOI',
    title: '',
    explanation: '',
    effective_from: '',
    plan: {
      name: '',
      goal: '',
      template_id: null,
      days: [{
        order: 1,
        weekday: 2,
        name: 'Ngày tập 1',
        estimated_minutes: 60,
        exercises: [{
          exercise_id: '',
          order: 1,
          target_sets: 3,
          min_reps: 8,
          max_reps: 12,
          target_weight_kg: null,
          rest_seconds: 90,
          notes: null,
        }],
      }],
    },
  }
}

function layPayload(phanHoi) {
  return phanHoi !== null && typeof phanHoi === 'object' && Object.prototype.hasOwnProperty.call(phanHoi, 'data')
    ? phanHoi.data
    : phanHoi
}

function taoLoiAnToan(error, fallback) {
  return {
    httpStatus: Number.isInteger(error?.httpStatus) ? error.httpStatus : null,
    code: typeof error?.code === 'string' ? error.code : null,
    message: typeof error?.message === 'string' && error.message.trim() !== '' ? error.message : fallback,
    fieldErrors: Object.fromEntries(Object.entries(error?.fieldErrors ?? {})
      .filter(([, values]) => Array.isArray(values))
      .map(([field, values]) => [field, values.filter((value) => typeof value === 'string')])),
    isNetworkError: error?.isNetworkError === true,
    ...(error?.outcomeUnknown === true ? { outcomeUnknown: true } : {}),
  }
}

function laLoiTamThoi(error) {
  return error?.isNetworkError === true
    || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

function laLoiMatPhamVi(error) {
  return error?.httpStatus === 403 || error?.httpStatus === 404
}

function saoChepDongBang(duLieu) {
  const banSao = JSON.parse(JSON.stringify(duLieu))
  const dongBangSau = (giaTri) => {
    if (giaTri !== null && typeof giaTri === 'object') {
      Object.freeze(giaTri)
      Object.values(giaTri).forEach(dongBangSau)
    }
    return giaTri
  }
  return dongBangSau(banSao)
}

function taoDanhSach(payload) {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.items)) return payload.items
  return null
}

/**
 * Xoa proposal store tu cleanup PT/Auth chi neu Pinia da khoi tao store.
 * Input: Pinia active hien tai; khong phu thuoc vao Router/Auth.
 * Process: chi goi purge neu state co store id `de_xuat`.
 * Output: true neu store duoc don, false neu chua ton tai.
 * Side effect: tang context generation de late proposal response khong commit duoc.
 */
export function xoaDuLieuDeXuatPtNeuDaKhoiTao(pinia = getActivePinia()) {
  if (!pinia?.state?.value?.de_xuat) return false
  useDeXuatStore(pinia).xoaDuLieu()
  return true
}

export const useDeXuatStore = defineStore('de_xuat', {
  state: () => ({
    memberId: null,
    contextGeneration: 0,
    readGeneration: 0,
    mutationGeneration: 0,
    danhSachDeXuat: [],
    dangTaiDanhSachDeXuat: false,
    loiTaiDanhSachDeXuat: null,
    banNhap: taoBanNhap(),
    dangTaoDeXuat: false,
    loiTaoDeXuat: null,
    thaoTacDangCho: null,
    ketQuaDeXuat: null,
  }),

  actions: {
    /** Chon Member theo ID route; doi Member se purge proposal/draft/key cu. */
    chonHoiVien(memberId) {
      const id = laIdHoiVienHopLe(memberId) ? Number(memberId) : null
      if (id === this.memberId) return id !== null
      this.xoaDuLieu()
      this.memberId = id
      return id !== null
    },

    /** Tai proposals chi trong assignment scope do Backend xac nhan. */
    async taiDanhSachDeXuat(memberId = this.memberId) {
      const id = laIdHoiVienHopLe(memberId) ? Number(memberId) : null
      if (id === null || this.memberId !== id) return null
      const context = this.contextGeneration
      const generation = ++this.readGeneration
      this.dangTaiDanhSachDeXuat = true
      this.loiTaiDanhSachDeXuat = null

      try {
        const phanHoi = await taiDanhSachDeXuatApi(id)
        const danhSach = taoDanhSach(layPayload(phanHoi))
        if (danhSach === null) throw new Error('Dữ liệu đề xuất kế hoạch không hợp lệ.')
        if (this.contextGeneration !== context || this.readGeneration !== generation || this.memberId !== id) return null
        this.danhSachDeXuat = danhSach
        return danhSach
      } catch (error) {
        if (this.contextGeneration !== context || this.readGeneration !== generation || this.memberId !== id) return null
        if (laLoiMatPhamVi(error)) {
          this.xoaDuLieu()
          return { scopeLost: true, memberId: id }
        }
        this.loiTaiDanhSachDeXuat = taoLoiAnToan(error, 'Không thể tải danh sách đề xuất.')
        return null
      } finally {
        if (this.contextGeneration === context && this.readGeneration === generation) {
          this.dangTaiDanhSachDeXuat = false
        }
      }
    },

    /** Tao proposal tu draft; unknown outcome luon giu nguyen body va UUIDv4. */
    async taoDeXuatKeHoach() {
      if (this.dangTaoDeXuat || this.memberId === null) return null
      if (this.thaoTacDangCho?.outcomeUnknown) return this.thuLaiDeXuat()

      let body
      let khoa
      try {
        body = taoBodyDeXuat(this.banNhap)
        khoa = taoKhoaIdempotency()
      } catch (error) {
        this.loiTaoDeXuat = taoLoiAnToan(error, 'Không thể chuẩn bị đề xuất.')
        return null
      }

      const thaoTac = Object.freeze({
        memberId: this.memberId,
        body: saoChepDongBang(body),
        idempotencyKey: khoa,
        outcomeUnknown: false,
      })
      this.thaoTacDangCho = thaoTac
      return this.guiThaoTacDeXuat(thaoTac)
    },

    /** Refetch danh sach truoc; danh sach khong co key nen retry chi duoc dung action goc. */
    async thuLaiDeXuat() {
      const thaoTac = this.thaoTacDangCho
      if (!thaoTac?.outcomeUnknown || this.dangTaoDeXuat || this.memberId !== thaoTac.memberId) return null
      const context = this.contextGeneration
      const danhSach = await this.taiDanhSachDeXuat(thaoTac.memberId)
      if (danhSach?.scopeLost) return danhSach
      if (this.contextGeneration !== context || this.memberId !== thaoTac.memberId) return null
      if (danhSach === null) return null
      return this.guiThaoTacDeXuat(thaoTac)
    },

    async guiThaoTacDeXuat(thaoTac) {
      if (this.memberId !== thaoTac.memberId || this.thaoTacDangCho?.idempotencyKey !== thaoTac.idempotencyKey) return null
      const context = this.contextGeneration
      const generation = ++this.mutationGeneration
      this.dangTaoDeXuat = true
      this.loiTaoDeXuat = null
      this.ketQuaDeXuat = null

      try {
        const phanHoi = await taoDeXuatKeHoachApi(thaoTac.memberId, thaoTac.body, thaoTac.idempotencyKey)
        const ketQua = layPayload(phanHoi)
        if (this.contextGeneration !== context || this.mutationGeneration !== generation || this.memberId !== thaoTac.memberId) return null
        this.ketQuaDeXuat = ketQua
        if (ketQua?.id !== undefined) {
          this.danhSachDeXuat = [ketQua, ...this.danhSachDeXuat.filter((item) => item?.id !== ketQua.id)].slice(0, 100)
        }
        this.thaoTacDangCho = null
        this.banNhap = taoBanNhap()
        return ketQua
      } catch (error) {
        if (this.contextGeneration !== context || this.mutationGeneration !== generation || this.memberId !== thaoTac.memberId) return null
        if (laLoiMatPhamVi(error)) {
          this.xoaDuLieu()
          return { scopeLost: true, memberId: thaoTac.memberId }
        }
        this.loiTaoDeXuat = taoLoiAnToan(error, 'Không thể gửi đề xuất kế hoạch.')
        if (laLoiTamThoi(error)) {
          this.thaoTacDangCho = Object.freeze({ ...thaoTac, outcomeUnknown: true })
          this.loiTaoDeXuat.outcomeUnknown = true
          await this.taiDanhSachDeXuat(thaoTac.memberId)
          if (this.memberId !== thaoTac.memberId || this.contextGeneration !== context) {
            return { scopeLost: true, memberId: thaoTac.memberId }
          }
          return null
        }
        this.thaoTacDangCho = null
        return null
      } finally {
        if (this.contextGeneration === context && this.memberId === thaoTac.memberId) {
          this.dangTaoDeXuat = false
        }
      }
    },

    /** Xoa toan bo proposals, draft, idempotency va request generation khi scope mat. */
    xoaDuLieu() {
      this.contextGeneration += 1
      this.readGeneration += 1
      this.mutationGeneration += 1
      this.memberId = null
      this.danhSachDeXuat = []
      this.dangTaiDanhSachDeXuat = false
      this.loiTaiDanhSachDeXuat = null
      this.banNhap = taoBanNhap()
      this.dangTaoDeXuat = false
      this.loiTaoDeXuat = null
      this.thaoTacDangCho = null
      this.ketQuaDeXuat = null
    },
  },
})
