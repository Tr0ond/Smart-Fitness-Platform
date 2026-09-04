<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../../../components/dung_chung/vung_thong_bao.vue'
import { datFocusVaoTruongLoiDau } from '../../../composables/su_dung_bieu_mau.js'
import { CAC_TRANG_THAI_TAI_KHOAN } from '../../../services/tai_khoan.api.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useTaiKhoanStore()
const {
  nhanVienLeTanDaChon,
  dangTaiChiTietNhanVienLeTan,
  loiTaiChiTietNhanVienLeTan,
  dangCapNhatTrangThaiNhanVienLeTan,
  loiCapNhatTrangThaiNhanVienLeTan,
  thongBaoCapNhatTrangThaiNhanVienLeTan,
  dangThuHoiVaiTroNhanVienLeTan,
  loiThuHoiVaiTroNhanVienLeTan,
  thongBaoThuHoiVaiTroNhanVienLeTan,
} = storeToRefs(store)

const trangThaiMoi = reactive({ giaTri: '' })
const hienThiXacNhan = reactive({ giaTri: false, loai: 'TRANG_THAI' })

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

function layTenChiNhanh(taiKhoan) {
  const ten = taiKhoan?.branch?.name
  const ma = taiKhoan?.branch?.code

  if (typeof ten !== 'string' || ten.trim() === '') {
    return 'Chưa gán'
  }

  return typeof ma === 'string' && ma.trim() !== '' ? `${ten} (${ma})` : ten
}

