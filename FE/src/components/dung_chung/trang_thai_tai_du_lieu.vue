<script setup>
defineProps({
  dangTai: {
    type: Boolean,
    default: true,
  },
  nhan: {
    type: String,
    default: 'Đang tải dữ liệu…',
  },
})
</script>

<template>
  <div
    v-if="dangTai"
    class="trang-thai-tai-du-lieu"
    role="status"
    aria-live="polite"
    aria-busy="true"
  >
    <span
      class="trang-thai-tai-du-lieu__dau-hieu"
      aria-hidden="true"
    />
    <span>{{ nhan }}</span>
  </div>
</template>
