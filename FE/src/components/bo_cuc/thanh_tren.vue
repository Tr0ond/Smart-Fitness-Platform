<script setup>
import { computed } from 'vue'

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
</script>

<template>
  <header class="thanh-tren">
    <button
      class="thanh-tren__nut-menu"
      type="button"
      data-testid="nut-mo-menu"
      :aria-expanded="props.dangMoThanhBen"
      :aria-controls="props.idThanhBen"
      :aria-label="props.dangMoThanhBen ? 'Đóng menu điều hướng' : 'Mở menu điều hướng'"
      @click="xuLyMoThanhBen"
    >
      <span aria-hidden="true">☰</span>
      <span>Menu</span>
    </button>

    <p
      class="thanh-tren__vai-tro"
      data-testid="nhan-vai-tro"
    >
      {{ props.nhanVaiTro }}
    </p>

    <div class="thanh-tren__tai-khoan">
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