function layLoiTruong(tenTruong) {
  const danhSach = loiCapNhatTrangThaiNhanVienLeTan.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

const dangMutation = computed(() => dangCapNhatTrangThaiNhanVienLeTan.value
  || dangThuHoiVaiTroNhanVienLeTan.value)

const coTheGuiCapNhat = computed(() => Boolean(nhanVienLeTanDaChon.value)
  && !dangMutation.value
  && CAC_TRANG_THAI_TAI_KHOAN.includes(trangThaiMoi.giaTri)
  && trangThaiMoi.giaTri !== nhanVienLeTanDaChon.value?.status)

const thongBaoLoiChiTiet = computed(() => {
  if (loiTaiChiTietNhanVienLeTan.value?.httpStatus === 404
    || loiTaiChiTietNhanVienLeTan.value?.code === 'RECEPTIONIST_ORIENTATION_INVALID') {
    return 'Không thể truy cập dữ liệu này.'
  }

  return layThongBaoLoiApi(
    loiTaiChiTietNhanVienLeTan.value,
    'Không thể tải chi tiết Nhân viên lễ tân. Vui lòng thử lại sau.',
  )
})

const coTheThuLaiChiTiet = computed(() => loiTaiChiTietNhanVienLeTan.value?.isNetworkError === true
  || (Number.isInteger(loiTaiChiTietNhanVienLeTan.value?.httpStatus)
    && loiTaiChiTietNhanVienLeTan.value.httpStatus >= 500))

function layThongBaoLoiTrangThai() {
  if (loiCapNhatTrangThaiNhanVienLeTan.value?.outcomeUnknown === true) {
    return loiCapNhatTrangThaiNhanVienLeTan.value.message
  }

  if (loiCapNhatTrangThaiNhanVienLeTan.value?.code === 'LAST_ACTIVE_ADMIN_PROTECTED') {
    return 'Không thể vô hiệu hóa quản trị viên hoạt động cuối cùng. Vui lòng kiểm tra lại trạng thái tài khoản.'
  }

  return layThongBaoLoiApi(
    loiCapNhatTrangThaiNhanVienLeTan.value,
    'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại sau.',
  )
}

function layThongBaoLoiVaiTro() {
  if (loiThuHoiVaiTroNhanVienLeTan.value?.outcomeUnknown === true) {
    return loiThuHoiVaiTroNhanVienLeTan.value.message
  }

  if (loiThuHoiVaiTroNhanVienLeTan.value?.code === 'LAST_ACTIVE_ADMIN_PROTECTED') {
    return 'Backend không cho phép thu hồi quản trị viên hoạt động cuối cùng.'
  }

  if (loiThuHoiVaiTroNhanVienLeTan.value?.code === 'ROLE_CONFLICT') {
    return 'Vai trò vừa được thay đổi bởi thao tác khác. Dữ liệu đã được tải lại; hãy kiểm tra lại trước khi thử tiếp.'
  }

  return layThongBaoLoiApi(
    loiThuHoiVaiTroNhanVienLeTan.value,
    'Không thể thu hồi vai trò Nhân viên lễ tân. Vui lòng thử lại sau.',
  )
}

/**
 * Tai detail Account Receptionist theo id route va xu ly orientation/quyen fail-closed.
 *
 * Dau vao: id dang co tren named route hien tai.
 * Cach hoat dong: Store GET Account detail, xac nhan active RECEPTIONIST va chi render
 * DTO an toan; 403 xoa scoped data va dieu huong trang khong quyen.
 * Ket qua: loading/detail/error generic, khong co profile Receptionist gia.
 * Side effect: GET read-only; khong goi shift/desk API, khong tu logout hay mutation.
 * Security Rule: route id khong phai authority; Backend va active assignment quyet dinh scope.
 */
async function xuLyMoChiTietNhanVienLeTan() {
  await store.taiChiTietNhanVienLeTan(layIdTuRoute())

  if (loiTaiChiTietNhanVienLeTan.value?.httpStatus === 403) {
    store.xoaDuLieuNhanVienLeTan()
    await router.replace({ name: 'khongCoQuyen' })
  }
}

/**
 * Retry detail Receptionist bang cung route id sau loi network/5xx.
 *
 * Dau vao: khong co; id lay lai tu URL hien tai.
 * Cach hoat dong: lap lai GET orientation read-only; khong retry 404/403 va khong mutation.
 * Ket qua: DTO authoritative moi hoac loi an toan.
 * Side effect: cap nhat scoped detail state va co the dieu huong 403.
 */
async function thuLaiChiTietNhanVienLeTan() {
  await xuLyMoChiTietNhanVienLeTan()
}

/**
 * Mo confirmation truoc khi PATCH status Account.
 *
 * Dau vao: status moi da chon trong enum Backend.
 * Cach hoat dong: chi doi presentation state, chan status hien tai/pending va chua goi API.
 * Ket qua: dialog xac nhan hoac khong thay doi.
 * Side effect: khong optimistic update, khong cap quyen va khong mutation.
 */
function xuLyCapNhatTrangThai() {
  if (coTheGuiCapNhat.value) {
    hienThiXacNhan.loai = 'TRANG_THAI'
    hienThiXacNhan.giaTri = true
  }
}

/**
 * Mo confirmation exact cho thu hoi role RECEPTIONIST.
 *
 * Dau vao: detail dang co active RECEPTIONIST role.
 * Cach hoat dong: chi mo dialog; role path se bi Store gan co dinh va chua goi DELETE.
 * Ket qua: nguoi dung xem lai hanh dong nguy hiem.
 * Side effect: thay doi presentation state; khong grant/regrant va khong optimistic.
 */
function xuLyThuHoiVaiTroLeTan() {
  if (nhanVienLeTanDaChon.value && !dangMutation.value) {
    hienThiXacNhan.loai = 'THU_HOI'
    hienThiXacNhan.giaTri = true
  }
}

function dongHopXacNhan() {
  if (!dangMutation.value) {
    hienThiXacNhan.giaTri = false
  }
}

/**
 * Xac nhan PATCH status mot lan va refetch authoritative detail/list qua Store.
 *
 * Dau vao: id detail va status da dong bang trong form/dialog.
 * Cach hoat dong: Store dung body `{ status }`, khong idempotency header/optimistic;
 * page xu ly 403 sau mutation ma khong tu logout.
 * Ket qua: dialog dong, status/error theo Backend.
 * Side effect: Backend co the revoke token/audit; page chi dieu huong khi mat quyen.
 */
async function xacNhanCapNhatTrangThai() {
  if (!coTheGuiCapNhat.value || !nhanVienLeTanDaChon.value) {
    return
  }

  await store.capNhatTrangThaiNhanVienLeTan(
    nhanVienLeTanDaChon.value.id,
    trangThaiMoi.giaTri,
  )
  hienThiXacNhan.giaTri = false

  if (loiCapNhatTrangThaiNhanVienLeTan.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiCapNhatTrangThaiNhanVienLeTan.value.fieldErrors,
      ['status'],
      { status: 'nhan-vien-le-tan-chi-tiet-trang-thai' },
      'nhan-vien-le-tan-loi-cap-nhat-trang-thai',
    )
  }

  if (loiCapNhatTrangThaiNhanVienLeTan.value?.httpStatus === 403) {
    store.xoaDuLieuNhanVienLeTan()
    await router.replace({ name: 'khongCoQuyen' })
  }
}

