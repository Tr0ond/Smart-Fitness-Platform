<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  nhanVaiTro: {
    type: String,
    default: '',
  },
  nguoiDung: {
    type: Object,
    default: null,
  },
  dangMoThanhBen: {
    type: Boolean,
    default: false,
  },
  idThanhBen: {
    type: String,
    default: 'thanh-ben-dieu-huong',
  },
})

const emit = defineEmits(['mo', 'dangXuat'])
const nutMenu = ref(null)

const tenAnToan = computed(() => {
  const ten = typeof props.nguoiDung?.name === 'string' ? props.nguoiDung.name.trim() : ''
  const email = typeof props.nguoiDung?.email === 'string' ? props.nguoiDung.email.trim() : ''

  return { ten, email }
})

/**
 * Mở hoặc đóng drawer điều hướng trên màn hình hẹp.
 *
 * Đầu vào: không có; trạng thái mở hiện tại được shell sở hữu.
 * Cách hoạt động: phát sự kiện UI để shell đổi trạng thái drawer.
 * Kết quả: nút có aria-expanded phản ánh trạng thái mới sau khi render.
 * Side effect: không gọi API, không đọc token và không quyết định role.
 * Auth/UX: giữ thao tác bàn phím/chạm qua button native và liên kết tới sidebar.
 */
function xuLyMoThanhBen() {
  emit('mo')
}

/**
 * Yêu cầu shell chạy lifecycle logout duy nhất.
 *
 * Đầu vào: không có.
 * Cách hoạt động: phát sự kiện cho shell gọi Auth Store và điều hướng neutral.
 * Kết quả: topbar không tự tạo request logout hoặc tự xóa state.
 * Side effect: shell có thể cleanup drawer và session theo Store.
 * Auth/UX: chỉ hiển thị name/email an toàn; không hiển thị token, expiry hoặc role history.
 */
function xuLyDangXuat() {
  emit('dangXuat')
}

/**
 * Tra focus ve nut menu sau khi drawer mobile dong.
 *
 * Dau vao: khong co; dung ref cua button native trong topbar.
 * Cach hoat dong: focus nut sau khi shell da go inert va render lai.
 * Ket qua: true neu focus thanh cong, false neu button khong con trong DOM.
 * Side effect: thay doi document.activeElement; khong mo drawer hay goi API.
 * Accessibility Rule: close/Escape/overlay tra focus ve phan tu da kich hoat drawer.
 */
function datFocusNutMenu() {
  if (!(nutMenu.value instanceof HTMLElement)) {
    return false
  }

  nutMenu.value.focus({ preventScroll: true })
  return true
}

defineExpose({ datFocusNutMenu })
</script>

<template>
  <header class="thanh-tren">
    <div class="thanh-tren__bo-cuc">
      <button
        ref="nutMenu"
        class="thanh-tren__nut-menu"
        type="button"
        data-testid="nut-mo-menu"
        :aria-expanded="props.dangMoThanhBen"
        :aria-controls="props.idThanhBen"
        :aria-label="props.dangMoThanhBen ? 'Đóng menu điều hướng' : 'Mở menu điều hướng'"
        @click="xuLyMoThanhBen"
      >
        <svg
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <path
            d="M3 5h14M3 10h14M3 15h14"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linecap="round"
          />
        </svg>
        <span>Menu</span>
      </button>

      <div>
        <p class="thanh-tren__nhan-khu-vuc">
          CỔNG VẬN HÀNH
        </p>
        <p
          class="thanh-tren__vai-tro"
          data-testid="nhan-vai-tro"
        >
          {{ props.nhanVaiTro }}
        </p>
      </div>
    </div>

    <div class="thanh-tren__tai-khoan">
      <span class="thanh-tren__trang-thai">
        <span
          class="thanh-tren__trang-thai-cham"
          aria-hidden="true"
        />
        Đang hoạt động
      </span>
      <div
        class="thanh-tren__danh-tinh"
        aria-label="Tài khoản hiện tại"
      >
        <span
          v-if="tenAnToan.ten"
          class="thanh-tren__ten"
          data-testid="ten-nguoi-dung"
        >
          {{ tenAnToan.ten }}
        </span>
        <span
          v-if="tenAnToan.email"
          class="thanh-tren__email"
          data-testid="email-nguoi-dung"
        >
          {{ tenAnToan.email }}
        </span>
      </div>
      <button
        class="thanh-tren__nut-dang-xuat"
        type="button"
        data-testid="nut-dang-xuat"
        @click="xuLyDangXuat"
      >
        Đăng xuất
      </button>
    </div>
  </header>
</template>
