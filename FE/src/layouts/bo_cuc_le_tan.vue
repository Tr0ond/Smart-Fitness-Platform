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
    ma-vai-tro="RECEPTIONIST"
    nhan-vai-tro="Lễ tân"
    :dang-khoi-phuc="dangKhoiPhuc"
  >
    <slot />
  </KhungUngDung>
</template>