/**
 * Xac nhan DELETE role RECEPTIONIST va roi list khi detail khong con scope.
 *
 * Dau vao: selected Account va confirmation state.
 * Cach hoat dong: Store goi dung DELETE `/roles/RECEPTIONIST`, refetch detail/list; khong
 * co grant/regrant control va khong retry blind khi unknown outcome.
 * Ket qua: neu role khong con active, dieu huong named list; conflict/error hien an toan.
 * Side effect: Backend ghi audit/revoke; page khong tu tinh last-admin.
 */
async function xacNhanThuHoiVaiTroLeTan() {
  if (!nhanVienLeTanDaChon.value || dangMutation.value) {
    return
  }

  const ketQua = await store.thuHoiVaiTroLeTan(nhanVienLeTanDaChon.value.id)
  hienThiXacNhan.giaTri = false

  if (loiThuHoiVaiTroNhanVienLeTan.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiThuHoiVaiTroNhanVienLeTan.value.fieldErrors,
      [],
      {},
      'nhan-vien-le-tan-loi-thu-hoi-vai-tro',
    )
  }

  if (loiThuHoiVaiTroNhanVienLeTan.value?.httpStatus === 403) {
    store.xoaDuLieuNhanVienLeTan()
    await router.replace({ name: 'khongCoQuyen' })
    return
  }

  if (ketQua?.scopeExited) {
    await router.replace({ name: 'adminNhanVienLeTan' })
  }
}

async function xacNhanHanhDong() {
  if (hienThiXacNhan.loai === 'TRANG_THAI') {
    await xacNhanCapNhatTrangThai()
    return
  }

  await xacNhanThuHoiVaiTroLeTan()
}

watch(nhanVienLeTanDaChon, (taiKhoan) => {
  trangThaiMoi.giaTri = taiKhoan?.status ?? ''
})

watch(() => route.params.id, () => {
  store.xoaChiTietNhanVienLeTan()
  hienThiXacNhan.giaTri = false
  hienThiXacNhan.loai = 'TRANG_THAI'
  void xuLyMoChiTietNhanVienLeTan()
})

onMounted(() => {
  void xuLyMoChiTietNhanVienLeTan()
})

onBeforeUnmount(() => {
  store.xoaChiTietNhanVienLeTan()
})
</script>

