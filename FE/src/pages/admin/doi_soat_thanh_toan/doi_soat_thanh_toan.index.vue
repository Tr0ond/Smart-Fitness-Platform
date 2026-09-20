<script setup>
import { computed, onMounted, reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { RouterLink, useRouter } from 'vue-router'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import BoLocDanhSach from '../../../components/dung_chung/bo_loc_danh_sach.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import ThanhPhanTrang from '../../../components/dung_chung/thanh_phan_trang.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import {
  CAC_COT_SAP_XEP_SU_KIEN,
  CAC_COT_SAP_XEP_THANH_TOAN,
  CAC_TRANG_THAI_SU_KIEN,
  CAC_TRANG_THAI_DON_MUA,
  CAC_TRANG_THAI_THANH_TOAN,
} from '../../../services/thanh_toan.api.js'
import { useThanhToanStore } from '../../../stores/thanh_toan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const router = useRouter()
const store = useThanhToanStore()
const {
  danhSachCanDoiSoat,
  boLocCanDoiSoat,
  phanTrangCanDoiSoat,
  dangTaiCanDoiSoat,
  loiCanDoiSoat,
  daTaiCanDoiSoatLanDau,
  danhSachSuKienThanhToan,
  boLocSuKienThanhToan,
  phanTrangSuKienThanhToan,
  dangTaiSuKienThanhToan,
  loiSuKienThanhToan,
  daTaiSuKienThanhToanLanDau,
} = storeToRefs(store)

const boLocPaymentNhap = reactive({
  order_code: '',
  member: '',
  payment_status: '',
  order_status: '',
  provider_order_code: '',
  provider_reference: '',
  from: '',
  to: '',
  sort_by: '',
  sort_direction: '',
  per_page: 20,
})

const boLocSuKienNhap = reactive({
  order_code: '',
  member: '',
  processing_status: '',
  provider_order_code: '',
  provider_reference: '',
  from: '',
  to: '',
  sort_by: '',
  sort_direction: '',
  per_page: 20,
})

const NHAN_TRANG_THAI_THANH_TOAN = Object.freeze({
  DANG_TAO: 'Đang tạo',
  CHO_THANH_TOAN: 'Chờ thanh toán',
  THANH_CONG: 'Thanh toán thành công',
  THAT_BAI: 'Thanh toán thất bại',
  HUY: 'Đã hủy',
  HET_HAN: 'Hết hạn',
  CAN_DOI_SOAT: 'Cần đối soát',
})

const NHAN_TRANG_THAI_DON_MUA = Object.freeze({
  CHO_THANH_TOAN: 'Chờ thanh toán',
  DA_THANH_TOAN: 'Đã thanh toán',
  HET_HAN: 'Hết hạn',
  HUY: 'Đã hủy',
  CAN_DOI_SOAT: 'Cần đối soát',
})

const NHAN_TRANG_THAI_SU_KIEN = Object.freeze({
  CHO_XU_LY: 'Chờ xử lý',
  DA_XU_LY: 'Đã xử lý',
  BI_TU_CHOI: 'Bị từ chối',
  CAN_DOI_SOAT: 'Cần đối soát',
  CHO_THU_LAI: 'Chờ thử lại',
})

const NHAN_TRANG_THAI_KY = Object.freeze({
  CHO_THANH_TOAN: 'Chờ thanh toán',
  CHO_KICH_HOAT: 'Chờ kích hoạt',
  CHO_DEN_LUOT: 'Chờ đến lượt',
  DANG_HOAT_DONG: 'Đang hoạt động',
  HET_HAN: 'Hết hạn',
  HUY: 'Đã hủy',
})

const NHAN_LY_DO_DOI_SOAT = Object.freeze({
  AMOUNT_MISMATCH: 'Sai số tiền',
  ORDER_EXPIRED: 'Đơn đã hết hạn',
  ORDER_CANCELLED: 'Đơn đã hủy',
  UNKNOWN_PROVIDER_ORDER: 'Không khớp mã đơn',
  POST_SUCCESS_RECONCILIATION: 'Cảnh báo sau thanh toán thành công',
})

const KIEU_TRANG_THAI = Object.freeze({
  THANH_CONG: 'thanh_cong',
  DA_THANH_TOAN: 'thanh_cong',
  DANG_HOAT_DONG: 'thanh_cong',
  CAN_DOI_SOAT: 'canh_bao',
  HET_HAN: 'canh_bao',
  HUY: 'nguy_hiem',
  THAT_BAI: 'nguy_hiem',
  BI_TU_CHOI: 'nguy_hiem',
})

const KIEU_SAP_XEP_THANH_TOAN = Object.freeze({
  created_at: 'Tạo lúc',
  confirmed_at: 'Xác nhận lúc',
  expected_amount: 'Số tiền yêu cầu',
  received_amount: 'Số tiền đã nhận',
})

const KIEU_SAP_XEP_SU_KIEN = Object.freeze({
  received_at: 'Nhận lúc',
  processed_at: 'Xử lý lúc',
  created_at: 'Tạo lúc',
})

function layNhan(map, ma, fallback = 'Không xác định') {
  return map[ma] ?? fallback
}

function layKieuTrangThai(ma) {
  return KIEU_TRANG_THAI[ma] ?? 'trung_tinh'
}

function dinhDangTien(soTien, donViTien) {
  if (soTien === null || soTien === undefined || soTien === '') {
    return '—'
  }

  const maTien = typeof donViTien === 'string' ? donViTien.trim().toUpperCase() : ''
  const giaTri = Number(soTien)

  if (!Number.isFinite(giaTri)) {
    return 'Không xác định'
  }

  if (/^[A-Z]{3}$/.test(maTien)) {
    try {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: maTien }).format(giaTri)
    } catch {
      return `${soTien} ${maTien}`
    }
  }

  return `${soTien}${maTien ? ` ${maTien}` : ''}`
}

