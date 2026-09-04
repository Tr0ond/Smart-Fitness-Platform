<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useXacThucStore } from '../../stores/xac_thuc.store.js'
import DuongDanPhanCap from './duong_dan_phan_cap.vue'
import ThanhBenDieuHuong from './thanh_ben_dieu_huong.vue'
import ThanhTren from './thanh_tren.vue'

const props = defineProps({
  maVaiTro: {
    type: String,
    required: true,
  },
  nhanVaiTro: {
    type: String,
    required: true,
  },
  mucDieuHuong: {
    type: Array,
    default: () => [],
  },
  dangKhoiPhuc: {
    type: Boolean,
    default: false,
  },
  hienDuongDan: {
    type: Boolean,
    default: false,
  },
})

const store = useXacThucStore()
const route = useRoute()
const router = useRouter()
const dangMoThanhBen = ref(false)
const thanhTren = ref(null)
const thanhBen = ref(null)
const laManHinhNho = ref(typeof window !== 'undefined' && window.innerWidth < 1024)
const idThanhBen = `thanh-ben-${props.maVaiTro.toLowerCase()}`

const laActorHienTai = computed(() => {
  const coVaiTroHienTai = store.vaiTroDangDung === props.maVaiTro
    && Array.isArray(store.vaiTro)
    && store.vaiTro.includes(props.maVaiTro)

  return coVaiTroHienTai && store.nguoiDung !== null && typeof store.nguoiDung === 'object'
})

const nguoiDungAnToan = computed(() => {
  if (!laActorHienTai.value) {
    return null
  }

  const ten = typeof store.nguoiDung.name === 'string' ? store.nguoiDung.name.trim() : ''
  const email = typeof store.nguoiDung.email === 'string' ? store.nguoiDung.email.trim() : ''

  return { name: ten, email }
})

const dangChoKhoiPhuc = computed(() => props.dangKhoiPhuc || store.dangKhoiPhucPhien)
const duocHienThiShell = computed(() => !dangChoKhoiPhuc.value
  && store.daKhoiPhucPhien
  && laActorHienTai.value)

/**
 * Mở sidebar/drawer điều hướng của actor hiện tại.
 *
 * Đầu vào: không có; chỉ được gọi bởi nút menu trên topbar.
 * Cách hoạt động: đổi state UI cục bộ, không suy role từ URL và không tạo menu mới.
 * Kết quả: sidebar nhận dangMo=true và topbar cập nhật aria-expanded.
 * Side effect: chỉ thay đổi trạng thái hiển thị trong shell.
 * Auth/UX: không hiển thị nội dung actor khi Auth Store còn đang restore.
 */
async function moThanhDieuHuong() {
  if (!duocHienThiShell.value) {
    return
  }

  if (dangMoThanhBen.value) {
    await dongThanhDieuHuong(true)
    return
  }

  dangMoThanhBen.value = true

  if (laManHinhNho.value) {
    await nextTick()
    thanhBen.value?.datFocusDauTien()
  }
}

/**
 * Đóng sidebar/drawer và trả lại vùng nội dung cho người dùng.
 *
 * Đầu vào: không có; có thể gọi từ overlay, Escape, sidebar hoặc route change.
 * Cách hoạt động: reset state drawer về false.
 * Kết quả: overlay biến mất và nội dung không còn bị che trên màn hình hẹp.
 * Side effect: chỉ thay đổi state UI, không logout và không gọi API.
 * Auth/UX: dùng chung cho navigation, logout và cleanup khi đổi breakpoint.
 */
async function dongThanhDieuHuong(traFocus = false) {
  const daMo = dangMoThanhBen.value
  dangMoThanhBen.value = false

  if (daMo && traFocus && laManHinhNho.value) {
    await nextTick()
    thanhTren.value?.datFocusNutMenu()
  }
}

function dongThanhDieuHuongVaTraFocus() {
  void dongThanhDieuHuong(true)
}

/**
 * Hoàn tất chọn mục điều hướng và đóng drawer mobile.
 *
 * Đầu vào: mục điều hướng đã được RouterLink điều hướng theo route registry.
 * Cách hoạt động: không tự dựng URL; chỉ đóng drawer sau click hợp lệ.
 * Kết quả: route hiện tại được giữ do Vue Router xử lý, menu được thu gọn.
 * Side effect: thay đổi state UI cục bộ.
 * Auth/UX: không mở quyền hoặc gọi API từ layout.
 */
function chuyenTrangVaDongMenu() {
  void dongThanhDieuHuong(false)
}

/**
 * Đóng drawer khi route hoặc breakpoint thay đổi.
 *
 * Đầu vào: event resize tùy chọn hoặc route hiện tại từ Vue Router.
 * Cách hoạt động: breakpoint desktop luôn đóng drawer; route change cũng đóng để tránh che trang mới.
 * Kết quả: shell giữ một trạng thái responsive ổn định.
 * Side effect: chỉ thay đổi state UI; không revalidate Auth và không gọi Backend.
 * Auth/UX: quyền vẫn do guard/Backend; hàm chỉ xử lý presentation state.
 */
