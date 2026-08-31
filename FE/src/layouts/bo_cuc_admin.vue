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
    ma-vai-tro="ADMIN"
    nhan-vai-tro="Quản trị viên"
    :dang-khoi-phuc="dangKhoiPhuc"
  >
    <slot />
  </KhungUngDung>
</template>
