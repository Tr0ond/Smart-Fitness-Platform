<script setup>
const props = defineProps({
  dangXuLy: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['apDung', 'datLai'])

/**
 * Gui yeu cau ap dung bo loc presentation cho page/store so huu query.
 *
 * Dau vao: gia tri filter nam trong slot form.
 * Cach hoat dong: ngan submit lap khi pending va phat event, khong tu tao payload/API.
 * Ket qua: caller nhan event apDung de validate va tai du lieu theo contract that.
 * Side effect: chi phat event UI; khong mutate URL, store hay authorization.
 * UI rule: form co label/keyboard native va aria-busy khi caller dang xu ly.
 */
function xuLyApDung() {
  if (!props.dangXuLy) {
    emit('apDung')
  }
}

function xuLyDatLai() {
  if (!props.dangXuLy) {
    emit('datLai')
  }
}
</script>

<template>
  <form
    class="bo-loc-danh-sach"
    :aria-busy="props.dangXuLy"
    @submit.prevent="xuLyApDung"
  >
    <div class="bo-loc-danh-sach__truong">
      <slot />
    </div>
    <div class="bo-loc-danh-sach__hanh-dong">
      <button
        class="nut nut--chinh"
        type="submit"
        :disabled="props.dangXuLy"
      >
        {{ props.dangXuLy ? 'Đang áp dụng…' : 'Áp dụng bộ lọc' }}
      </button>
      <button
        class="nut nut--phu"
        type="button"
        :disabled="props.dangXuLy"
        @click="xuLyDatLai"
      >
        Đặt lại
      </button>
      <slot name="hanhDong" />
    </div>
  </form>
</template>