function dongMenuKhiDoiTuyen() {
  void dongThanhDieuHuong(false)
}

function dongMenuKhiDoiManHinh() {
  laManHinhNho.value = window.innerWidth < 1024

  if (!laManHinhNho.value) {
    void dongThanhDieuHuong(false)
  }
}

function xuLyPhimTat(event) {
  if (!laManHinhNho.value || !dangMoThanhBen.value) {
    return
  }

  if (event.key === 'Escape') {
    event.preventDefault()
    void dongThanhDieuHuong(true)
    return
  }

  if (event.key !== 'Tab') {
    return
  }

  const cacPhanTuCoTheFocus = thanhBen.value?.layCacPhanTuCoTheFocus() ?? []
  if (cacPhanTuCoTheFocus.length === 0) {
    event.preventDefault()
    return
  }

  const phanTuDau = cacPhanTuCoTheFocus[0]
  const phanTuCuoi = cacPhanTuCoTheFocus[cacPhanTuCoTheFocus.length - 1]
  const phanTuDangFocus = document.activeElement
  const focusNamTrongDrawer = cacPhanTuCoTheFocus.includes(phanTuDangFocus)

  if (event.shiftKey && (!focusNamTrongDrawer || phanTuDangFocus === phanTuDau)) {
    event.preventDefault()
    phanTuCuoi.focus({ preventScroll: true })
  } else if (!event.shiftKey && (!focusNamTrongDrawer || phanTuDangFocus === phanTuCuoi)) {
    event.preventDefault()
    phanTuDau.focus({ preventScroll: true })
  }
}

/**
 * Logout từ authenticated shell qua đúng lifecycle của Auth Store.
 *
 * Đầu vào: không có; access token và account state do Store quản lý.
 * Cách hoạt động: gọi Store dangXuat best-effort, luôn đóng drawer và về chooser neutral.
 * Kết quả: local cleanup vẫn xảy ra khi remote logout lỗi; không hiển thị raw error.
 * Side effect: Store gọi POST logout nếu phù hợp, xóa session local và router replace nội bộ.
 * Auth/UX: không tự gửi request, không hiển thị token và không giữ actor/menu stale sau logout.
 */
async function xuLyDangXuat() {
  try {
    await store.dangXuat()
  } finally {
    await dongThanhDieuHuong(false)
    await router.replace({ name: 'chonVaiTro' })
  }
}

watch(() => route.fullPath, dongMenuKhiDoiTuyen)

onMounted(() => {
  window.addEventListener('resize', dongMenuKhiDoiManHinh)
  window.addEventListener('keydown', xuLyPhimTat)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', dongMenuKhiDoiManHinh)
  window.removeEventListener('keydown', xuLyPhimTat)
})
</script>

<template>
  <main
    v-if="dangChoKhoiPhuc"
    class="khung-ung-dung__trang-thai"
    aria-live="polite"
  >
    <p role="status">
      Đang khôi phục phiên làm việc…
    </p>
  </main>

  <main
    v-else-if="!duocHienThiShell"
    class="khung-ung-dung__trang-thai"
    aria-live="polite"
  >
    <p role="status">
      Phiên làm việc cần được xác thực lại.
    </p>
  </main>

  <div
    v-else
    class="khung-ung-dung"
    :class="{ 'khung-ung-dung--mo': dangMoThanhBen }"
  >
    <ThanhTren
      ref="thanhTren"
      :nhan-vai-tro="props.nhanVaiTro"
      :nguoi-dung="nguoiDungAnToan"
      :dang-mo-thanh-ben="dangMoThanhBen"
      :id-thanh-ben="idThanhBen"
      :inert="laManHinhNho && dangMoThanhBen ? '' : undefined"
      :aria-hidden="laManHinhNho && dangMoThanhBen ? 'true' : undefined"
      @mo="moThanhDieuHuong"
      @dang-xuat="xuLyDangXuat"
    />

    <ThanhBenDieuHuong
      ref="thanhBen"
      :id-thanh-ben="idThanhBen"
      :muc-dieu-huong="props.mucDieuHuong"
      :dang-mo="dangMoThanhBen"
      :la-man-hinh-nho="laManHinhNho"
      @dong="dongThanhDieuHuongVaTraFocus"
      @chon="chuyenTrangVaDongMenu"
    />

    <button
      v-if="dangMoThanhBen"
      class="khung-ung-dung__lop-phu"
      type="button"
      tabindex="-1"
      aria-label="Đóng menu điều hướng"
      @click="dongThanhDieuHuongVaTraFocus"
    />

    <div
      class="khung-ung-dung__noi-dung"
      :inert="laManHinhNho && dangMoThanhBen ? '' : undefined"
      :aria-hidden="laManHinhNho && dangMoThanhBen ? 'true' : undefined"
    >
      <main class="khung-ung-dung__main">
        <DuongDanPhanCap v-if="props.hienDuongDan" />
        <slot />
      </main>
    </div>
  </div>
</template>
