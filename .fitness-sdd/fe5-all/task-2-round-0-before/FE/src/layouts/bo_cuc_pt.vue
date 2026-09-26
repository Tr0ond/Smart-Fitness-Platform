<script setup>
import KhungUngDung from '../components/bo_cuc/khung_ung_dung.vue'

defineProps({
  dangKhoiPhuc: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <KhungUngDung
    ma-vai-tro="PT"
    nhan-vai-tro="Huấn luyện viên"
    :dang-khoi-phuc="dangKhoiPhuc"
  >
    <slot />
  </KhungUngDung>
</template>