<template>
  <section
    class="trang-chi-tiet-nhan-vien-le-tan"
    aria-label="Chi tiết Nhân viên lễ tân"
    :aria-busy="dangTaiChiTietNhanVienLeTan || dangMutation"
  >
    <TieuDeTrang
      tieu-de="Chi tiết Nhân viên lễ tân"
      mo-ta="Xem thông tin Account và quản lý trạng thái, vai trò Nhân viên lễ tân theo quyền Admin."
    >
      <template #hanhDong>
        <RouterLink
          :to="{ name: 'adminNhanVienLeTan' }"
          class="nut nut--lien-ket"
        >
          Quay lại danh sách Nhân viên lễ tân
        </RouterLink>
      </template>
    </TieuDeTrang>

    <TrangThaiTaiDuLieu
      v-if="dangTaiChiTietNhanVienLeTan && !nhanVienLeTanDaChon"
      nhan="Đang tải chi tiết Nhân viên lễ tân…"
    />

    <TrangThaiLoi
      v-if="loiTaiChiTietNhanVienLeTan"
      :thong-bao="thongBaoLoiChiTiet"
      :co-the-thu-lai="coTheThuLaiChiTiet"
      :dang-thu-lai="dangTaiChiTietNhanVienLeTan"
      @thu-lai="thuLaiChiTietNhanVienLeTan"
    />

    <template v-if="nhanVienLeTanDaChon">
      <section
        class="the-chi-tiet-nhan-vien-le-tan the-chi-tiet-nhan-vien-le-tan--nhan-dien"
        aria-labelledby="tieu-de-thong-tin-nhan-vien-le-tan"
      >
        <div class="the-chi-tiet-nhan-vien-le-tan__dau">
          <div>
            <p class="the-chi-tiet-nhan-vien-le-tan__nhan-khu-vuc">
              ACCOUNT #{{ nhanVienLeTanDaChon.id }} · RECEPTIONIST
            </p>
            <h2 id="tieu-de-thong-tin-nhan-vien-le-tan">
              {{ nhanVienLeTanDaChon.name }}
            </h2>
            <p class="the-chi-tiet-nhan-vien-le-tan__phu-de">
              {{ nhanVienLeTanDaChon.email }}
            </p>
          </div>
          <HuyHieuTrangThai
            :trang-thai="layKieuTrangThai(nhanVienLeTanDaChon.status)"
            :nhan="layNhanTrangThai(nhanVienLeTanDaChon.status)"
          />
        </div>

        <dl class="luoi-thuoc-tinh-nhan-vien-le-tan">
          <div><dt>ID tài khoản</dt><dd>{{ nhanVienLeTanDaChon.id }}</dd></div>
          <div><dt>Họ tên</dt><dd>{{ nhanVienLeTanDaChon.name }}</dd></div>
          <div>
            <dt>Email</dt><dd class="luoi-thuoc-tinh-nhan-vien-le-tan__gia-tri-dai">
              {{ nhanVienLeTanDaChon.email }}
            </dd>
          </div>
          <div><dt>Điện thoại</dt><dd>{{ dinhDangGiaTri(nhanVienLeTanDaChon.phone) }}</dd></div>
          <div><dt>Chi nhánh</dt><dd>{{ layTenChiNhanh(nhanVienLeTanDaChon) }}</dd></div>
          <div><dt>Email xác minh</dt><dd>{{ dinhDangThoiGian(nhanVienLeTanDaChon.email_verified_at) }}</dd></div>
          <div><dt>Đăng nhập gần nhất</dt><dd>{{ dinhDangThoiGian(nhanVienLeTanDaChon.last_login_at) }}</dd></div>
          <div><dt>Tạo lúc</dt><dd>{{ dinhDangThoiGian(nhanVienLeTanDaChon.created_at) }}</dd></div>
          <div><dt>Cập nhật lúc</dt><dd>{{ dinhDangThoiGian(nhanVienLeTanDaChon.updated_at) }}</dd></div>
        </dl>
      </section>

      <section
        class="the-chi-tiet-nhan-vien-le-tan"
        aria-labelledby="tieu-de-trang-thai-nhan-vien-le-tan"
        :aria-busy="dangMutation"
      >
        <div class="the-chi-tiet-nhan-vien-le-tan__dau">
          <div>
            <p class="the-chi-tiet-nhan-vien-le-tan__nhan-khu-vuc">
              VÒNG ĐỜI TÀI KHOẢN
            </p>
            <h2 id="tieu-de-trang-thai-nhan-vien-le-tan">
              Cập nhật trạng thái
            </h2>
          </div>
          <p class="the-chi-tiet-nhan-vien-le-tan__ghi-chu">
            Backend quyết định trạng thái cuối cùng của Account.
          </p>
        </div>

        <form
          class="bieu-mau-trang-thai-nhan-vien-le-tan"
          aria-label="Cập nhật trạng thái tài khoản"
          :aria-busy="dangMutation"
          @submit.prevent="xuLyCapNhatTrangThai"
        >
          <TruongBieuMau
            id="nhan-vien-le-tan-chi-tiet-trang-thai"
            nhan="Trạng thái mới"
            bat-buoc
            tro-giup="Chỉ chọn một trạng thái Backend cho phép."
            :loi="layLoiTruong('status')"
          >
            <template #default="{ id, ariaDescribedby, ariaInvalid }">
              <select
                :id="id"
                v-model="trangThaiMoi.giaTri"
                name="status"
                :disabled="dangMutation"
                :aria-describedby="ariaDescribedby"
                :aria-invalid="ariaInvalid"
              >
                <option
                  v-for="trangThai in CAC_TRANG_THAI_TAI_KHOAN"
                  :key="trangThai"
                  :value="trangThai"
                >
                  {{ layNhanTrangThai(trangThai) }}
                </option>
              </select>
            </template>
          </TruongBieuMau>
          <button
            class="nut nut--chinh"
            type="submit"
            :disabled="!coTheGuiCapNhat"
            :aria-busy="dangMutation"
          >
            {{ dangMutation ? 'Đang cập nhật…' : 'Cập nhật trạng thái' }}
          </button>
        </form>

        <p
          v-if="loiCapNhatTrangThaiNhanVienLeTan"
          id="nhan-vien-le-tan-loi-cap-nhat-trang-thai"
          class="the-chi-tiet-nhan-vien-le-tan__loi-mutation"
          tabindex="-1"
          role="alert"
          aria-live="assertive"
        >
          {{ layThongBaoLoiTrangThai() }}
        </p>
        <VungThongBao
          v-if="thongBaoCapNhatTrangThaiNhanVienLeTan"
          :danh-sach="[{ id: 'cap-nhat-trang-thai-le-tan', kieu: 'thanh_cong', noiDung: thongBaoCapNhatTrangThaiNhanVienLeTan }]"
        />
      </section>

      <section
        class="the-chi-tiet-nhan-vien-le-tan"
        aria-labelledby="tieu-de-vai-tro-nhan-vien-le-tan"
      >
        <div class="the-chi-tiet-nhan-vien-le-tan__dau">
          <div>
            <p class="the-chi-tiet-nhan-vien-le-tan__nhan-khu-vuc">
              QUYỀN ĐANG GHI NHẬN
            </p>
            <h2 id="tieu-de-vai-tro-nhan-vien-le-tan">
              Vai trò
            </h2>
          </div>
          <p class="the-chi-tiet-nhan-vien-le-tan__ghi-chu">
            Màn hình chỉ cho phép thu hồi role Nhân viên lễ tân.
          </p>
        </div>
        <div class="the-chi-tiet-nhan-vien-le-tan__vai-tro">
          <HuyHieuTrangThai
            trang-thai="thong_tin"
            nhan="Nhân viên lễ tân · Đang hoạt động"
          />
          <button
            class="nut nut--nguy-hiem"
            type="button"
            :disabled="dangMutation"
            :aria-busy="dangThuHoiVaiTroNhanVienLeTan"
            @click="xuLyThuHoiVaiTroLeTan"
          >
            {{ dangThuHoiVaiTroNhanVienLeTan ? 'Đang thu hồi…' : 'Thu hồi vai trò Nhân viên lễ tân' }}
          </button>
        </div>
        <p
          v-if="loiThuHoiVaiTroNhanVienLeTan"
          id="nhan-vien-le-tan-loi-thu-hoi-vai-tro"
          class="the-chi-tiet-nhan-vien-le-tan__loi-mutation"
          tabindex="-1"
          role="alert"
          aria-live="assertive"
        >
          {{ layThongBaoLoiVaiTro() }}
        </p>
        <VungThongBao
          v-if="thongBaoThuHoiVaiTroNhanVienLeTan"
          :danh-sach="[{ id: 'thu-hoi-vai-tro-le-tan', kieu: 'thanh_cong', noiDung: thongBaoThuHoiVaiTroNhanVienLeTan }]"
        />
      </section>
    </template>

    <HopThoaiXacNhan
      :hien-thi="hienThiXacNhan.giaTri"
      :tieu-de="hienThiXacNhan.loai === 'THU_HOI' ? 'Xác nhận thu hồi vai trò' : 'Xác nhận cập nhật trạng thái'"
      :mo-ta="hienThiXacNhan.loai === 'THU_HOI' ? 'Thu hồi vai trò Nhân viên lễ tân?' : `Chuyển trạng thái tài khoản này sang “${layNhanTrangThai(trangThaiMoi.giaTri)}”? Backend sẽ kiểm tra lại trước khi cập nhật.`"
      :nhan-xac-nhan="hienThiXacNhan.loai === 'THU_HOI' ? 'Thu hồi vai trò Nhân viên lễ tân' : `Đổi sang ${layNhanTrangThai(trangThaiMoi.giaTri)}`"
      nhan-huy="Quay lại"
      :mang-nguy-hiem="hienThiXacNhan.loai === 'THU_HOI' || trangThaiMoi.giaTri !== 'HOAT_DONG'"
      :dang-xu-ly="dangMutation"
      @xac-nhan="xacNhanHanhDong"
      @huy="dongHopXacNhan"
      @dong="dongHopXacNhan"
    />
  </section>
</template>
