import { defineStore, getActivePinia } from 'pinia'
import {
  ketThucPhanCong,
  laIdHoSoHopLe,
  phanCongLai,
  taiChiTietPhanCong,
  taiDanhSachPhanCong,
  taoPhanCong,
} from '../services/phan_cong_pt.api.js'

const BO_LOC_MAC_DINH = Object.freeze({
  member_id: '',
  trainer_id: '',
  current: '',
  per_page: 20,
})

const PHAN_TRANG_MAC_DINH = Object.freeze({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
})

function taoLoiAnToan(error, message = 'Không thể tải dữ liệu phân công PT.') {
  return Object.freeze({
    httpStatus: Number.isInteger(error?.httpStatus) ? error.httpStatus : null,
    code: typeof error?.code === 'string' ? error.code : null,
    message: typeof error?.message === 'string' && error.message.trim() !== ''
      ? error.message
      : message,
    fieldErrors: Object.fromEntries(Object.entries(error?.fieldErrors ?? {})
      .filter(([, values]) => Array.isArray(values))
      .map(([field, values]) => [field, values.filter((value) => typeof value === 'string')])),
    isNetworkError: error?.isNetworkError === true,
    ...(error?.outcomeUnknown === true ? { outcomeUnknown: true } : {}),
  })
}

function taoLoiKhongBietKetQua(message) {
  return taoLoiAnToan({
    code: 'ASSIGNMENT_OUTCOME_UNKNOWN',
    message,
    outcomeUnknown: true,
  }, message)
}

