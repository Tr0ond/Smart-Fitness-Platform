<script setup>
const props = defineProps({
  nhan: {
    type: String,
    required: true,
  },
  giaTri: {
    type: [Number, String],
    required: true,
  },
  moTa: {
    type: String,
    default: '',
  },
  dangTai: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <article
    class="the-chi-so"
    :class="{ 'the-chi-so--dang-tai': props.dangTai }"
    :aria-busy="props.dangTai"
  >
    <p class="the-chi-so__nhan">
      {{ props.nhan }}
    </p>
    <p
      v-if="props.dangTai"
      class="the-chi-so__gia-tri the-chi-so__gia-tri--dang-tai"
      role="status"
      aria-live="polite"
    >
      Đang cập nhật…
    </p>
    <p
      v-else
      class="the-chi-so__gia-tri"
    >
      {{ props.giaTri }}
    </p>
    <p
      v-if="props.moTa"
      class="the-chi-so__mo-ta"
    >
      {{ props.moTa }}
    </p>
  </article>
</template>
