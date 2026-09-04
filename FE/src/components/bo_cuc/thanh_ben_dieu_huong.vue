<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { laMucDieuHuongDangHoatDong } from '../../router/dieu_huong_admin.js'

const props = defineProps({
  idThanhBen: {
    type: String,
    default: 'thanh-ben-dieu-huong',
  },
  mucDieuHuong: {
    type: Array,
    default: () => [],
  },
  dangMo: {
    type: Boolean,
    default: false,
  },
  laManHinhNho: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['dong', 'chon'])
const route = useRoute()
const router = useRouter()
const phanTuThanhBen = ref(null)
const BO_CHON_CO_THE_FOCUS = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

function layDuongDanHopLe(muc) {
  if (muc === null || typeof muc !== 'object') {
    return null
  }

  try {
    const tenTuyenDuong = typeof muc.tenTuyenDuong === 'string'
      ? muc.tenTuyenDuong.trim()
      : typeof muc.to?.name === 'string' ? muc.to.name.trim() : ''

    if (tenTuyenDuong === '') {
      return null
    }

    if (typeof router.hasRoute === 'function' && !router.hasRoute(tenTuyenDuong)) {
      return null
    }

    const ketQua = router.resolve({ name: tenTuyenDuong })
    return ketQua.matched.length > 0 ? { ...ketQua, tenTuyenDuong } : null
  } catch {
    return null
  }
}

const mucDieuHuongHopLe = computed(() => props.mucDieuHuong.filter((muc) => {
  const ketQua = layDuongDanHopLe(muc)
  return ketQua !== null && typeof muc.nhan === 'string' && muc.nhan.trim() !== ''
}))

function laMucDangChon(muc) {
  const ketQua = layDuongDanHopLe(muc)
  return ketQua !== null && laMucDieuHuongDangHoatDong(muc, route)
}

/**
 * Đóng drawer sau khi người dùng chọn một mục điều hướng hợp lệ.
 *
 * Đầu vào: mục điều hướng đã được RouterLink kiểm tra route.
 * Cách hoạt động: phát sự kiện để shell đóng drawer; RouterLink vẫn chịu trách nhiệm điều hướng.
 * Kết quả: menu mobile không che nội dung sau khi route đổi.
 * Side effect: chỉ phát sự kiện UI, không gọi API và không thay đổi quyền actor.
 * Auth/UX: không tự tạo link hoặc menu theo role; chỉ dùng mục được layout truyền vào.
 */
function xuLyChonMuc(muc) {
  emit('chon', muc)
}

/**
 * Lay cac control dang co the focus ben trong drawer theo thu tu DOM.
 *
 * Dau vao: khong co; doc root aside hien tai sau render.
 * Cach hoat dong: query allow-list control va loai phan tu disabled/aria-hidden.
 * Ket qua: danh sach HTMLElement de shell xu ly vong Tab.
 * Side effect: khong doi focus, route, state hay goi API.
 * Accessibility Rule: focus trap chi dung cac control thuc su thao tac duoc.
 */
function layCacPhanTuCoTheFocus() {
  if (!(phanTuThanhBen.value instanceof HTMLElement)) {
    return []
  }

  return Array.from(phanTuThanhBen.value.querySelectorAll(BO_CHON_CO_THE_FOCUS))
    .filter((phanTu) => phanTu instanceof HTMLElement
      && !phanTu.hasAttribute('disabled')
      && phanTu.getAttribute('aria-hidden') !== 'true')
}

/**
 * Dat focus vao control dau tien cua drawer sau khi mo.
 *
 * Dau vao: khong co.
 * Cach hoat dong: lay danh sach focusable sau render va focus phan tu dau.
 * Ket qua: true neu focus thanh cong, false neu drawer khong co control.
 * Side effect: thay doi document.activeElement; khong dieu huong hay goi API.
 * Accessibility Rule: nguoi dung ban phim duoc dua vao drawer vua mo.
 */
function datFocusDauTien() {
  const phanTuDauTien = layCacPhanTuCoTheFocus()[0]

  if (!(phanTuDauTien instanceof HTMLElement)) {
    return false
  }

  phanTuDauTien.focus({ preventScroll: true })
  return true
}

defineExpose({ datFocusDauTien, layCacPhanTuCoTheFocus })
</script>

<template>
  <aside
    :id="props.idThanhBen"
    ref="phanTuThanhBen"
    class="thanh-ben-dieu-huong"
    :class="{ 'thanh-ben-dieu-huong--dang-mo': props.dangMo }"
    :inert="props.laManHinhNho && !props.dangMo ? '' : undefined"
    :aria-hidden="props.laManHinhNho && !props.dangMo ? 'true' : undefined"
    aria-label="Điều hướng khu vực"
  >
    <div class="thanh-ben-dieu-huong__dau">
      <div class="thanh-ben-dieu-huong__thuong-hieu">
        <span
          class="thanh-ben-dieu-huong__dau-hieu"
          aria-hidden="true"
        >
          <svg
            viewBox="0 0 32 32"
            fill="none"
          >
            <path
              d="M7 6v20M25 6v20M4 11h6M22 11h6M4 21h6M22 21h6"
              stroke="currentColor"
              stroke-width="2.4"
              stroke-linecap="round"
            />
            <path
              d="M12 16h8"
              stroke="currentColor"
              stroke-width="2.4"
              stroke-linecap="round"
            />
          </svg>
        </span>
        <span class="thanh-ben-dieu-huong__tieu-de">SMART FITNESS</span>
      </div>
      <p class="thanh-ben-dieu-huong__nhan">
        VẬN HÀNH / 01
      </p>
      <button
        class="thanh-ben-dieu-huong__nut-dong"
        type="button"
        aria-label="Đóng menu điều hướng"
        @click="emit('dong')"
      >
        <svg
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <path
            d="m5 5 10 10M15 5 5 15"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linecap="round"
          />
        </svg>
        Đóng menu
      </button>
    </div>

    <nav
      class="thanh-ben-dieu-huong__nav"
      aria-label="Menu khu vực"
    >
      <ul
        v-if="mucDieuHuongHopLe.length > 0"
        class="thanh-ben-dieu-huong__danh-sach"
      >
        <li
          v-for="muc in mucDieuHuongHopLe"
          :key="muc.id ?? muc.ten ?? muc.nhan"
        >
          <RouterLink
            :to="{ name: muc.tenTuyenDuong ?? muc.to.name }"
            class="thanh-ben-dieu-huong__lien-ket"
            :class="{ 'thanh-ben-dieu-huong__lien-ket--dang-chon': laMucDangChon(muc) }"
            :aria-current="laMucDangChon(muc) ? 'page' : undefined"
            @click="xuLyChonMuc(muc)"
          >
            {{ muc.nhan }}
          </RouterLink>
        </li>
      </ul>
      <p
        v-else
        class="thanh-ben-dieu-huong__rong"
      >
        Chưa có mục điều hướng. Khu vực nghiệp vụ đang được hoàn thiện.
      </p>
    </nav>
  </aside>
</template>
