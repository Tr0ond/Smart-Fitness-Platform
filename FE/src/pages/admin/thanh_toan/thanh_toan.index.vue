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
  CAC_COT_SAP_XEP_THANH_TOAN,
  CAC_TRANG_THAI_DON_MUA,
  CAC_TRANG_THAI_THANH_TOAN,
} from '../../../services/thanh_toan.api.js'
import { useThanhToanStore } from '../../../stores/thanh_toan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const store = useThanhToanStore()
const router = useRouter()
const {
  danhSachThanhToan,
  boLocThanhToan,
  phanTrangThanhToan,
  dangTaiThanhToan,
  loiThanhToan,
  daTaiThanhToanLanDau,
} = storeToRefs(store)

const boLocNhap = reactive({
  order_code: '',
  member: '',
  payment_status: '',
  order_status: '',
  provider_order_code: '',
  provider_reference: '',
  reconciliation_required: '',
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
})

const KIEU_SAP_XEP = Object.freeze({
  created_at: 'Tạo lúc',
  confirmed_at: 'Xác nhận lúc',
  expected_amount: 'Số tiền yêu cầu',
  received_amount: 'Số tiền đã nhận',
})

function taoBoLocTuForm() {
  return {
    order_code: boLocNhap.order_code,
    member: boLocNhap.member,
    payment_status: boLocNhap.payment_status,
    order_status: boLocNhap.order_status,
    provider_order_code: boLocNhap.provider_order_code,
    provider_reference: boLocNhap.provider_reference,
    reconciliation_required: boLocNhap.reconciliation_required,
    from: boLocNhap.from,
    to: boLocNhap.to,
    sort_by: boLocNhap.sort_by,
    sort_direction: boLocNhap.sort_direction,
    per_page: boLocNhap.per_page,
  }
}

function dongBoBoLocNhap() {
  Object.assign(boLocNhap, {
    ...boLocThanhToan.value,
    reconciliation_required: boLocThanhToan.value.reconciliation_required ?? '',
  })
}

