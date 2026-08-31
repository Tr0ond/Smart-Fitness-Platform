<script setup>
const props = defineProps({
  thongBao: {
    type: String,
    default: 'Không thể tải dữ liệu. Vui lòng thử lại sau.',
  },
  coTheThuLai: {
    type: Boolean,
    default: false,
  },
  dangThuLai: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['thuLai'])

/**
 * Phat yeu cau thu lai cho caller khi nguoi dung chu dong chon.
 *
 * Dau vao: khong co; component chi biet trang thai pending do caller truyen vao.
 * Cach hoat dong: chan click lap khi dang thu lai va phat event presentation.
 * Ket qua: caller tu quyet dinh co tai lai query hay khong.
 * Side effect: khong goi API, khong doc Axios error va khong retry tu dong.
 * UI rule: chi hien thong bao an toan bang interpolation, khong hien stack/SQL/raw error.
 */
function xuLyThuLai() {
  if (!props.dangThuLai) {
    emit('thuLai')
  }
}
</script>

<template>
  <section
    class="trang-thai-loi"
    role="alert"
    aria-live="assertive"
  >
    <p class="trang-thai-loi__thong-bao">
      {{ props.thongBao }}
    </p>
    <button
      v-if="props.coTheThuLai"
      class="nut nut--phu"
      type="button"
      :disabled="props.dangThuLai"
      :aria-busy="props.dangThuLai"
      @click="xuLyThuLai"
    >
      {{ props.dangThuLai ? 'Đang thử lại…' : 'Thử lại' }}
    </button>
    <slot name="hanhDong" />
  </section>
</template>
