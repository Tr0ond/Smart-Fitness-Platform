<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useTaiKhoanStore()
const {
  hoiVienDaChon,
  dangTaiChiTietHoiVien,
  loiTaiChiTietHoiVien,
} = storeToRefs(store)

const NHAN_TRANG_THAI = Object.freeze({
  HOAT_DONG: 'Hoạt động',
  BI_KHOA: 'Bị khóa',
  NGUNG_HOAT_DONG: 'Ngừng hoạt động',
})

const KIEU_TRANG_THAI = Object.freeze({
  HOAT_DONG: 'thanh_cong',
  BI_KHOA: 'nguy_hiem',
  NGUNG_HOAT_DONG: 'canh_bao',
})

function layIdTuRoute() {
  return typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id
}

function layNhanTrangThai(trangThai) {
  return NHAN_TRANG_THAI[trangThai] ?? 'Không xác định'
}

function layKieuTrangThai(trangThai) {
  return KIEU_TRANG_THAI[trangThai] ?? 'trung_tinh'
}

function dinhDangGiaTri(giaTri) {
  return typeof giaTri === 'string' && giaTri.trim() !== '' ? giaTri : 'Chưa cập nhật'
}

function dinhDangThoiGian(thoiGian) {
  if (typeof thoiGian !== 'string' || Number.isNaN(Date.parse(thoiGian))) {
    return 'Chưa có dữ liệu'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(thoiGian))
}

function layMaHoiVien(hoiVien) {
  return typeof hoiVien?.member_profile?.code === 'string'
    && hoiVien.member_profile.code.trim() !== ''
    ? hoiVien.member_profile.code
    : 'Chưa có thông tin hồ sơ Hội viên.'
}

function layTenChiNhanh(hoiVien) {
  const tenChiNhanh = hoiVien?.branch?.name
  const maChiNhanh = hoiVien?.branch?.code

  if (typeof tenChiNhanh !== 'string' || tenChiNhanh.trim() === '') {
    return 'Chưa gán'
  }

  return typeof maChiNhanh === 'string' && maChiNhanh.trim() !== ''
    ? `${tenChiNhanh} (${maChiNhanh})`
    : tenChiNhanh
}

/**
 * Tai Account detail cho Member-oriented page va fail-closed voi sai orientation.
 *
 * Dau vao: id dang co tren named route hien tai.
 * Cach hoat dong: Store goi Account detail, kiem tra active MEMBER role va chi commit
 * DTO an toan; neu 403 thi clear scoped state va chuyen trang loi quyen.
 * Ket qua: page hien loading, basic account/member fields, empty profile hoac loi generic.
 * Side effect: GET read-only; khong goi Membership/PT/Member-self API, khong mutation.
 * Security Rule: Backend van la authority; page khong suy quyen tu URL hay cached role.
 */
async function taiChiTietHoiVien() {
  await store.taiChiTietHoiVien(layIdTuRoute())

  if (loiTaiChiTietHoiVien.value?.httpStatus === 403) {
    store.xoaDuLieuHoiVien()
    await router.replace({ name: 'khongCoQuyen' })
  }
}

/**
 * Thu lai GET detail Member bang cung route id sau loi tam thoi.
 *
 * Dau vao: id hien tai tren URL, khong nhan id tu form.
 * Cach hoat dong: lap lai query read-only va giu orientation check; chi retry thu cong
 * cho network/5xx, khong retry 404/403 hay tao mutation.
 * Ket qua: detail authoritative moi hoac loi an toan.
 * Side effect: cap nhat state Member rieng va co the dieu huong khi mat quyen.
 */
async function thuLaiChiTietHoiVien() {
  await taiChiTietHoiVien()
}

const thongBaoLoi = computed(() => {
  if (loiTaiChiTietHoiVien.value?.httpStatus === 404
    || loiTaiChiTietHoiVien.value?.code === 'MEMBER_ORIENTATION_INVALID') {
    return 'Không thể truy cập dữ liệu này.'
  }

  return layThongBaoLoiApi(
    loiTaiChiTietHoiVien.value,
    'Không thể tải chi tiết Hội viên. Vui lòng thử lại sau.',
  )
})

const coTheThuLai = computed(() => loiTaiChiTietHoiVien.value?.isNetworkError === true
  || (Number.isInteger(loiTaiChiTietHoiVien.value?.httpStatus)
    && loiTaiChiTietHoiVien.value.httpStatus >= 500))

watch(() => route.params.id, () => {
  store.xoaChiTietHoiVien()
  void taiChiTietHoiVien()
})

onMounted(() => {
  void taiChiTietHoiVien()
})

onBeforeUnmount(() => {
  store.xoaChiTietHoiVien()
})
</script>

