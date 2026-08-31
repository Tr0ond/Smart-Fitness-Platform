<script setup>
defineProps({
  tieuDe: {
    type: String,
    required: true,
  },
  moTa: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <header class="tieu-de-trang">
    <div class="tieu-de-trang__noi-dung">
      <h1 class="tieu-de-trang__tieu-de">
        {{ tieuDe }}
      </h1>
      <p
        v-if="moTa"
        class="tieu-de-trang__mo-ta"
      >
        {{ moTa }}
      </p>
    </div>
    <div
      v-if="$slots.hanhDong"
      class="tieu-de-trang__hanh-dong"
    >
      <slot name="hanhDong" />
    </div>
  </header>
</template>
