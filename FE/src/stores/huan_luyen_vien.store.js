import { defineStore, getActivePinia } from 'pinia'
import {
  CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN,
  onboardTaiKhoanHuanLuyenVien,
  taiHoSoHuanLuyenVien as taiHoSoApi,
  taoHuanLuyenVien as taoHuanLuyenVienApi,
} from '../services/huan_luyen_vien.api.js'
import { taoKhoaIdempotency } from '../utils/khoa_idempotency.js'

function taoLoiAnToan(error, message = 'Không thể xử lý dữ liệu Huấn luyện viên.') {
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

function laIdHopLe(giaTri) {
  return typeof giaTri === 'number'
    ? Number.isSafeInteger(giaTri) && giaTri > 0
    : typeof giaTri === 'string' && /^[1-9]\d*$/.test(giaTri.trim())
}

function laHoSoHopLe(duLieu) {
  return duLieu !== null
    && typeof duLieu === 'object'
    && laIdHopLe(duLieu.account_id)
    && laIdHopLe(duLieu.trainer_profile_id)
    && typeof duLieu.trainer_code === 'string'
    && duLieu.trainer_code.trim() !== ''
    && typeof duLieu.status === 'string'
    && (duLieu.introduction === null || typeof duLieu.introduction === 'string')
    && (duLieu.specialties === null || typeof duLieu.specialties === 'string')
    && typeof duLieu.updated_at === 'string'
    && !Number.isNaN(Date.parse(duLieu.updated_at))
}

function laKetQuaOnboardingHopLe(phanHoi) {
  const duLieu = phanHoi?.data
  return duLieu !== null
    && typeof duLieu === 'object'
    && laIdHopLe(duLieu.account?.id)
    && laIdHopLe(duLieu.trainer_profile?.id)
    && typeof duLieu.trainer_profile?.trainer_code === 'string'
    && CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN.includes(duLieu.trainer_profile?.status)
    && duLieu.role?.code === 'PT'
    && duLieu.role?.active === true
    && ['QUEUED', 'NOT_REQUESTED'].includes(duLieu.invitation)
}

function taoLoiPhanHoiKhongHopLe() {
  return {
    code: 'TRAINER_ONBOARDING_RESPONSE_INVALID',
    message: 'Dữ liệu onboarding Huấn luyện viên không hợp lệ.',
    fieldErrors: {},
  }
}

function taoLoiHoSoKhongHopLe() {
  return {
    code: 'TRAINER_PROFILE_RESPONSE_INVALID',
    message: 'Dữ liệu hồ sơ PT không hợp lệ.',
    fieldErrors: {},
  }
}

function laLoiTamThoi(error) {
  return error?.isNetworkError === true
    || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

function laCapNhatHoSoCuaSoHuu(store, sequence) {
  return sequence === store.soThuTuCapNhatHoSo
}

export function xoaDuLieuHuanLuyenVienNeuDaKhoiTao(pinia = getActivePinia()) {
  if (!pinia?.state?.value?.huan_luyen_vien) {
    return false
  }

  useHuanLuyenVienStore(pinia).xoaDuLieu()
  return true
}

export const useHuanLuyenVienStore = defineStore('huan_luyen_vien', {
  state: () => ({
    dangTaiHoSo: false,
    hoSoDaChon: null,
    loiTaiHoSo: null,
    soThuTuHoSo: 0,
    dangOnboarding: false,
    loiOnboarding: null,
    ketQuaOnboarding: null,
    khoaOnboarding: null,
    soThuTuOnboarding: 0,
    dangCapNhatHoSo: false,
    loiCapNhatHoSo: null,
    thongBaoCapNhatHoSo: null,
    khoaCapNhatHoSo: null,
    soThuTuCapNhatHoSo: 0,
  }),

  actions: {
    batDauOnboarding() {
      if (this.khoaOnboarding === null) {
        this.khoaOnboarding = taoKhoaIdempotency()
      }
      return this.khoaOnboarding
    },

    ketThucOnboarding() {
      this.khoaOnboarding = null
    },

    huyOnboarding() {
      this.soThuTuOnboarding += 1
      this.khoaOnboarding = null
      this.dangOnboarding = false
      this.loiOnboarding = null
      this.ketQuaOnboarding = null
    },

    danhDauThayDoiNoiDungOnboarding() {
      if (this.khoaOnboarding === null || this.dangOnboarding) {
        return
      }

      this.soThuTuOnboarding += 1
      this.khoaOnboarding = taoKhoaIdempotency()
      this.loiOnboarding = null
      this.ketQuaOnboarding = null
    },

    async taoMoiHuanLuyenVien({ mode = 'new', duLieu = {} } = {}) {
      if (this.dangOnboarding) {
        return null
      }
      const key = this.batDauOnboarding()
      const sequence = ++this.soThuTuOnboarding
      this.dangOnboarding = true
      this.loiOnboarding = null
      this.ketQuaOnboarding = null

      try {
        const phanHoi = mode === 'existing'
          ? await onboardTaiKhoanHuanLuyenVien(duLieu.account_id, duLieu, key)
          : await taoHuanLuyenVienApi(duLieu, key)
        if (!laKetQuaOnboardingHopLe(phanHoi)) {
          throw taoLoiPhanHoiKhongHopLe()
        }
        if (sequence !== this.soThuTuOnboarding) {
          return null
        }
        this.ketQuaOnboarding = phanHoi.data
        this.khoaOnboarding = null
        return phanHoi.data
      } catch (error) {
        if (sequence !== this.soThuTuOnboarding) {
          return null
        }
        this.loiOnboarding = taoLoiAnToan(error, 'Không thể onboarding Huấn luyện viên. Vui lòng thử lại.')
        return null
      } finally {
        if (sequence === this.soThuTuOnboarding) {
          this.dangOnboarding = false
        }
      }
    },

    async taiHoSoHuanLuyenVien(accountId, sequenceCapNhat = null) {
      if (sequenceCapNhat !== null && !laCapNhatHoSoCuaSoHuu(this, sequenceCapNhat)) {
        return null
      }
      const sequence = ++this.soThuTuHoSo
      const id = Number(accountId)
      const idHienTai = Number(this.hoSoDaChon?.account_id)
      this.dangTaiHoSo = true
      this.loiTaiHoSo = null
      if (!laIdHopLe(accountId) || idHienTai !== id) {
        this.hoSoDaChon = null
      }
      if (!laIdHopLe(accountId)) {
        this.loiTaiHoSo = taoLoiAnToan({ httpStatus: 404, code: 'TRAINER_PROFILE_NOT_FOUND' }, 'Không thể truy cập hồ sơ PT này.')
        this.dangTaiHoSo = false
        return null
      }

      try {
        const phanHoi = await taiHoSoApi(id)
        if (sequenceCapNhat !== null && !laCapNhatHoSoCuaSoHuu(this, sequenceCapNhat)) {
          return null
        }
        if (!laHoSoHopLe(phanHoi?.data)) {
          throw taoLoiHoSoKhongHopLe()
        }
        if (sequence !== this.soThuTuHoSo
          || (sequenceCapNhat !== null && !laCapNhatHoSoCuaSoHuu(this, sequenceCapNhat))) {
          return null
        }
        this.hoSoDaChon = phanHoi.data
        return phanHoi.data
      } catch (error) {
        if (sequence !== this.soThuTuHoSo
          || (sequenceCapNhat !== null && !laCapNhatHoSoCuaSoHuu(this, sequenceCapNhat))) {
          return null
        }
        if (error?.httpStatus === 403 || error?.httpStatus === 404 || !laLoiTamThoi(error)) {
          this.hoSoDaChon = null
        }
        this.loiTaiHoSo = taoLoiAnToan(error, 'Không thể tải hồ sơ PT. Vui lòng thử lại sau.')
        return null
      } finally {
        if (sequence === this.soThuTuHoSo
          && (sequenceCapNhat === null || laCapNhatHoSoCuaSoHuu(this, sequenceCapNhat))) {
          this.dangTaiHoSo = false
        }
      }
    },

    batDauCapNhatHoSo() {
      if (this.khoaCapNhatHoSo === null) {
        this.khoaCapNhatHoSo = taoKhoaIdempotency()
      }
      return this.khoaCapNhatHoSo
    },

    danhDauThayDoiNoiDungCapNhat() {
      if (this.khoaCapNhatHoSo === null || this.dangCapNhatHoSo) {
        return
      }

      this.soThuTuCapNhatHoSo += 1
      this.khoaCapNhatHoSo = taoKhoaIdempotency()
      this.loiCapNhatHoSo = null
      this.thongBaoCapNhatHoSo = null
    },

    async capNhatHoSoHuanLuyenVien(accountId, duLieu = {}) {
      if (this.dangCapNhatHoSo || !laIdHopLe(accountId)) {
        return null
      }
      const sequence = ++this.soThuTuCapNhatHoSo
      const key = this.batDauCapNhatHoSo()
      this.dangCapNhatHoSo = true
      this.loiCapNhatHoSo = null
      this.thongBaoCapNhatHoSo = null

      try {
        await onboardTaiKhoanHuanLuyenVien(Number(accountId), duLieu, key)
        if (!laCapNhatHoSoCuaSoHuu(this, sequence)) {
          return null
        }
        const hoSo = await this.taiHoSoHuanLuyenVien(Number(accountId), sequence)
        if (!laCapNhatHoSoCuaSoHuu(this, sequence) || hoSo === null) {
          return null
        }
        if (!laCapNhatHoSoCuaSoHuu(this, sequence)) {
          return null
        }
        this.khoaCapNhatHoSo = null
        this.thongBaoCapNhatHoSo = 'Đã cập nhật hồ sơ PT.'
        return hoSo
      } catch (error) {
        if (!laCapNhatHoSoCuaSoHuu(this, sequence)) {
          return null
        }
        if (error?.httpStatus === 409) {
          if (!laCapNhatHoSoCuaSoHuu(this, sequence)) return null
          await this.taiHoSoHuanLuyenVien(Number(accountId), sequence)
          if (!laCapNhatHoSoCuaSoHuu(this, sequence)) return null
        }
        if (!laCapNhatHoSoCuaSoHuu(this, sequence)) return null
        this.loiCapNhatHoSo = taoLoiAnToan(error, 'Không thể cập nhật hồ sơ PT. Vui lòng thử lại sau.')
        return null
      } finally {
        if (laCapNhatHoSoCuaSoHuu(this, sequence)) {
          this.dangCapNhatHoSo = false
        }
      }
    },

    xoaHoSoDangChon() {
      this.soThuTuHoSo += 1
      this.soThuTuCapNhatHoSo += 1
      this.hoSoDaChon = null
      this.dangTaiHoSo = false
      this.loiTaiHoSo = null
      this.dangCapNhatHoSo = false
      this.loiCapNhatHoSo = null
      this.thongBaoCapNhatHoSo = null
      this.khoaCapNhatHoSo = null
    },

    xoaDuLieu() {
      this.soThuTuHoSo += 1
      this.soThuTuOnboarding += 1
      this.soThuTuCapNhatHoSo += 1
      this.dangTaiHoSo = false
      this.hoSoDaChon = null
      this.loiTaiHoSo = null
      this.dangOnboarding = false
      this.loiOnboarding = null
      this.ketQuaOnboarding = null
      this.khoaOnboarding = null
      this.dangCapNhatHoSo = false
      this.loiCapNhatHoSo = null
      this.thongBaoCapNhatHoSo = null
      this.khoaCapNhatHoSo = null
    },
  },
})

export { laHoSoHopLe, laKetQuaOnboardingHopLe }