function dinhDangThoiDiem(thoiDiem) {
  return typeof thoiDiem === 'string' && thoiDiem.trim() !== '' ? thoiDiem : '—'
}

function layGiaTri(doiTuong, truong, fallback = '—') {
  const giaTri = doiTuong?.[truong]
  return typeof giaTri === 'string' && giaTri.trim() !== '' ? giaTri : fallback
}

function layMaDon(thanhToan) {
  return layGiaTri(thanhToan?.order, 'code', 'Chưa có mã đơn')
}

function layTenHoiVien(thanhToan) {
  return layGiaTri(thanhToan?.member, 'code', 'Chưa xác định hội viên')
}

function layIdThanhToan(thanhToan) {
  return Number.isSafeInteger(thanhToan?.payment_id) && thanhToan.payment_id > 0
    ? thanhToan.payment_id
    : null
}

function layNhanLyDo(suKien) {
  const maLyDo = suKien?.reconciliation_reason
  return typeof maLyDo === 'string' && maLyDo.trim() !== ''
    ? NHAN_LY_DO_DOI_SOAT[maLyDo] ?? 'Cần đối soát'
    : 'Cần đối soát'
}

function layLoiTruong(loi, tenTruong) {
  const danhSach = loi?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

function coTheThuLaiLoi(loi) {
  return loi?.isNetworkError === true
    || loi?.code?.endsWith('_RESPONSE_INVALID')
    || (Number.isInteger(loi?.httpStatus) && loi.httpStatus >= 500)
}

const thongBaoLoiPayment = computed(() => layThongBaoLoiApi(
  loiCanDoiSoat.value,
  'Không thể tải hàng đợi thanh toán cần đối soát. Vui lòng thử lại sau.',
))

const thongBaoLoiSuKien = computed(() => layThongBaoLoiApi(
  loiSuKienThanhToan.value,
  'Không thể tải hàng đợi sự kiện cần đối soát. Vui lòng thử lại sau.',
))

const coTheThuLaiPayment = computed(() => coTheThuLaiLoi(loiCanDoiSoat.value))
const coTheThuLaiSuKien = computed(() => coTheThuLaiLoi(loiSuKienThanhToan.value))

/**
 * Tải queue Payment authoritative mà không ghép event phía client.
 * Input: filter Payment được nhập trong panel thứ nhất.
 * Process: Store buộc `reconciliation_required=1`, giữ pagination Backend và dọn state khi 403.
 * Output: hàng đợi Payment thành công hoặc trạng thái lỗi an toàn riêng queue.
 * Side effect: một GET read-only; không có control chỉnh sửa hay kích hoạt Membership.
 */
async function taiHangDoiPayment() {
  try {
    await store.apDungBoLocCanDoiSoat(boLocPaymentNhap)
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

/**
 * Tải queue event độc lập, bao gồm event chưa liên kết payment_id.
 * Input: filter event đúng Request Backend trong panel thứ hai.
 * Process: Store gọi endpoint event với cờ đối soát bắt buộc; không tìm Payment tương ứng ở UI.
 * Output: event safe DTO và pagination riêng với queue Payment.
 * Side effect: GET read-only; 403 chuyển khung cấm quyền dùng chung.
 */
async function taiHangDoiSuKien() {
  try {
    await store.apDungBoLocSuKienThanhToan(boLocSuKienNhap)
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function taiHaiHangDoi() {
  await Promise.all([taiHangDoiPayment(), taiHangDoiSuKien()])
}

async function datLaiPayment() {
  Object.keys(boLocPaymentNhap).forEach((truong) => {
    boLocPaymentNhap[truong] = truong === 'per_page' ? 20 : ''
  })
  try {
    await store.datLaiBoLocCanDoiSoat()
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function datLaiSuKien() {
  Object.keys(boLocSuKienNhap).forEach((truong) => {
    boLocSuKienNhap[truong] = truong === 'per_page' ? 20 : ''
  })
  try {
    await store.datLaiBoLocSuKienThanhToan()
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function chuyenTrangPayment(trang) {
  try {
    await store.chuyenTrangCanDoiSoat(trang)
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function chuyenTrangSuKien(trang) {
  try {
    await store.chuyenTrangSuKienThanhToan(trang)
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function xuLyThuLaiPayment() {
  try {
    await store.taiDanhSachCanDoiSoat({
      boLoc: boLocCanDoiSoat.value,
      trang: phanTrangCanDoiSoat.value.current_page,
    })
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function xuLyThuLaiSuKien() {
  try {
    await store.taiSuKienThanhToan({
      boLoc: boLocSuKienThanhToan.value,
      trang: phanTrangSuKienThanhToan.value.current_page,
    })
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

function dongBoBoLoc() {
  Object.assign(boLocPaymentNhap, boLocCanDoiSoat.value)
  Object.assign(boLocSuKienNhap, boLocSuKienThanhToan.value)
}

onMounted(() => {
  dongBoBoLoc()
  void taiHaiHangDoi()
})
</script>

<template>
  <section
    class="trang-doi-soat-thanh-toan"
    aria-label="Đối soát thanh toán"
  >
    <TieuDeTrang
      tieu-de="Đối soát thanh toán"
      mo-ta="Hai hàng đợi độc lập do Backend phân giải: Payment cần đối soát và event thanh toán cần theo dõi."
    />

    <section
      class="trang-doi-soat-thanh-toan__hang-doi"
      aria-labelledby="tieu-de-hang-doi-payment"
    >
      <header class="trang-doi-soat-thanh-toan__dau">
        <div>
          <p class="trang-doi-soat-thanh-toan__nhan-khu-vuc">
            Hàng đợi 01
          </p>
          <h2 id="tieu-de-hang-doi-payment">
            Payment cần đối soát
          </h2>
        </div>
        <p class="trang-doi-soat-thanh-toan__goi-y">
          Backend tự xác định Payment bất thường và Payment thành công có cảnh báo liên quan.
        </p>
      </header>

      <BoLocDanhSach
        :dang-xu-ly="dangTaiCanDoiSoat"
        @ap-dung="taiHangDoiPayment"
        @dat-lai="datLaiPayment"
      >
        <TruongBieuMau
          id="doi-soat-payment-ma-don"
          nhan="Mã đơn hàng"
          :loi="layLoiTruong(loiCanDoiSoat, 'order_code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocPaymentNhap.order_code"
              type="search"
              maxlength="40"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-hoi-vien"
          nhan="Hội viên"
          :loi="layLoiTruong(loiCanDoiSoat, 'member')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocPaymentNhap.member"
              type="search"
              maxlength="254"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-trang-thai"
          nhan="Trạng thái Payment"
          :loi="layLoiTruong(loiCanDoiSoat, 'payment_status')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocPaymentNhap.payment_status"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Tất cả trạng thái
              </option>
              <option
                v-for="trangThai in CAC_TRANG_THAI_THANH_TOAN"
                :key="trangThai"
                :value="trangThai"
              >
                {{ layNhan(NHAN_TRANG_THAI_THANH_TOAN, trangThai) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-trang-thai-don"
          nhan="Trạng thái đơn"
          :loi="layLoiTruong(loiCanDoiSoat, 'order_status')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocPaymentNhap.order_status"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Tất cả trạng thái đơn
              </option>
              <option
                v-for="trangThai in CAC_TRANG_THAI_DON_MUA"
                :key="trangThai"
                :value="trangThai"
              >
                {{ layNhan(NHAN_TRANG_THAI_DON_MUA, trangThai) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-ma-cong"
          nhan="Mã giao dịch cổng"
          :loi="layLoiTruong(loiCanDoiSoat, 'provider_order_code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocPaymentNhap.provider_order_code"
              type="number"
              min="1"
              step="1"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-tham-chieu"
          nhan="Mã tham chiếu"
          :loi="layLoiTruong(loiCanDoiSoat, 'provider_reference')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocPaymentNhap.provider_reference"
              type="search"
              maxlength="150"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-tu-ngay"
          nhan="Từ ngày"
          :loi="layLoiTruong(loiCanDoiSoat, 'from')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocPaymentNhap.from"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-den-ngay"
          nhan="Đến ngày"
          :loi="layLoiTruong(loiCanDoiSoat, 'to')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocPaymentNhap.to"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-sap-xep"
          nhan="Sắp xếp"
          :loi="layLoiTruong(loiCanDoiSoat, 'sort_by')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocPaymentNhap.sort_by"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Mặc định Backend
              </option>
              <option
                v-for="cot in CAC_COT_SAP_XEP_THANH_TOAN"
                :key="cot"
                :value="cot"
              >
                {{ KIEU_SAP_XEP_THANH_TOAN[cot] }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-payment-chieu-sap-xep"
          nhan="Chiều sắp xếp"
          :loi="layLoiTruong(loiCanDoiSoat, 'sort_direction')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocPaymentNhap.sort_direction"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Mặc định Backend
              </option>
              <option value="asc">
                Tăng dần
              </option>
              <option value="desc">
                Giảm dần
              </option>
            </select>
          </template>
        </TruongBieuMau>
      </BoLocDanhSach>

      <TrangThaiTaiDuLieu
        v-if="dangTaiCanDoiSoat && !daTaiCanDoiSoatLanDau"
        nhan="Đang tải hàng đợi Payment…"
      />
      <TrangThaiLoi
        v-if="loiCanDoiSoat && !danhSachCanDoiSoat.length"
        id="doi-soat-loi-payment"
        tabindex="-1"
        :thong-bao="thongBaoLoiPayment"
        :co-the-thu-lai="coTheThuLaiPayment"
        :dang-thu-lai="dangTaiCanDoiSoat"
        @thu-lai="xuLyThuLaiPayment"
      />
      <TrangThaiLoi
        v-if="loiCanDoiSoat && danhSachCanDoiSoat.length > 0"
        id="doi-soat-loi-payment-cap-nhat"
        :thong-bao="thongBaoLoiPayment"
        :co-the-thu-lai="coTheThuLaiPayment"
        :dang-thu-lai="dangTaiCanDoiSoat"
        @thu-lai="xuLyThuLaiPayment"
      />
      <p
        v-if="dangTaiCanDoiSoat && daTaiCanDoiSoatLanDau"
        class="trang-doi-soat-thanh-toan__dang-tai-lai"
        role="status"
        aria-live="polite"
      >
        Đang cập nhật hàng đợi Payment…
      </p>
      <TrangThaiTrong
        v-if="daTaiCanDoiSoatLanDau && !loiCanDoiSoat && !danhSachCanDoiSoat.length"
        tieu-de="Không có Payment cần đối soát"
        mo-ta="Backend không trả về Payment nào trong hàng đợi hiện tại."
      />
      <BangDuLieu
        v-if="danhSachCanDoiSoat.length > 0"
        class="trang-doi-soat-thanh-toan__bang"
        :cot="[]"
        :hang="danhSachCanDoiSoat"
        tieu-de="Payment cần đối soát"
        :dang-tai="dangTaiCanDoiSoat"
      >
        <template #tieuDeCot>
          <tr>
            <th scope="col">
              Mã Payment
            </th>
            <th scope="col">
              Mã đơn
            </th>
            <th scope="col">
              Hội viên
            </th>
            <th scope="col">
              Trạng thái Payment
            </th>
            <th scope="col">
              Trạng thái kỳ
            </th>
            <th scope="col">
              Số tiền
            </th>
            <th scope="col">
              Lý do
            </th>
            <th scope="col">
              Chi tiết
            </th>
          </tr>
        </template>
        <template #hang="{ hang }">
          <tr
            v-for="thanhToan in hang"
            :key="thanhToan.payment_id"
          >
            <td>{{ thanhToan.payment_id }}</td>
            <td>{{ layMaDon(thanhToan) }}</td>
            <td>{{ layTenHoiVien(thanhToan) }}</td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(thanhToan.status)"
                :nhan="layNhan(NHAN_TRANG_THAI_THANH_TOAN, thanhToan.status)"
              />
            </td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(thanhToan.membership_term?.status)"
                :nhan="layNhan(NHAN_TRANG_THAI_KY, thanhToan.membership_term?.status, 'Chưa có dữ liệu kỳ')"
              />
            </td>
            <td>{{ dinhDangTien(thanhToan.received_amount ?? thanhToan.expected_amount, thanhToan.currency) }}</td>
            <td>
              <HuyHieuTrangThai
                trang-thai="canh_bao"
                :nhan="layNhanLyDo({ reconciliation_reason: thanhToan.reconciliation_reason })"
              />
            </td>
            <td>
              <RouterLink
                v-if="layIdThanhToan(thanhToan)"
                class="nut nut--lien-ket"
                :to="{ name: 'adminChiTietThanhToan', params: { id: layIdThanhToan(thanhToan) } }"
              >
                Xem chi tiết
              </RouterLink>
            </td>
          </tr>
        </template>
      </BangDuLieu>
      <ThanhPhanTrang
        v-if="daTaiCanDoiSoatLanDau && !loiCanDoiSoat && phanTrangCanDoiSoat.last_page > 1"
        :trang-hien-tai="phanTrangCanDoiSoat.current_page"
        :tong-so-trang="phanTrangCanDoiSoat.last_page"
        :dang-tai="dangTaiCanDoiSoat"
        @chuyen-trang="chuyenTrangPayment"
      />
    </section>

    <section
      class="trang-doi-soat-thanh-toan__hang-doi"
      aria-labelledby="tieu-de-hang-doi-su-kien"
    >
      <header class="trang-doi-soat-thanh-toan__dau">
        <div>
          <p class="trang-doi-soat-thanh-toan__nhan-khu-vuc">
            Hàng đợi 02
          </p>
          <h2 id="tieu-de-hang-doi-su-kien">
            Sự kiện thanh toán cần đối soát
          </h2>
        </div>
        <p class="trang-doi-soat-thanh-toan__goi-y">
          Event chưa liên kết vẫn hiển thị độc lập; payment_id null không tạo liên kết giả.
        </p>
      </header>

      <BoLocDanhSach
        :dang-xu-ly="dangTaiSuKienThanhToan"
        @ap-dung="taiHangDoiSuKien"
        @dat-lai="datLaiSuKien"
      >
        <TruongBieuMau
          id="doi-soat-su-kien-ma-don"
          nhan="Mã đơn hàng"
          :loi="layLoiTruong(loiSuKienThanhToan, 'order_code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocSuKienNhap.order_code"
              type="search"
              maxlength="40"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-hoi-vien"
          nhan="Hội viên"
          :loi="layLoiTruong(loiSuKienThanhToan, 'member')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocSuKienNhap.member"
              type="search"
              maxlength="254"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-trang-thai"
          nhan="Trạng thái xử lý"
          :loi="layLoiTruong(loiSuKienThanhToan, 'processing_status')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocSuKienNhap.processing_status"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Tất cả trạng thái
              </option>
              <option
                v-for="trangThai in CAC_TRANG_THAI_SU_KIEN"
                :key="trangThai"
                :value="trangThai"
              >
                {{ layNhan(NHAN_TRANG_THAI_SU_KIEN, trangThai) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-ma-cong"
          nhan="Mã giao dịch cổng"
          :loi="layLoiTruong(loiSuKienThanhToan, 'provider_order_code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocSuKienNhap.provider_order_code"
              type="number"
              min="1"
              step="1"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-tham-chieu"
          nhan="Mã tham chiếu"
          :loi="layLoiTruong(loiSuKienThanhToan, 'provider_reference')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocSuKienNhap.provider_reference"
              type="search"
              maxlength="150"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-tu-ngay"
          nhan="Từ ngày"
          :loi="layLoiTruong(loiSuKienThanhToan, 'from')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocSuKienNhap.from"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-den-ngay"
          nhan="Đến ngày"
          :loi="layLoiTruong(loiSuKienThanhToan, 'to')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocSuKienNhap.to"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-sap-xep"
          nhan="Sắp xếp"
          :loi="layLoiTruong(loiSuKienThanhToan, 'sort_by')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocSuKienNhap.sort_by"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Mặc định Backend
              </option>
              <option
                v-for="cot in CAC_COT_SAP_XEP_SU_KIEN"
                :key="cot"
                :value="cot"
              >
                {{ KIEU_SAP_XEP_SU_KIEN[cot] }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="doi-soat-su-kien-chieu-sap-xep"
          nhan="Chiều sắp xếp"
          :loi="layLoiTruong(loiSuKienThanhToan, 'sort_direction')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocSuKienNhap.sort_direction"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Mặc định Backend
              </option>
              <option value="asc">
                Tăng dần
              </option>
              <option value="desc">
                Giảm dần
              </option>
            </select>
          </template>
        </TruongBieuMau>
      </BoLocDanhSach>

      <TrangThaiTaiDuLieu
        v-if="dangTaiSuKienThanhToan && !daTaiSuKienThanhToanLanDau"
        nhan="Đang tải hàng đợi sự kiện…"
      />
      <TrangThaiLoi
        v-if="loiSuKienThanhToan && !danhSachSuKienThanhToan.length"
        id="doi-soat-loi-su-kien"
        tabindex="-1"
        :thong-bao="thongBaoLoiSuKien"
        :co-the-thu-lai="coTheThuLaiSuKien"
        :dang-thu-lai="dangTaiSuKienThanhToan"
        @thu-lai="xuLyThuLaiSuKien"
      />
      <TrangThaiLoi
        v-if="loiSuKienThanhToan && danhSachSuKienThanhToan.length > 0"
        id="doi-soat-loi-su-kien-cap-nhat"
        :thong-bao="thongBaoLoiSuKien"
        :co-the-thu-lai="coTheThuLaiSuKien"
        :dang-thu-lai="dangTaiSuKienThanhToan"
        @thu-lai="xuLyThuLaiSuKien"
      />
      <p
        v-if="dangTaiSuKienThanhToan && daTaiSuKienThanhToanLanDau"
        class="trang-doi-soat-thanh-toan__dang-tai-lai"
        role="status"
        aria-live="polite"
      >
        Đang cập nhật hàng đợi sự kiện…
      </p>
      <TrangThaiTrong
        v-if="daTaiSuKienThanhToanLanDau && !loiSuKienThanhToan && !danhSachSuKienThanhToan.length"
        tieu-de="Không có sự kiện cần đối soát"
        mo-ta="Backend không trả về sự kiện nào trong hàng đợi hiện tại."
      />
      <BangDuLieu
        v-if="danhSachSuKienThanhToan.length > 0"
        class="trang-doi-soat-thanh-toan__bang"
        :cot="[]"
        :hang="danhSachSuKienThanhToan"
        tieu-de="Sự kiện thanh toán cần đối soát"
        :dang-tai="dangTaiSuKienThanhToan"
      >
        <template #tieuDeCot>
          <tr>
            <th scope="col">
              Mã event
            </th>
            <th scope="col">
              Payment liên kết
            </th>
            <th scope="col">
              Mã giao dịch cổng
            </th>
            <th scope="col">
              Mã tham chiếu
            </th>
            <th scope="col">
              Số tiền
            </th>
            <th scope="col">
              Trạng thái xử lý
            </th>
            <th scope="col">
              Lý do
            </th>
            <th scope="col">
              Nhận lúc
            </th>
          </tr>
        </template>
        <template #hang="{ hang }">
          <tr
            v-for="suKien in hang"
            :key="suKien.event_id"
          >
            <td>{{ suKien.event_id }}</td>
            <td>
              <RouterLink
                v-if="Number.isSafeInteger(suKien.payment_id) && suKien.payment_id > 0"
                class="nut nut--lien-ket"
                :to="{ name: 'adminChiTietThanhToan', params: { id: suKien.payment_id } }"
              >
                #{{ suKien.payment_id }}
              </RouterLink>
              <span v-else>Chưa liên kết với Payment</span>
            </td>
            <td>{{ suKien.provider_order_code ?? '—' }}</td>
            <td>{{ layGiaTri(suKien, 'provider_reference') }}</td>
            <td>{{ dinhDangTien(suKien.amount, suKien.currency) }}</td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(suKien.processing_status)"
                :nhan="layNhan(NHAN_TRANG_THAI_SU_KIEN, suKien.processing_status)"
              />
            </td>
            <td>
              <HuyHieuTrangThai
                trang-thai="canh_bao"
                :nhan="layNhanLyDo(suKien)"
              />
            </td>
            <td>{{ dinhDangThoiDiem(suKien.received_at) }}</td>
          </tr>
        </template>
      </BangDuLieu>
      <ThanhPhanTrang
        v-if="daTaiSuKienThanhToanLanDau && !loiSuKienThanhToan && phanTrangSuKienThanhToan.last_page > 1"
        :trang-hien-tai="phanTrangSuKienThanhToan.current_page"
        :tong-so-trang="phanTrangSuKienThanhToan.last_page"
        :dang-tai="dangTaiSuKienThanhToan"
        @chuyen-trang="chuyenTrangSuKien"
      />
    </section>
  </section>
</template>
