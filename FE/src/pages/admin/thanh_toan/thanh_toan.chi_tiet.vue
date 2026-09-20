<script setup>
import { computed, onBeforeUnmount, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useThanhToanStore } from '../../../stores/thanh_toan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useThanhToanStore()
const {
  chiTietThanhToan,
  dangTaiChiTiet,
  loiChiTiet,
  daTaiChiTietLanDau,
} = storeToRefs(store)

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

const NHAN_TRANG_THAI_SU_KIEN = Object.freeze({
  CHO_XU_LY: 'Chờ xử lý',
  DA_XU_LY: 'Đã xử lý',
  BI_TU_CHOI: 'Bị từ chối',
  CAN_DOI_SOAT: 'Cần đối soát',
  CHO_THU_LAI: 'Chờ thử lại',
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

function layLyDo(suKien) {
  return typeof suKien?.reconciliation_reason === 'string'
    && suKien.reconciliation_reason.trim() !== ''
    ? NHAN_LY_DO_DOI_SOAT[suKien.reconciliation_reason] ?? 'Cần đối soát'
    : 'Không'
}

function layKieuLyDo(suKien) {
  return suKien?.reconciliation_reason ? 'canh_bao' : 'trung_tinh'
}

function layGiaTri(doiTuong, truong, fallback = '—') {
  const giaTri = doiTuong?.[truong]
  return typeof giaTri === 'string' && giaTri.trim() !== '' ? giaTri : fallback
}

const thongBaoLoi = computed(() => {
  if (loiChiTiet.value?.httpStatus === 404) {
    return 'Không tìm thấy thông tin thanh toán.'
  }

  return layThongBaoLoiApi(loiChiTiet.value, 'Không thể tải chi tiết thanh toán. Vui lòng thử lại sau.')
})

const coTheThuLai = computed(() => loiChiTiet.value?.isNetworkError === true
  || loiChiTiet.value?.code?.endsWith('_RESPONSE_INVALID')
  || (Number.isInteger(loiChiTiet.value?.httpStatus) && loiChiTiet.value.httpStatus >= 500))

/**
 * Tải detail theo route param và vô hiệu hóa detail trước đó.
 * Input: `route.params.id`; Store chỉ nhận ID dương an toàn rồi để Backend quyết định scope.
 * Process: phát GET, map 403 sang trang cấm quyền, giữ 404 generic và cho retry thủ công
 * với network/5xx hoặc response malformed.
 * Output: Safe DTO detail trong Store hoặc trạng thái query an toàn.
 * Side effect: chỉ đọc; không tự sửa Payment, Membership hay event history.
 */
async function taiChiTietTheoRoute(id) {
  store.xoaChiTietThanhToan()

  try {
    await store.taiChiTietThanhToan(id)
  } catch (error) {
    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
    }
  }
}

async function xuLyThuLai() {
  await taiChiTietTheoRoute(route.params.id)
}

watch(() => route.params.id, (id) => {
  void taiChiTietTheoRoute(id)
}, { immediate: true })

onBeforeUnmount(() => {
  store.xoaChiTietThanhToan()
})
</script>

<template>
  <section
    class="trang-thanh-toan trang-thanh-toan--chi-tiet"
    aria-label="Chi tiết thanh toán"
    :aria-busy="dangTaiChiTiet"
  >
    <TieuDeTrang
      tieu-de="Chi tiết thanh toán"
      mo-ta="Thông tin Payment, đơn mua, kỳ Membership và lịch sử event an toàn."
    >
      <template #hanhDong>
        <RouterLink
          class="nut nut--phu"
          :to="{ name: 'adminThanhToan' }"
        >
          Quay lại danh sách
        </RouterLink>
      </template>
    </TieuDeTrang>

    <TrangThaiTaiDuLieu
      v-if="dangTaiChiTiet && !daTaiChiTietLanDau"
      nhan="Đang tải chi tiết thanh toán…"
    />

    <TrangThaiLoi
      v-if="loiChiTiet && !chiTietThanhToan"
      id="thanh-toan-loi-chi-tiet"
      tabindex="-1"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiChiTiet"
      @thu-lai="xuLyThuLai"
    />

    <p
      v-if="dangTaiChiTiet && chiTietThanhToan"
      class="trang-thanh-toan__dang-tai-lai"
      role="status"
      aria-live="polite"
    >
      Đang cập nhật chi tiết…
    </p>

    <TrangThaiLoi
      v-if="loiChiTiet && chiTietThanhToan"
      id="thanh-toan-loi-chi-tiet-cap-nhat"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiChiTiet"
      @thu-lai="xuLyThuLai"
    />

    <TrangThaiTrong
      v-if="daTaiChiTietLanDau && !loiChiTiet && !chiTietThanhToan"
      tieu-de="Không có chi tiết thanh toán"
      mo-ta="Backend không trả về bản ghi chi tiết an toàn."
    />

    <template v-if="chiTietThanhToan">
      <section
        class="trang-thanh-toan__khung-thong-tin"
        aria-labelledby="tieu-de-thong-tin-payment"
      >
        <div class="trang-thanh-toan__khung-tieu-de">
          <div>
            <p class="trang-thanh-toan__nhan-khu-vuc">
              Quyền sở hữu Payment
            </p>
            <h2 id="tieu-de-thong-tin-payment">
              Payment #{{ chiTietThanhToan.payment_id }}
            </h2>
          </div>
          <HuyHieuTrangThai
            :trang-thai="layKieuTrangThai(chiTietThanhToan.status)"
            :nhan="layNhan(NHAN_TRANG_THAI_THANH_TOAN, chiTietThanhToan.status)"
          />
        </div>
        <dl class="trang-thanh-toan__luoi-thuoc-tinh">
          <div>
            <dt>Lần thử</dt>
            <dd>{{ chiTietThanhToan.attempt ?? '—' }}</dd>
          </div>
          <div>
            <dt>Kênh</dt>
            <dd>{{ layGiaTri(chiTietThanhToan, 'channel') }}</dd>
          </div>
          <div>
            <dt>Mã giao dịch cổng</dt>
            <dd>{{ chiTietThanhToan.provider_order_code ?? '—' }}</dd>
          </div>
          <div>
            <dt>Mã tham chiếu</dt>
            <dd>{{ layGiaTri(chiTietThanhToan, 'provider_reference') }}</dd>
          </div>
          <div>
            <dt>Số tiền yêu cầu</dt>
            <dd>{{ dinhDangTien(chiTietThanhToan.expected_amount, chiTietThanhToan.currency) }}</dd>
          </div>
          <div>
            <dt>Số tiền đã nhận</dt>
            <dd>{{ dinhDangTien(chiTietThanhToan.received_amount, chiTietThanhToan.currency) }}</dd>
          </div>
          <div>
            <dt>Thanh toán lúc</dt>
            <dd>{{ dinhDangThoiDiem(chiTietThanhToan.paid_at) }}</dd>
          </div>
          <div>
            <dt>Xác nhận lúc</dt>
            <dd>{{ dinhDangThoiDiem(chiTietThanhToan.confirmed_at) }}</dd>
          </div>
        </dl>
        <p
          v-if="chiTietThanhToan.status === 'THANH_CONG'"
          class="trang-thanh-toan__ghi-chu"
        >
          Thanh toán thành công xác nhận quyền sở hữu. Trạng thái kích hoạt kỳ Membership được hiển thị riêng từ Backend.
        </p>
      </section>

      <section
        class="trang-thanh-toan__khung-thong-tin"
        aria-labelledby="tieu-de-don-mua"
      >
        <div class="trang-thanh-toan__khung-tieu-de">
          <h2 id="tieu-de-don-mua">
            Đơn mua và hội viên
          </h2>
        </div>
        <dl class="trang-thanh-toan__luoi-thuoc-tinh">
          <div>
            <dt>Mã đơn</dt>
            <dd>{{ layGiaTri(chiTietThanhToan.order, 'code', 'Chưa có mã đơn') }}</dd>
          </div>
          <div>
            <dt>Trạng thái đơn</dt>
            <dd>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(chiTietThanhToan.order?.status)"
                :nhan="layNhan(NHAN_TRANG_THAI_DON_MUA, chiTietThanhToan.order?.status)"
              />
            </dd>
          </div>
          <div>
            <dt>Hội viên</dt>
            <dd>{{ layGiaTri(chiTietThanhToan.member, 'code', 'Chưa xác định hội viên') }}</dd>
          </div>
          <div>
            <dt>Email</dt>
            <dd>{{ layGiaTri(chiTietThanhToan.member, 'email') }}</dd>
          </div>
          <div>
            <dt>Giá đã chốt</dt>
            <dd>{{ dinhDangTien(chiTietThanhToan.order?.expected_amount, chiTietThanhToan.order?.currency) }}</dd>
          </div>
          <div>
            <dt>Chốt giá lúc</dt>
            <dd>{{ dinhDangThoiDiem(chiTietThanhToan.order?.price_locked_at) }}</dd>
          </div>
        </dl>
      </section>

      <section
        class="trang-thanh-toan__khung-thong-tin"
        aria-labelledby="tieu-de-ky-membership"
      >
        <div class="trang-thanh-toan__khung-tieu-de">
          <h2 id="tieu-de-ky-membership">
            Kỳ Membership
          </h2>
          <p class="trang-thanh-toan__goi-y">
            Đây là trạng thái do Backend cung cấp, không phải tính toán tại client.
          </p>
        </div>
        <dl class="trang-thanh-toan__luoi-thuoc-tinh">
          <div>
            <dt>Tên gói</dt>
            <dd>{{ layGiaTri(chiTietThanhToan.membership_term, 'package_name', 'Chưa có dữ liệu kỳ') }}</dd>
          </div>
          <div>
            <dt>Trạng thái kỳ</dt>
            <dd>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(chiTietThanhToan.membership_term?.status)"
                :nhan="layNhan(NHAN_TRANG_THAI_KY, chiTietThanhToan.membership_term?.status, 'Chưa có dữ liệu kỳ')"
              />
            </dd>
          </div>
          <div>
            <dt>Thứ tự kỳ</dt>
            <dd>{{ chiTietThanhToan.membership_term?.sequence ?? '—' }}</dd>
          </div>
          <div>
            <dt>Phiên bản gói</dt>
            <dd>{{ chiTietThanhToan.membership_term?.package_version ?? '—' }}</dd>
          </div>
          <div>
            <dt>Giá snapshot</dt>
            <dd>{{ dinhDangTien(chiTietThanhToan.membership_term?.purchase_price, chiTietThanhToan.currency) }}</dd>
          </div>
        </dl>
      </section>

      <section
        class="trang-thanh-toan__khung-thong-tin"
        aria-labelledby="tieu-de-su-kien"
      >
        <div class="trang-thanh-toan__khung-tieu-de">
          <h2 id="tieu-de-su-kien">
            Lịch sử sự kiện thanh toán
          </h2>
        </div>
        <TrangThaiTrong
          v-if="!Array.isArray(chiTietThanhToan.events) || chiTietThanhToan.events.length === 0"
          tieu-de="Chưa có sự kiện liên quan"
          mo-ta="Backend không trả về event nào cho lần thanh toán này."
        />
        <BangDuLieu
          v-else
          class="trang-thanh-toan__bang"
          :cot="[]"
          :hang="chiTietThanhToan.events"
          tieu-de="Các event liên quan"
        >
          <template #tieuDeCot>
            <tr>
              <th scope="col">
                Mã event
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
                Đối soát
              </th>
              <th scope="col">
                Nhận lần đầu
              </th>
              <th scope="col">
                Xử lý lúc
              </th>
            </tr>
          </template>
          <template #hang="{ hang }">
            <tr
              v-for="suKien in hang"
              :key="suKien.event_id"
            >
              <td>{{ suKien.event_id }}</td>
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
                  :trang-thai="layKieuLyDo(suKien)"
                  :nhan="layLyDo(suKien)"
                />
              </td>
              <td>{{ dinhDangThoiDiem(suKien.received_at) }}</td>
              <td>{{ dinhDangThoiDiem(suKien.processed_at) }}</td>
            </tr>
          </template>
        </BangDuLieu>
      </section>
    </template>
  </section>
</template>
