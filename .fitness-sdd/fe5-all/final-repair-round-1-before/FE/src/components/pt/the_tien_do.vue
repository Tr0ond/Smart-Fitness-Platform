<script setup>
defineProps({
  nhan: {
    type: String,
    required: true,
  },
  giaTri: {
    type: [String, Number],
    default: '—',
  },
  troGiup: {
    type: String,
    default: '',
  },
  donVi: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <article class="pt-the-tien-do">
    <p class="pt-the-tien-do__nhan">{{ nhan }}</p>
    <p class="pt-the-tien-do__gia-tri">
      <strong>{{ giaTri }}</strong>
      <span v-if="donVi" class="pt-the-tien-do__don-vi">{{ donVi }}</span>
    </p>
    <p v-if="troGiup" class="pt-the-tien-do__tro-giup">{{ troGiup }}</p>
  </article>
</template>