function laLoiTamThoi(error) {
  return error?.isNetworkError === true
    || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

function laPhanTrangHopLe(phanTrang) {
  return phanTrang !== null
    && typeof phanTrang === 'object'
    && Number.isInteger(phanTrang.current_page)
    && phanTrang.current_page >= 1
    && Number.isInteger(phanTrang.per_page)
    && phanTrang.per_page >= 1
    && Number.isInteger(phanTrang.total)
    && phanTrang.total >= 0
    && Number.isInteger(phanTrang.last_page)
    && phanTrang.last_page >= 1
    && phanTrang.current_page <= phanTrang.last_page
}

function laAssignmentHopLe(assignment) {
  return assignment !== null
    && typeof assignment === 'object'
    && laIdHoSoHopLe(assignment.id)
    && assignment.member !== null
    && typeof assignment.member === 'object'
    && laIdHoSoHopLe(assignment.member.id)
    && typeof assignment.member.code === 'string'
    && typeof assignment.member.name === 'string'
    && assignment.trainer !== null
    && typeof assignment.trainer === 'object'
    && laIdHoSoHopLe(assignment.trainer.id)
    && typeof assignment.trainer.code === 'string'
    && typeof assignment.trainer.name === 'string'
    && typeof assignment.trainer.status === 'string'
    && typeof assignment.start_at === 'string'
    && !Number.isNaN(Date.parse(assignment.start_at))
    && (assignment.end_at === null || (typeof assignment.end_at === 'string' && !Number.isNaN(Date.parse(assignment.end_at))))
    && (assignment.reason === null || typeof assignment.reason === 'string')
    && typeof assignment.is_current === 'boolean'
    && typeof assignment.created_at === 'string'
    && typeof assignment.updated_at === 'string'
}

function laDanhSachHopLe(phanHoi) {
  return Array.isArray(phanHoi?.data?.items)
    && laPhanTrangHopLe(phanHoi?.data?.pagination)
    && phanHoi.data.items.every(laAssignmentHopLe)
}

function layBoLoc(boLoc = BO_LOC_MAC_DINH) {
  const memberId = boLoc?.member_id ?? ''
  const trainerId = boLoc?.trainer_id ?? ''
  return {
    member_id: memberId === '' ? '' : Number(memberId),
    trainer_id: trainerId === '' ? '' : Number(trainerId),
    current: boLoc?.current === true || boLoc?.current === false ? boLoc.current : '',
    per_page: Number.isInteger(Number(boLoc?.per_page)) ? Number(boLoc.per_page) : 20,
  }
}

function taoLoiDanhSachKhongHopLe() {
  return { code: 'ASSIGNMENT_LIST_RESPONSE_INVALID', message: 'Dữ liệu danh sách phân công không hợp lệ.' }
}

function taoLoiChiTietKhongHopLe() {
  return { code: 'ASSIGNMENT_DETAIL_RESPONSE_INVALID', message: 'Dữ liệu chi tiết phân công không hợp lệ.' }
}

function taoLoiTaoKhongHopLe(field) {
  return {
    httpStatus: 422,
    code: 'INVALID_ASSIGNMENT_REQUEST',
    message: 'Member và PT phải là hồ sơ hợp lệ.',
    fieldErrors: { [field]: ['Hồ sơ không hợp lệ.'] },
    isNetworkError: false,
  }
}

function laKhoangGiongNhau(giaTriMot, giaTriHai) {
  if (giaTriMot === null || giaTriHai === null) return giaTriMot === giaTriHai
  const mot = Date.parse(giaTriMot)
  const hai = Date.parse(giaTriHai)
  return !Number.isNaN(mot) && !Number.isNaN(hai) && mot === hai
}

function layIdCungCap(duLieu, truong) {
  if (!laIdHoSoHopLe(duLieu?.[truong])) {
    throw taoLoiTaoKhongHopLe(truong)
  }

  return Number(duLieu[truong])
}

function layThoiGianKetThucChuan(duLieu) {
  const giaTri = duLieu?.end_at
  return giaTri === undefined || giaTri === null || giaTri === '' ? null : giaTri
}

function laMutationCuaSoHuu(store, sequence) {
  return sequence === store.soThuTuMutation
}

/**
 * Doc day du assignment cung Member/PT ma khong cham vao list/detail dang hien thi.
 *
 * Dau vao: payload create da copy va sequence cua mutation dang cho.
 * Cach hoat dong: GET page 1..last_page voi per_page 100, validate tung envelope/DTO,
 * kiem tra sequence sau moi await va thu ID moi trong mot inventory local.
 * Ket qua: inventory immutable gom items va ID da ton tai; null neu sequence da stale.
 * Side effect: chi GET read-only; khong thay doi danhSach, boLoc, phanTrang hay chiTiet.
 */
async function docInventoryCungCap(duLieu, sequence, store) {
  const memberId = layIdCungCap(duLieu, 'member_id')
  const trainerId = layIdCungCap(duLieu, 'trainer_id')
  const items = []
  const ids = new Set()
  let trang = 1
  let tongSoTrang = null

  while (true) {
    if (sequence !== store.soThuTuMutation) return null

    const phanHoi = await taiDanhSachPhanCong({
      member_id: memberId,
      trainer_id: trainerId,
      per_page: 100,
      page: trang,
    })

    if (sequence !== store.soThuTuMutation) return null
    if (!laDanhSachHopLe(phanHoi)) throw taoLoiDanhSachKhongHopLe()

    const phanTrang = phanHoi.data.pagination
    if (phanTrang.current_page !== trang) throw taoLoiDanhSachKhongHopLe()
    if (tongSoTrang === null) {
      tongSoTrang = phanTrang.last_page
    } else if (tongSoTrang !== phanTrang.last_page) {
      throw taoLoiDanhSachKhongHopLe()
    }

    for (const item of phanHoi.data.items) {
      if (item.member.id !== memberId || item.trainer.id !== trainerId) continue
      const id = Number(item.id)
      if (ids.has(id)) throw taoLoiDanhSachKhongHopLe()
      ids.add(id)
      items.push(item)
    }

    if (trang >= tongSoTrang) break
    trang += 1
  }

  return Object.freeze({
    items: Object.freeze(items),
    ids: Object.freeze([...ids]),
  })
}

export function xoaDuLieuPhanCongPtNeuDaKhoiTao(pinia = getActivePinia()) {
  if (!pinia?.state?.value?.phan_cong_pt) {
    return false
  }

  usePhanCongPtStore(pinia).xoaDuLieu()
  return true
}

export const usePhanCongPtStore = defineStore('phan_cong_pt', {
  state: () => ({
    danhSach: [],
    boLoc: { ...BO_LOC_MAC_DINH },
    phanTrang: { ...PHAN_TRANG_MAC_DINH },
    dangTai: false,
    loiTaiDanhSach: null,
    daTaiLanDau: false,
    soThuTuDanhSach: 0,
    chiTiet: null,
    dangTaiChiTiet: false,
    loiTaiChiTiet: null,
    soThuTuChiTiet: 0,
    dangMutation: false,
    loaiMutation: null,
    loiMutation: null,
    ketQuaMutation: null,
    soThuTuMutation: 0,
  }),

  actions: {
    async taiDanhSachPhanCong({ boLoc = this.boLoc, trang = this.phanTrang.current_page } = {}) {
      const boLocDaChuanHoa = layBoLoc(boLoc)
      const trangYeuCau = Number.isInteger(Number(trang)) && Number(trang) >= 1 ? Number(trang) : 1
      const sequence = ++this.soThuTuDanhSach
      this.dangTai = true
      this.loiTaiDanhSach = null

      try {
        const phanHoi = await taiDanhSachPhanCong({ ...boLocDaChuanHoa, page: trangYeuCau })
        if (!laDanhSachHopLe(phanHoi)) {
          throw taoLoiDanhSachKhongHopLe()
        }
        if (sequence !== this.soThuTuDanhSach) return null
        this.danhSach = phanHoi.data.items
        this.boLoc = boLocDaChuanHoa
        this.phanTrang = { ...phanHoi.data.pagination }
        this.daTaiLanDau = true
        return phanHoi.data
      } catch (error) {
        if (sequence === this.soThuTuDanhSach) {
          this.loiTaiDanhSach = taoLoiAnToan(error)
          this.daTaiLanDau = true
        }
        return null
      } finally {
        if (sequence === this.soThuTuDanhSach) this.dangTai = false
      }
    },

    async taiChiTietPhanCong(assignmentId) {
      const sequence = ++this.soThuTuChiTiet
      const id = Number(assignmentId)
      const oldId = Number(this.chiTiet?.id)
      this.dangTaiChiTiet = true
      this.loiTaiChiTiet = null
      if (!laIdHoSoHopLe(assignmentId) || oldId !== id) this.chiTiet = null
      if (!laIdHoSoHopLe(assignmentId)) {
        this.loiTaiChiTiet = taoLoiAnToan({ httpStatus: 404, code: 'ASSIGNMENT_NOT_FOUND' }, 'Không thể truy cập phân công này.')
        this.dangTaiChiTiet = false
        return null
      }

      try {
        const phanHoi = await taiChiTietPhanCong(id)
        if (!laAssignmentHopLe(phanHoi?.data)) throw taoLoiChiTietKhongHopLe()
        if (sequence !== this.soThuTuChiTiet) return null
        this.chiTiet = phanHoi.data
        return phanHoi.data
      } catch (error) {
        if (sequence !== this.soThuTuChiTiet) return null
        if (error?.httpStatus === 403 || error?.httpStatus === 404 || !laLoiTamThoi(error)) this.chiTiet = null
        this.loiTaiChiTiet = taoLoiAnToan(error, 'Không thể tải chi tiết phân công.')
        return null
      } finally {
        if (sequence === this.soThuTuChiTiet) this.dangTaiChiTiet = false
      }
    },

    async lamMoiSauMutation(assignmentId, sequence) {
      if (!laMutationCuaSoHuu(this, sequence)) return null

      await this.taiDanhSachPhanCong({ boLoc: this.boLoc, trang: this.phanTrang.current_page })
      if (!laMutationCuaSoHuu(this, sequence)) return null
      if (!laIdHoSoHopLe(assignmentId)) return null
      if (!laMutationCuaSoHuu(this, sequence)) return null

      const chiTiet = await this.taiChiTietPhanCong(assignmentId)
      if (!laMutationCuaSoHuu(this, sequence)) return null
      return chiTiet
    },

    async doiSoatTao(duLieu, baseline, sequence) {
      if (!laMutationCuaSoHuu(this, sequence)) return null

      const inventory = await docInventoryCungCap(duLieu, sequence, this)
      if (inventory === null || !laMutationCuaSoHuu(this, sequence)) return null

      const idsTruocKhiTao = new Set(baseline.ids)
      const endAt = layThoiGianKetThucChuan(duLieu)
      const coStartAt = duLieu.start_at !== undefined
        && duLieu.start_at !== null
        && duLieu.start_at !== ''
      const matches = inventory.items.filter((item) => (
        !idsTruocKhiTao.has(Number(item.id))
        && item.member.id === Number(duLieu.member_id)
        && item.trainer.id === Number(duLieu.trainer_id)
        && laKhoangGiongNhau(item.end_at, endAt)
        && (!coStartAt || laKhoangGiongNhau(item.start_at, duLieu.start_at))
      ))

      if (matches.length !== 1) return null
      if (!laMutationCuaSoHuu(this, sequence)) return null

      await this.lamMoiSauMutation(matches[0].id, sequence)
      if (!laMutationCuaSoHuu(this, sequence)) return null
      this.ketQuaMutation = matches[0]
      if (!laMutationCuaSoHuu(this, sequence)) return null
      return matches[0]
    },

    async taoMoiPhanCong(duLieu) {
      if (this.dangMutation) return null
      const sequence = ++this.soThuTuMutation
      this.dangMutation = true
      this.loaiMutation = 'create'
      this.loiMutation = null
      this.ketQuaMutation = null
      const payload = Object.freeze({ ...(duLieu ?? {}) })
      let baseline = null
      let postDaGui = false
      try {
        try {
          baseline = await docInventoryCungCap(payload, sequence, this)
        } catch (error) {
          if (laMutationCuaSoHuu(this, sequence)) {
            this.loiMutation = taoLoiAnToan(error, 'Không thể kiểm tra phân công hiện tại. Vui lòng thử lại sau.')
          }
          return null
        }

        if (baseline === null || !laMutationCuaSoHuu(this, sequence)) return null
        if (!laMutationCuaSoHuu(this, sequence)) return null

        postDaGui = true
        const phanHoi = await taoPhanCong(payload)
        if (!laMutationCuaSoHuu(this, sequence)) return null
        if (!laAssignmentHopLe(phanHoi?.data)) throw taoLoiChiTietKhongHopLe()
        await this.lamMoiSauMutation(phanHoi.data.id, sequence)
        if (!laMutationCuaSoHuu(this, sequence)) return null
        this.ketQuaMutation = phanHoi.data
        this.loiMutation = null
        return phanHoi.data
      } catch (error) {
        if (!laMutationCuaSoHuu(this, sequence)) return null
        const canDoiSoatSauKhiGui = postDaGui
          && (laLoiTamThoi(error) || error?.code === 'ASSIGNMENT_DETAIL_RESPONSE_INVALID')
        if (canDoiSoatSauKhiGui) {
          let reconciled = null
          try {
            reconciled = await this.doiSoatTao(payload, baseline, sequence)
          } catch {
            reconciled = null
          }
          if (!laMutationCuaSoHuu(this, sequence)) return null
          if (reconciled !== null) {
            this.loiMutation = null
            return reconciled
          }
          this.loiMutation = taoLoiKhongBietKetQua('Chưa xác định được kết quả tạo phân công. Không tự động gửi lại.')
        } else {
          if (error?.httpStatus === 409) {
            if (!laMutationCuaSoHuu(this, sequence)) return null
            await this.taiDanhSachPhanCong({ boLoc: this.boLoc, trang: this.phanTrang.current_page })
            if (!laMutationCuaSoHuu(this, sequence)) return null
          }
          if (!laMutationCuaSoHuu(this, sequence)) return null
          this.loiMutation = taoLoiAnToan(error, 'Không thể tạo phân công PT.')
        }
        return null
      } finally {
        if (laMutationCuaSoHuu(this, sequence)) this.dangMutation = false
      }
    },

    async ketThucPhanCong(assignmentId, duLieu = {}) {
      if (this.dangMutation || !laIdHoSoHopLe(assignmentId)) return null
      const sequence = ++this.soThuTuMutation
      this.dangMutation = true
      this.loaiMutation = 'end'
      this.loiMutation = null
      this.ketQuaMutation = null
      try {
        const phanHoi = await ketThucPhanCong(assignmentId, duLieu)
        if (!laMutationCuaSoHuu(this, sequence)) return null
        if (!laAssignmentHopLe(phanHoi?.data)) throw taoLoiChiTietKhongHopLe()
        const refreshed = await this.lamMoiSauMutation(assignmentId, sequence)
        if (!laMutationCuaSoHuu(this, sequence)) return null
        this.ketQuaMutation = refreshed ?? phanHoi.data
        this.loiMutation = null
        return this.ketQuaMutation
      } catch (error) {
        if (!laMutationCuaSoHuu(this, sequence)) return null
        if (laLoiTamThoi(error)) {
          if (!laMutationCuaSoHuu(this, sequence)) return null
          const current = await this.taiChiTietPhanCong(assignmentId)
          if (!laMutationCuaSoHuu(this, sequence)) return null
          if (current?.end_at !== null && current?.end_at !== undefined) {
            if (!laMutationCuaSoHuu(this, sequence)) return null
            this.ketQuaMutation = current
            this.loiMutation = null
            return current
          }
          if (!laMutationCuaSoHuu(this, sequence)) return null
          this.loiMutation = taoLoiKhongBietKetQua('Chưa xác định được kết quả kết thúc phân công. Không tự động gửi lại.')
        } else {
          if (error?.httpStatus === 409) {
            if (!laMutationCuaSoHuu(this, sequence)) return null
            await this.lamMoiSauMutation(assignmentId, sequence)
            if (!laMutationCuaSoHuu(this, sequence)) return null
          }
          if (!laMutationCuaSoHuu(this, sequence)) return null
          this.loiMutation = taoLoiAnToan(error, 'Không thể kết thúc phân công PT.')
        }
        return null
      } finally {
        if (laMutationCuaSoHuu(this, sequence)) this.dangMutation = false
      }
    },

    async phanCongLai(assignmentId, duLieu = {}) {
      if (this.dangMutation || !laIdHoSoHopLe(assignmentId)) return null
      const sequence = ++this.soThuTuMutation
      this.dangMutation = true
      this.loaiMutation = 'reassign'
      this.loiMutation = null
      this.ketQuaMutation = null
      try {
        const phanHoi = await phanCongLai(assignmentId, duLieu)
        if (!laMutationCuaSoHuu(this, sequence)) return null
        if (!laAssignmentHopLe(phanHoi?.data)) throw taoLoiChiTietKhongHopLe()
        const refreshed = await this.lamMoiSauMutation(phanHoi.data.id, sequence)
        if (!laMutationCuaSoHuu(this, sequence)) return null
        this.ketQuaMutation = refreshed ?? phanHoi.data
        this.loiMutation = null
        return this.ketQuaMutation
      } catch (error) {
        if (!laMutationCuaSoHuu(this, sequence)) return null
        if (laLoiTamThoi(error)) {
          if (!laMutationCuaSoHuu(this, sequence)) return null
          const old = await this.taiChiTietPhanCong(assignmentId)
          if (!laMutationCuaSoHuu(this, sequence)) return null
          if (!old) {
            this.loiMutation = taoLoiKhongBietKetQua('Chưa xác định được kết quả đổi PT. Không tự động gửi lại.')
            return null
          }
          if (!laMutationCuaSoHuu(this, sequence)) return null
          const list = await taiDanhSachPhanCong({ member_id: old.member.id, per_page: 100 })
          if (!laMutationCuaSoHuu(this, sequence)) return null
          const matches = laDanhSachHopLe(list)
            ? list.data.items.filter((item) => item.trainer.id === Number(duLieu.trainer_id)
              && laKhoangGiongNhau(item.start_at, duLieu.start_at))
            : []
          if (old?.end_at !== null && laKhoangGiongNhau(old.end_at, duLieu.start_at) && matches.length === 1) {
            if (!laMutationCuaSoHuu(this, sequence)) return null
            this.ketQuaMutation = matches[0]
            this.loiMutation = null
            return matches[0]
          }
          if (!laMutationCuaSoHuu(this, sequence)) return null
          this.loiMutation = taoLoiKhongBietKetQua('Chưa xác định được kết quả đổi PT. Không tự động gửi lại.')
        } else {
          if (error?.httpStatus === 409) {
            if (!laMutationCuaSoHuu(this, sequence)) return null
            await this.lamMoiSauMutation(assignmentId, sequence)
            if (!laMutationCuaSoHuu(this, sequence)) return null
          }
          if (!laMutationCuaSoHuu(this, sequence)) return null
          this.loiMutation = taoLoiAnToan(error, 'Không thể đổi PT cho phân công.')
        }
        return null
      } finally {
        if (laMutationCuaSoHuu(this, sequence)) this.dangMutation = false
      }
    },

    xoaChiTiet() {
      this.soThuTuChiTiet += 1
      this.chiTiet = null
      this.dangTaiChiTiet = false
      this.loiTaiChiTiet = null
    },

    xoaDuLieu() {
      this.soThuTuDanhSach += 1
      this.soThuTuChiTiet += 1
      this.soThuTuMutation += 1
      this.danhSach = []
      this.boLoc = { ...BO_LOC_MAC_DINH }
      this.phanTrang = { ...PHAN_TRANG_MAC_DINH }
      this.dangTai = false
      this.loiTaiDanhSach = null
      this.daTaiLanDau = false
      this.chiTiet = null
      this.dangTaiChiTiet = false
      this.loiTaiChiTiet = null
      this.dangMutation = false
      this.loaiMutation = null
      this.loiMutation = null
      this.ketQuaMutation = null
    },
  },
})

export { laAssignmentHopLe, laDanhSachHopLe }