function layLoiTruong(tenTruong) {
  const danhSach = loiThanhToan.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

function layNhanTrangThaiThanhToan(trangThai) {
  return NHAN_TRANG_THAI_THANH_TOAN[trangThai] ?? 'Không xác định'
}

function layNhanTrangThaiDonMua(trangThai) {
  return NHAN_TRANG_THAI_DON_MUA[trangThai] ?? 'Không xác định'
}

function layNhanTrangThaiKy(trangThai) {
  return NHAN_TRANG_THAI_KY[trangThai] ?? 'Chưa có dữ liệu kỳ'
}

function layKieuTrangThai(trangThai) {
  return KIEU_TRANG_THAI[trangThai] ?? 'trung_tinh'
}

function layNhanLyDoDoiSoat(maLyDo) {
  return NHAN_LY_DO_DOI_SOAT[maLyDo] ?? 'Cần đối soát'
}

function layKieuLyDoDoiSoat(maLyDo) {
  return maLyDo ? 'canh_bao' : 'trung_tinh'
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

function layTenHoiVien(thanhToan) {
  return typeof thanhToan?.member?.code === 'string' && thanhToan.member.code.trim() !== ''
    ? thanhToan.member.code
    : 'Chưa xác định hội viên'
}

function layMaDon(thanhToan) {
  return typeof thanhToan?.order?.code === 'string' && thanhToan.order.code.trim() !== ''
    ? thanhToan.order.code
    : 'Chưa có mã đơn'
}

function layTrangThaiKy(thanhToan) {
  return thanhToan?.membership_term?.status ?? null
}

function layIdThanhToan(thanhToan) {
  return Number.isSafeInteger(thanhToan?.payment_id) && thanhToan.payment_id > 0
    ? thanhToan.payment_id
    : null
}

function coTheThuLaiLoi(loi) {
  return loi?.isNetworkError === true
    || loi?.code?.endsWith('_RESPONSE_INVALID')
    || (Number.isInteger(loi?.httpStatus) && loi.httpStatus >= 500)
}

const thongBaoLoi = computed(() => layThongBaoLoiApi(
  loiThanhToan.value,
  'Không thể tải danh sách thanh toán. Vui lòng thử lại sau.',
))

const coTheThuLai = computed(() => coTheThuLaiLoi(loiThanhToan.value))

/**
 * Tải Payment list theo bộ lọc người dùng và reset server page về 1.
 * Input: các field hiện có trong form, đã giới hạn đúng Request Backend.
 * Process: Store validate/filter, giữ lỗi 422 ở field và không tự retry khi lỗi mạng.
 * Output: cập nhật list/pagination hoặc trạng thái lỗi an toàn.
 * Side effect: một GET read-only; 403 được Store dọn state và chuyển trang cấm quyền.
 */
async function taiTheoBoLoc() {
  try {
    await store.apDungBoLocThanhToan(taoBoLocTuForm())
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

/**
 * Đặt lại filter về mặc định Backend rồi tải lại trang đầu.
 * Input: không có.
 * Process: xóa giá trị form và gọi Store reset filter read-only.
 * Output: list mới theo query mặc định.
 * Side effect: không thay đổi dữ liệu thanh toán.
 */
async function datLaiBoLoc() {
  Object.assign(boLocNhap, {
    order_code: '',
    member: '',
    payment_status: '',
    order_status: '',
    provider_order_code: '',
    provider_reference: '',
    reconciliation_required: '',
    from: '',
    to: '',
    sort_by: '',
    sort_direction: '',
    per_page: 20,
  })

  try {
    await store.datLaiBoLocThanhToan()
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

/**
 * Đọc một trang Payment tiếp theo từ pagination Backend.
 * Input: số trang hợp lệ do component pagination phát ra.
 * Process: giữ nguyên filter đã áp dụng, để Store bảo vệ race sequence.
 * Output: trang dữ liệu mới hoặc lỗi retry hiển thị trên page.
 * Side effect: chỉ GET, không suy diễn tổng số trang ở client.
 */
async function chuyenTrang(trang) {
  try {
    await store.chuyenTrangThanhToan(trang)
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function xuLyThuLai() {
  try {
    await store.taiDanhSachThanhToan({
      boLoc: boLocThanhToan.value,
      trang: phanTrangThanhToan.value.current_page,
    })
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

function dongBoDuLieuKhoiDau() {
  dongBoBoLocNhap()
}

onMounted(async () => {
  dongBoDuLieuKhoiDau()
  if (!daTaiThanhToanLanDau.value) {
    try {
      await store.taiDanhSachThanhToan({
        boLoc: boLocThanhToan.value,
        trang: phanTrangThanhToan.value.current_page,
      })
    } catch (error) {
      if (error?.httpStatus === 403) {
        await router.replace({ name: 'khongCoQuyen' })
      }
    }
  }
})
</script>

<template>
  <section
    class="trang-thanh-toan"
    aria-label="Danh sách thanh toán"
    :aria-busy="dangTaiThanhToan"
  >
    <TieuDeTrang
      tieu-de="Thanh toán"
      mo-ta="Theo dõi các lần thanh toán và quyền sở hữu theo Safe DTO từ Backend."
    />

    <section
      class="trang-thanh-toan__bo-loc"
      aria-labelledby="tieu-de-bo-loc-thanh-toan"
    >
      <div class="trang-thanh-toan__bo-loc-dau">
        <div>
          <p class="trang-thanh-toan__nhan-khu-vuc">
            Tra cứu read-only
          </p>
          <h2 id="tieu-de-bo-loc-thanh-toan">
            Bộ lọc thanh toán
          </h2>
        </div>
        <p class="trang-thanh-toan__goi-y">
          Bộ lọc được kiểm tra và phân trang bởi Backend.
        </p>
      </div>

      <BoLocDanhSach
        :dang-xu-ly="dangTaiThanhToan"
        @ap-dung="taiTheoBoLoc"
        @dat-lai="datLaiBoLoc"
      >
        <TruongBieuMau
          id="thanh-toan-ma-don"
          nhan="Mã đơn hàng"
          tro-giup="Tối đa 40 ký tự."
          :loi="layLoiTruong('order_code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocNhap.order_code"
              name="order_code"
              type="search"
              maxlength="40"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-hoi-vien"
          nhan="Hội viên"
          tro-giup="Mã hội viên hoặc email."
          :loi="layLoiTruong('member')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocNhap.member"
              name="member"
              type="search"
              maxlength="254"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-trang-thai"
          nhan="Trạng thái Payment"
          :loi="layLoiTruong('payment_status')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocNhap.payment_status"
              name="payment_status"
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
                {{ layNhanTrangThaiThanhToan(trangThai) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-trang-thai-don"
          nhan="Trạng thái đơn"
          :loi="layLoiTruong('order_status')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocNhap.order_status"
              name="order_status"
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
                {{ layNhanTrangThaiDonMua(trangThai) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-ma-cong"
          nhan="Mã giao dịch cổng"
          :loi="layLoiTruong('provider_order_code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocNhap.provider_order_code"
              name="provider_order_code"
              type="number"
              min="1"
              step="1"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-ma-tham-chieu"
          nhan="Mã tham chiếu"
          :loi="layLoiTruong('provider_reference')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocNhap.provider_reference"
              name="provider_reference"
              type="search"
              maxlength="150"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-tu-ngay"
          nhan="Từ ngày"
          :loi="layLoiTruong('from')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocNhap.from"
              name="from"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-den-ngay"
          nhan="Đến ngày"
          :loi="layLoiTruong('to')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocNhap.to"
              name="to"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-doi-soat"
          nhan="Hàng đợi đối soát"
          :loi="layLoiTruong('reconciliation_required')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocNhap.reconciliation_required"
              name="reconciliation_required"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Tất cả thanh toán
              </option>
              <option value="1">
                Chỉ cần đối soát
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-sap-xep"
          nhan="Sắp xếp"
          :loi="layLoiTruong('sort_by')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocNhap.sort_by"
              name="sort_by"
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
                {{ KIEU_SAP_XEP[cot] }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="thanh-toan-chieu-sap-xep"
          nhan="Chiều sắp xếp"
          :loi="layLoiTruong('sort_direction')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocNhap.sort_direction"
              name="sort_direction"
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
    </section>

    <TrangThaiTaiDuLieu
      v-if="dangTaiThanhToan && !daTaiThanhToanLanDau"
      nhan="Đang tải danh sách thanh toán…"
    />

    <TrangThaiLoi
      v-if="loiThanhToan && !danhSachThanhToan.length"
      id="thanh-toan-loi-danh-sach"
      tabindex="-1"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiThanhToan"
      @thu-lai="xuLyThuLai"
    />

    <p
      v-if="dangTaiThanhToan && daTaiThanhToanLanDau"
      class="trang-thanh-toan__dang-tai-lai"
      role="status"
      aria-live="polite"
    >
      Đang cập nhật danh sách thanh toán…
    </p>

    <TrangThaiLoi
      v-if="loiThanhToan && danhSachThanhToan.length"
      id="thanh-toan-loi-cap-nhat"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiThanhToan"
      @thu-lai="xuLyThuLai"
    />

    <TrangThaiTrong
      v-if="daTaiThanhToanLanDau && !loiThanhToan && !danhSachThanhToan.length"
      tieu-de="Chưa có lần thanh toán"
      mo-ta="Không có dữ liệu phù hợp với bộ lọc hiện tại."
    />

    <BangDuLieu
      v-if="danhSachThanhToan.length > 0"
      class="trang-thanh-toan__bang"
      :cot="[]"
      :hang="danhSachThanhToan"
      tieu-de="Danh sách lần thanh toán"
      :dang-tai="dangTaiThanhToan"
      thong-bao-trong="Không có lần thanh toán phù hợp."
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
            Số tiền
          </th>
          <th scope="col">
            Trạng thái Payment
          </th>
          <th scope="col">
            Kỳ Membership
          </th>
          <th scope="col">
            Cần đối soát
          </th>
          <th scope="col">
            Xác nhận
          </th>
          <th scope="col">
            Thao tác
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
          <td>{{ dinhDangTien(thanhToan.received_amount ?? thanhToan.expected_amount, thanhToan.currency) }}</td>
          <td>
            <HuyHieuTrangThai
              :trang-thai="layKieuTrangThai(thanhToan.status)"
              :nhan="layNhanTrangThaiThanhToan(thanhToan.status)"
            />
          </td>
          <td>
            <HuyHieuTrangThai
              :trang-thai="layKieuTrangThai(layTrangThaiKy(thanhToan))"
              :nhan="layNhanTrangThaiKy(layTrangThaiKy(thanhToan))"
            />
          </td>
          <td>
            <HuyHieuTrangThai
              :trang-thai="layKieuLyDoDoiSoat(thanhToan.reconciliation_reason)"
              :nhan="thanhToan.reconciliation_reason
                ? layNhanLyDoDoiSoat(thanhToan.reconciliation_reason)
                : 'Không'"
            />
          </td>
          <td>{{ dinhDangThoiDiem(thanhToan.confirmed_at) }}</td>
          <td>
            <RouterLink
              v-if="layIdThanhToan(thanhToan)"
              class="nut nut--lien-ket"
              :to="{ name: 'adminChiTietThanhToan', params: { id: layIdThanhToan(thanhToan) } }"
            >
              Xem chi tiết
            </RouterLink>
            <span v-else>—</span>
          </td>
        </tr>
      </template>
    </BangDuLieu>

    <ThanhPhanTrang
      v-if="daTaiThanhToanLanDau && !loiThanhToan && phanTrangThanhToan.last_page > 1"
      :trang-hien-tai="phanTrangThanhToan.current_page"
      :tong-so-trang="phanTrangThanhToan.last_page"
      :dang-tai="dangTaiThanhToan"
      @chuyen-trang="chuyenTrang"
    />
  </section>
</template>