<template>
  <section
    class="trang-chi-tiet-hoi-vien"
    aria-label="Chi tiết Hội viên"
    :aria-busy="dangTaiChiTietHoiVien"
  >
    <TieuDeTrang
      tieu-de="Chi tiết Hội viên"
      mo-ta="Chỉ hiển thị thông tin Account và hồ sơ Hội viên cơ bản được Backend cho phép."
    >
      <template #hanhDong>
        <RouterLink
          :to="{ name: 'adminHoiVien' }"
          class="nut nut--lien-ket"
        >
          Quay lại danh sách Hội viên
        </RouterLink>
      </template>
    </TieuDeTrang>

    <TrangThaiTaiDuLieu
      v-if="dangTaiChiTietHoiVien && !hoiVienDaChon"
      nhan="Đang tải chi tiết Hội viên…"
    />

    <TrangThaiLoi
      v-if="loiTaiChiTietHoiVien"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiChiTietHoiVien"
      @thu-lai="thuLaiChiTietHoiVien"
    />

    <template v-if="hoiVienDaChon">
      <section
        class="the-chi-tiet-hoi-vien the-chi-tiet-hoi-vien--nhan-dien"
        aria-labelledby="tieu-de-thong-tin-hoi-vien"
      >
        <div class="the-chi-tiet-hoi-vien__dau">
          <div>
            <p class="the-chi-tiet-hoi-vien__nhan-khu-vuc">
              ACCOUNT #{{ hoiVienDaChon.id }} · MEMBER
            </p>
            <h2 id="tieu-de-thong-tin-hoi-vien">
              {{ hoiVienDaChon.name }}
            </h2>
            <p class="the-chi-tiet-hoi-vien__phu-de">
              {{ hoiVienDaChon.email }}
            </p>
          </div>
          <HuyHieuTrangThai
            :trang-thai="layKieuTrangThai(hoiVienDaChon.status)"
            :nhan="layNhanTrangThai(hoiVienDaChon.status)"
          />
        </div>

        <dl class="luoi-thuoc-tinh-hoi-vien">
          <div>
            <dt>ID tài khoản</dt>
            <dd>{{ hoiVienDaChon.id }}</dd>
          </div>
          <div>
            <dt>Họ tên</dt>
            <dd>{{ hoiVienDaChon.name }}</dd>
          </div>
          <div>
            <dt>Email</dt>
            <dd class="luoi-thuoc-tinh-hoi-vien__gia-tri-dai">
              {{ hoiVienDaChon.email }}
            </dd>
          </div>
          <div>
            <dt>Điện thoại</dt>
            <dd>{{ dinhDangGiaTri(hoiVienDaChon.phone) }}</dd>
          </div>
          <div>
            <dt>Chi nhánh</dt>
            <dd>{{ layTenChiNhanh(hoiVienDaChon) }}</dd>
          </div>
          <div>
            <dt>Trạng thái vai trò</dt>
            <dd>
              <HuyHieuTrangThai
                trang-thai="thong_tin"
                nhan="Hội viên"
              />
            </dd>
          </div>
          <div>
            <dt>Email xác minh</dt>
            <dd>{{ dinhDangThoiGian(hoiVienDaChon.email_verified_at) }}</dd>
          </div>
          <div>
            <dt>Đăng nhập gần nhất</dt>
            <dd>{{ dinhDangThoiGian(hoiVienDaChon.last_login_at) }}</dd>
          </div>
          <div>
            <dt>Tạo lúc</dt>
            <dd>{{ dinhDangThoiGian(hoiVienDaChon.created_at) }}</dd>
          </div>
          <div>
            <dt>Cập nhật lúc</dt>
            <dd>{{ dinhDangThoiGian(hoiVienDaChon.updated_at) }}</dd>
          </div>
        </dl>
      </section>

      <section
        class="the-chi-tiet-hoi-vien"
        aria-labelledby="tieu-de-ho-so-hoi-vien"
      >
        <div class="the-chi-tiet-hoi-vien__dau">
          <div>
            <p class="the-chi-tiet-hoi-vien__nhan-khu-vuc">
              HỒ SƠ CƠ BẢN
            </p>
            <h2 id="tieu-de-ho-so-hoi-vien">
              Hồ sơ Hội viên
            </h2>
          </div>
          <p class="the-chi-tiet-hoi-vien__ghi-chu">
            Hồ sơ có thể chưa được tạo; màn hình này không tự tạo hoặc suy diễn dữ liệu còn thiếu.
          </p>
        </div>

        <dl class="luoi-thuoc-tinh-hoi-vien luoi-thuoc-tinh-hoi-vien--ho-so">
          <div>
            <dt>Mã hội viên</dt>
            <dd>{{ layMaHoiVien(hoiVienDaChon) }}</dd>
          </div>
          <div>
            <dt>Trạng thái Account</dt>
            <dd>{{ layNhanTrangThai(hoiVienDaChon.status) }}</dd>
          </div>
        </dl>
      </section>
    </template>
  </section>
</template>
