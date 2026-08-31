<script setup>
defineProps({
  tieuDe: {
    type: String,
    default: 'Không có dữ liệu',
  },
  moTa: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <section
    class="trang-thai-trong"
    role="status"
    aria-live="polite"
  >
    <h2 class="trang-thai-trong__tieu-de">
      {{ tieuDe }}
    </h2>
    <p
      v-if="moTa"
      class="trang-thai-trong__mo-ta"
    >
      {{ moTa }}
    </p>
    <div
      v-if="$slots.hanhDong"
      class="trang-thai-trong__hanh-dong"
    >
      <slot name="hanhDong" />
    </div>
  </section>
</template>
